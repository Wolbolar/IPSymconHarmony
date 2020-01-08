<?php

declare(strict_types=1);

class HarmonyDevice extends IPSModule
{
    // helper properties
    private $position = 0;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        // 1. Verfügbarer HarmonySplitter wird verbunden oder neu erzeugt, wenn nicht vorhanden.
        $this->ConnectParent('{03B162DB-7A3A-41AE-A676-2444F16EBEDF}');

        $this->RegisterPropertyString('devicename', '');
        $this->RegisterPropertyInteger('DeviceID', 0);
        $this->RegisterPropertyInteger('ConnectionID', 0);
        $this->RegisterPropertyBoolean('BluetoothDevice', false);
        $this->RegisterPropertyBoolean('VolumeControl', false);
        $this->RegisterPropertyInteger('MaxStepVolume', 0);
        $this->RegisterPropertyString('Manufacturer', '');
        $this->RegisterPropertyBoolean('IsKeyboardAssociated', false);
        $this->RegisterPropertyString('model', '');
        $this->RegisterPropertyString('commandset', '');
        $this->RegisterPropertyString('deviceTypeDisplayName', '');
        $this->RegisterPropertyBoolean('HarmonyVars', false);
        $this->RegisterPropertyBoolean('HarmonyScript', false);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        //$this->RegisterVariableString("BufferIN", "BufferIN", "", 1);

        $this->ValidateConfiguration();
    }

    /**
     * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
     * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:.
     */
    private function ValidateConfiguration()
    {
        /*
        $ConnectionID = $this->ReadPropertyInteger("ConnectionID");
        if($ConnectionID > 0)
        {
            IPS_ConnectInstance($this->InstanceID, $ConnectionID);
        }
        */
        //Type und Zone
        $devicename    = $this->ReadPropertyString('devicename');
        $DeviceID      = $this->ReadPropertyInteger('DeviceID');
        $VolumeControl = $this->ReadPropertyBoolean('VolumeControl');
        $MaxStepVolume = $this->ReadPropertyInteger('MaxStepVolume');
        if ($VolumeControl) {
            if ($MaxStepVolume == 0) {
                $this->SetStatus(201);
            }
            if ($MaxStepVolume > 0) {
                $this->RegisterVariableFloat('VolumeSlider', 'Volume', '~Intensity.1', 1);
                $this->EnableAction('VolumeSlider');
            }
        }
        $HarmonyVars = $this->ReadPropertyBoolean('HarmonyVars');
        if ($HarmonyVars && $devicename !== '' && $DeviceID !== '') {
            $this->SetHarmonyInstanceVars();
        }

        //Auswahl Prüfen
        if ($devicename !== '' && $DeviceID !== '') {
            $this->SetStatus(102);
        }
    }

    public function RequestAction($Ident, $Value)
    {
        $ObjID      = $this->GetIDForIdent($Ident);
        $Object     = IPS_GetObject($ObjID);
        $ObjectInfo = $Object['ObjectInfo'];
        $commands   = json_decode($ObjectInfo, true);
        $command    = $commands[$Value];
        if ($Ident == 'VolumeSlider') {
            $this->SetVolumeSlider($Value);
        } else {
            $this->Send($command);
        }
        SetValue($ObjID, $Value);
    }

    public function SetVolumeSlider(float $Value)
    {
        $MaxStepVolume = $this->ReadPropertyInteger('MaxStepVolume');
        $this->SendDebug('Logitech Hub', 'Max Step Volume: ' . print_r($MaxStepVolume, true), 0);
        $CurrentVolume = GetValue($this->GetIDForIdent('VolumeSlider'));
        $this->SendDebug('Logitech Hub', 'Current Volume: ' . print_r($CurrentVolume, true), 0);
        $TargetVolume = round($Value * $MaxStepVolume);
        $this->SendDebug('Logitech Hub', 'Target Volume: ' . print_r($Value, true), 0);
        $this->SendDebug('Logitech Hub', 'Steps to Target Volume: ' . print_r($TargetVolume, true), 0);
        $commandrepeat = 0;
        $command       = 'Unknown';
        if ($Value > $CurrentVolume) {
            $command       = 'VolumeUp';
            $commandrepeat = $TargetVolume - ($CurrentVolume * $MaxStepVolume);
        } elseif ($Value < $CurrentVolume) {
            $command       = 'VolumeDown';
            $commandrepeat = ($CurrentVolume * $MaxStepVolume) - $TargetVolume;
        }
        $commandrepeat = round($commandrepeat);
        $this->SendDebug('Logitech Hub', 'Send Command: ' . print_r($command, true), 0);
        $this->SendDebug('Logitech Hub', 'Repeat Rate: ' . print_r($commandrepeat, true), 0);
        $this->VolumeControl($command, intval($commandrepeat));
    }

    public function VolumeControl(string $command, int $commandrepeat = null)
    {
        for ($i = 0; $i <= $commandrepeat; $i++) {
            $this->Send($command);
            IPS_Sleep(150);
        }
    }

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID); //array
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false; //ConnectionID
    }

    //IP Harmony Hub
    protected function GetIPHarmonyHub()
    {
        $ParentID     = $this->GetParent();
        $IPHarmonyHub = '';
        if ($ParentID) {
            $IPHarmonyHub = IPS_GetProperty(intval($ParentID), 'Host');
        }

        return $IPHarmonyHub;
    }

    //Test zum Senden
    public function SendTest(string $Text)
    {
        IPS_LogMessage('HarmonyHub Device Test', $Text);
        $this->SendDataToParent(
            json_encode(['DataID' => '{EF26FF17-6C5B-4EFE-A7E2-63F599B84345}', 'Buffer' => $Text])
        ); //Harmony Device Interface GUI
    }

    public function Send(string $Command)
    {
        $DeviceID = $this->ReadPropertyInteger('DeviceID');
        $payload  = ['DeviceID' => $DeviceID, 'Command' => $Command, 'BluetoothDevice' => $this->ReadPropertyBoolean('BluetoothDevice')];
        $this->SendDebug('Harmony device id:', strval($DeviceID), 0);
        $this->SendDebug('Command:', $Command, 0);
        $this->SendDataToParent(
            json_encode(['DataID' => '{EF26FF17-6C5B-4EFE-A7E2-63F599B84345}', 'Buffer' => $payload])
        ); //Harmony Device Interface GUI
    }

    //Verfügbare Commands für Instanz ausgeben
    public function GetCommands()
    {
        $currentdeviceid = $this->ReadPropertyInteger('DeviceID');
        $commandlist     = false;
        $config          = $this->SendData('GetHarmonyConfigJSON');
        if (!empty($config)) {
            $data      = json_decode($config, true);
            $devices[] = $data['device'];
            foreach ($devices as $harmonydevicelist) {
                foreach ($harmonydevicelist as $harmonydevice) {
                    // $InstName = $harmonydevice["label"]; //Bezeichnung Harmony Device
                    $DeviceID = $harmonydevice['id']; // Harmony Device ID
                    if ($DeviceID == $currentdeviceid) {
                        $controlGroups = $harmonydevice['controlGroup'];
                        $commandlist   = [];
                        foreach ($controlGroups as $controlGroup) {
                            $commands = $controlGroup['function']; //Function Array
                            foreach ($commands as $command) {
                                $harmonycommand = json_decode($command['action'], true); // command, type, deviceId
                                $commandlist[]  = $harmonycommand['command'];
                            }
                        }
                    }
                }
            }
        }

        return $commandlist;
    }

    // Daten vom Splitter Instanz
    public function ReceiveData($JSONString)
    {

        // Empfangene Daten vom Splitter
        $data         = json_decode($JSONString);
        $datasplitter = $data->Buffer;
        //SetValueString($this->GetIDForIdent("BufferIN"), $datasplitter);
        IPS_LogMessage('ReceiveData Harmony Device', utf8_decode($datasplitter));

        // Hier werden die Daten verarbeitet und in Variablen geschrieben
    }

    protected function CheckVolumeControl()
    {
        $CheckVolumeControl = false;
        $commands           = $this->GetCommands();
        if ($commands) {
            foreach ($commands as $key => $command) {
                if ($command == 'VolumeDown') {
                    $CheckVolumeControl = true;
                }
            }
        }

        return $CheckVolumeControl;
    }

    //Variablen anlegen
    protected function SetupVariable(string $VarIdent, string $VarName, string $VarProfile, $profilemin, $profilemax, $ProfileAssActivities)
    {
        $this->RegisterProfileAssociation(
            'LogitechHarmony.' . $VarProfile, 'Execute', '', '', $profilemin, $profilemax, 0, 0, 1, $ProfileAssActivities
        );
        $variablenID = $this->RegisterVariableInteger($VarIdent, $VarName, 'LogitechHarmony.' . $VarProfile, $this->_getPosition());
        $this->SendDebug(
            'Logitech Device', 'Register Variable: ' . $VarName . ' (' . $VarIdent . ') with profile ' . $VarProfile . ' and ID: ' . $variablenID, 0
        );
        $this->EnableAction($VarIdent);

        return $variablenID;
    }

    protected function SetHarmonyInstanceVars()
    {
        $devicename    = $this->ReadPropertyString('devicename');
        $commands_json = $this->ReadPropertyString('commandset');
        $controlGroups = json_decode($commands_json);
        foreach ($controlGroups as $controlGroup) {
            $name                 = $controlGroup->name;
            $commands             = $controlGroup->function; //Function Array
            $profilemax           = (count($commands)) - 1;
            $ProfileAssActivities = [];

            $assid       = 0;
            $description = [];
            foreach ($commands as $command) {
                $this->SendDebug('Device ' . $devicename, $name . ': ' . $command->action, 0);
                $harmonycommand = json_decode($command->action); // command, type, deviceId
                //Wert , Name, Icon , Farbe
                $ProfileAssActivities[] = [$assid, $harmonycommand->command, '', -1];
                $description[$assid]    = $harmonycommand->command;
                $assid++;
            }
            $descriptionjson   = json_encode($description);
            $profiledevicename = str_replace(' ', '', $devicename);
            $profiledevicename = preg_replace('/[^A-Za-z0-9\-]/', '', $profiledevicename); // Removes special chars.
            $profiledevicename = str_replace('-', '_', $profiledevicename);
            $profilegroupname  = str_replace(' ', '', $name);
            $profilegroupname  = preg_replace('/[^A-Za-z0-9\-]/', '', $profilegroupname); // Removes special chars.
            $profilegroupname  = str_replace('-', '_', $profilegroupname);
            //Variablenprofil anlegen
            $NumberAss = count($ProfileAssActivities);
            $VarIdent  = $this->CreateIdent($name); //Command Group Name
            $VarName   = $name; //Command Group Name
            if ($NumberAss >= 32) {//wenn mehr als 32 Assoziationen splitten
                $splitProfileAssActivities = array_chunk($ProfileAssActivities, 32);
                $splitdescription          = array_chunk($description, 32);
                //2. Array neu setzten
                $id                         = 0;
                $SecondProfileAssActivities = [];
                $seconddescription          = [];
                foreach ($splitProfileAssActivities[1] as $Activity) {
                    $SecondProfileAssActivities[] = [$id, $Activity[1], '', -1];
                    $seconddescription[]          = $Activity[1];
                    $id++;
                }

                //Association 1
                $varid = $this->SetupVariable(
                    $VarIdent, $VarName, $profiledevicename . '.' . $profilegroupname, 0, 31, $splitProfileAssActivities[0]
                ); //32 Associationen

                //Association 2
                $VarIdent1             = $this->CreateIdent($name) . '1'; //Command Group Name
                $VarName1              = $name . '1'; //Command Group Name
                $seconddescriptionjson = json_encode($seconddescription);
                $varid1                = $this->SetupVariable(
                    $VarIdent1, $VarName1, $profiledevicename . '.' . $profilegroupname . '1', 0, ($profilemax - 32), $SecondProfileAssActivities
                );
                IPS_SetInfo($varid1, $seconddescriptionjson);
                $firstdescriptionjson = json_encode($splitdescription[0]);
                IPS_SetInfo($varid, $firstdescriptionjson);
            } else {
                $varid =
                    $this->SetupVariable($VarIdent, $VarName, $profiledevicename . '.' . $profilegroupname, 0, $profilemax, $ProfileAssActivities);
                IPS_SetInfo($varid, $descriptionjson);
            }
        }
    }

    private function CreateIdent($str)
    {
        $search  = [
            'ä',
            'ö',
            'ü',
            'ß',
            'Ä',
            'Ö',
            'Ü',
            '&',
            'é',
            'á',
            'ó',
            ' :)',
            ' :D',
            ' :-)',
            ' :P',
            ' :O',
            ' ;D',
            ' ;)',
            ' ^^',
            ' :|',
            ' :-/',
            ':)',
            ':D',
            ':-)',
            ':P',
            ':O',
            ';D',
            ';)',
            '^^',
            ':|',
            ':-/',
            '(',
            ')',
            '[',
            ']',
            '<',
            '>',
            '!',
            '"',
            '§',
            '$',
            '%',
            '&',
            '/',
            '(',
            ')',
            '=',
            '?',
            '`',
            '´',
            '*',
            "'",
            '-',
            ':',
            ';',
            '²',
            '³',
            '{',
            '}',
            '\\',
            '~',
            '#',
            '+',
            '.',
            ',',
            '=',
            ':',
            '=)', ];
        $replace = [
            'ae',
            'oe',
            'ue',
            'ss',
            'Ae',
            'Oe',
            'Ue',
            'und',
            'e',
            'a',
            'o',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '', ];

        $str = str_replace($search, $replace, $str);
        $str = str_replace(' ', '_', $str); // Replaces all spaces with underline.
        $how = '_';
        //$str = strtolower(preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str));
        $str = preg_replace('/[^a-zA-Z0-9]+/', trim($how), $str);

        return $str;
    }

    //Profile

    /**
     * register profiles.
     *
     * @param $Name
     * @param $Icon
     * @param $Prefix
     * @param $Suffix
     * @param $MinValue
     * @param $MaxValue
     * @param $StepSize
     * @param $Digits
     * @param $Vartype
     */
    protected function RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype)
    {
        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, $Vartype); // 0 boolean, 1 int, 2 float, 3 string,
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != $Vartype) {
                $this->_debug('profile', 'Variable profile type does not match for profile ' . $Name);
            }
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues(
            $Name, $MinValue, $MaxValue, $StepSize
        ); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
    }

    /**
     * register profile association.
     *
     * @param $Name
     * @param $Icon
     * @param $Prefix
     * @param $Suffix
     * @param $MinValue
     * @param $MaxValue
     * @param $Stepsize
     * @param $Digits
     * @param $Vartype
     * @param $Associations
     */
    protected function RegisterProfileAssociation($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype, $Associations)
    {
        if (is_array($Associations) && count($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        }
        $this->RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype);

        if (is_array($Associations)) {
            foreach ($Associations as $Association) {
                IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
            }
        } else {
            $Associations = $this->$Associations;
            foreach ($Associations as $code => $association) {
                IPS_SetVariableProfileAssociation($Name, $code, $this->Translate($association), $Icon, -1);
            }
        }
    }

    /***********************************************************
     * Configuration Form
     ***********************************************************/

    /**
     * build configuration form.
     *
     * @return string
     */
    public function GetConfigurationForm()
    {
        // return current form
        return json_encode(
            [
                'elements' => $this->FormHead(),
                'actions'  => $this->FormActions(),
                'status'   => $this->FormStatus(), ]
        );
    }

    /**
     * return form configurations on configuration step.
     *
     * @return array
     */
    protected function FormHead()
    {
        $form               = [
            [
                'type'    => 'Label',
                'caption' => 'Please create instance or harmony scripts with the harmony configurator', ],
            [
                'name'    => 'devicename',
                'type'    => 'ValidationTextBox',
                'caption' => 'Name', ],
            [
                'name'    => 'DeviceID',
                'type'    => 'NumberSpinner',
                'caption' => 'DeviceID', ],
            [
                'type'    => 'Label',
                'caption' => 'Create Variables', ],
            [
                'name'    => 'HarmonyVars',
                'type'    => 'CheckBox',
                'caption' => 'Harmony variables', ],
            [
                'name'    => 'HarmonyScript',
                'type'    => 'CheckBox',
                'caption' => 'Harmony scripts', ], ];
        $CheckVolumeControl = $this->CheckVolumeControl();
        if ($CheckVolumeControl) {
            $form = array_merge_recursive(
                $form, [
                    [
                        'name'    => 'VolumeControl',
                        'type'    => 'CheckBox',
                        'caption' => 'Volume Control', ],
                    [
                        'name'    => 'MaxStepVolume',
                        'type'    => 'NumberSpinner',
                        'caption' => 'Steps Volume', ], ]
            );
        }

        return $form;
    }

    /**
     * return form actions by token.
     *
     * @return array
     */
    protected function FormActions()
    {
        $form = [];

        return $form;
    }

    /**
     * return from status.
     *
     * @return array
     */
    protected function FormStatus()
    {
        $form = [
            [
                'code'    => 101,
                'icon'    => 'inactive',
                'caption' => 'Creating instance.', ],
            [
                'code'    => 102,
                'icon'    => 'active',
                'caption' => 'configuration valid', ],
            [
                'code'    => 104,
                'icon'    => 'inactive',
                'caption' => 'Harmony Device is inactive', ],
            [
                'code'    => 201,
                'icon'    => 'inactive',
                'caption' => 'Volume step can not be zero.', ],
            [
                'code'    => 202,
                'icon'    => 'error',
                'caption' => 'Harmony Hub IP adress must not empty.', ],
            [
                'code'    => 203,
                'icon'    => 'error',
                'caption' => 'No valid IP adress.', ],
            [
                'code'    => 204,
                'icon'    => 'error',
                'caption' => 'connection to the Harmony Hub lost.', ],
            [
                'code'    => 205,
                'icon'    => 'error',
                'caption' => 'field must not be empty.', ],
            [
                'code'    => 206,
                'icon'    => 'error',
                'caption' => 'select category for import.', ], ];

        return $form;
    }

    /**
     * send debug log.
     *
     * @param string $notification
     * @param string $message
     * @param int    $format       0 = Text, 1 = Hex
     */
    private function _debug(string $notification = null, string $message = null, $format = 0)
    {
        $this->SendDebug($notification, $message, $format);
    }

    /**
     * return incremented position.
     *
     * @return int
     */
    private function _getPosition()
    {
        $this->position++;

        return $this->position;
    }

    /** Eine Anfrage an den IO und liefert die Antwort.
     *
     * @param string $Method
     *
     * @return string
     */
    private function SendData(string $Method)
    {
        $Data['DataID'] = '{EF26FF17-6C5B-4EFE-A7E2-63F599B84345}';
        $Data['Buffer'] = ['Method' => $Method];
        $this->SendDebug('Method:', $Method, 0);
        $devices = @$this->SendDataToParent(json_encode($Data));

        return $devices;
    }
}

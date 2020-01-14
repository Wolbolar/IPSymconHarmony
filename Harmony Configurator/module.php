<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
require_once __DIR__ . '/../libs/HarmonyBufferHelper.php';
require_once __DIR__ . '/../libs/HarmonyDebugHelper.php';

class HarmonyConfigurator extends IPSModule
{
    use HarmonyBufferHelper; use HarmonyDebugHelper;
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        // 1. Verfügbarer Harmony Hub wird verbunden oder neu erzeugt, wenn nicht vorhanden.
        $this->ConnectParent('{03B162DB-7A3A-41AE-A676-2444F16EBEDF}');
        $this->RegisterPropertyInteger('ImportCategoryID', 0);
        $this->RegisterPropertyString('name', '');
        $this->RegisterPropertyString('uuid', '');
        $this->RegisterPropertyString('host', '');
        $this->RegisterPropertyBoolean('HarmonyVars', false);
        $this->RegisterPropertyBoolean('HarmonyScript', false);
    }

    /**
     * Interne Funktion des SDK.
     */
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
    }

    // Geräte Skripte und Links anlegen
    public function SetupHarmony()
    {
        $MyParent      = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $HubCategoryID = $this->CreateHarmonyHubCategory();
        //Konfig prüfen
        $HarmonyConfig = $this->SendData('GetHarmonyConfigJSON');
        if ($HarmonyConfig == '') {
            $timestamp = time();
            $this->SendData('getConfig');
            $i = 0;
            do {
                IPS_Sleep(10);
                $updatetimestamp = $this->SendData('GetHarmonyConfigTimestamp');

                //echo $i."\n";
                $i++;
            } while ($updatetimestamp <= $timestamp);
        }

        //Skripte installieren
        $HarmonyScript = $this->ReadPropertyBoolean('HarmonyScript');
        if ($HarmonyScript == true) {
            $this->SendDebug('Harmony Hub Configurator', 'Setup Scripts', 0);
            $this->SetHarmonyInstanceScripts($HubCategoryID);
        }

        //Harmony Aktivity Skripte setzten
        $this->SetupActivityScripts($HubCategoryID);

        //Harmony Aktivity Link setzten
        $this->CreateAktivityLink();
    }

    protected function CreateHarmonyHubCategory()
    {
        $MyParent   = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $hubip      = $this->SendData('GetHubIP');
        $hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline.
        $hubname    = GetValue(IPS_GetObjectIDByIdent('HarmonyHubName', $MyParent));
        $CategoryID = $this->CreateHarmonyScriptCategory();
        //Prüfen ob Kategorie schon existiert
        $HubCategoryID = @IPS_GetObjectIDByIdent('CatLogitechHub_' . $hubipident, $CategoryID);
        if ($HubCategoryID === false) {
            $HubCategoryID = IPS_CreateCategory();
            IPS_SetName($HubCategoryID, 'Logitech ' . $hubname . ' (' . $hubip . ')');
            IPS_SetIdent($HubCategoryID, 'CatLogitechHub_' . $hubipident); // Ident muss eindeutig sein
            IPS_SetInfo($HubCategoryID, $hubip);
            IPS_SetParent($HubCategoryID, $CategoryID);
        }
        $this->SendDebug('Hub Hub Script Category', strval($HubCategoryID), 0);

        return $HubCategoryID;
    }

    protected function CreateHarmonyScriptCategory()
    {
        $CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
        //Prüfen ob Kategorie schon existiert
        $HubScriptCategoryID = @IPS_GetObjectIDByIdent('CatLogitechHubScripts', $CategoryID);
        if ($HubScriptCategoryID === false) {
            $HubScriptCategoryID = IPS_CreateCategory();
            IPS_SetName($HubScriptCategoryID, $this->Translate('Harmony Scripts'));
            IPS_SetIdent($HubScriptCategoryID, 'CatLogitechHubScripts');
            IPS_SetInfo($HubScriptCategoryID, $this->Translate('Harmony Scripts'));
            IPS_SetParent($HubScriptCategoryID, $CategoryID);
        }
        $this->SendDebug('Hub Script Category', strval($HubScriptCategoryID), 0);

        return $HubScriptCategoryID;
    }

    protected function GetCurrentHarmonyDevices()
    {
        $HarmonyInstanceIDList = IPS_GetInstanceListByModuleID('{B0B4D0C2-192E-4669-A624-5D5E72DBB555}'); // Harmony Devices
        $HarmonyInstanceList   = [];
        foreach ($HarmonyInstanceIDList as $key => $HarmonyInstanceID) {
            $devicename                     = IPS_GetProperty($HarmonyInstanceID, 'devicename');
            $deviceid                       = IPS_GetProperty($HarmonyInstanceID, 'DeviceID');
            $HarmonyInstanceList[$deviceid] = ['objid' => $HarmonyInstanceID, 'devicename' => $devicename, 'deviceid' => $deviceid];
        }

        return $HarmonyInstanceList;
    }

    protected function SetHarmonyInstanceScripts($HubCategoryID)
    {
        $HarmonyInstanceList = $this->GetCurrentHarmonyDevices(); // Harmony Devices

        $config = $this->SendData('GetHarmonyConfigJSON');
        if (!empty($config)) {
            $data         = json_decode($config, true);
            $activities[] = $data['activity'];
            $devices[]    = $data['device'];
            foreach ($devices as $harmonydevicelist) {
                foreach ($harmonydevicelist as $harmonydevice) {
                    $harmonyid = $harmonydevice['id'];
                    // check if instance with $harmonyid exists
                    $HarmonyInstance_Key = array_key_exists($harmonyid, $HarmonyInstanceList);
                    if ($HarmonyInstance_Key) {
                        $harmony_objid = $HarmonyInstanceList[$harmonyid]['objid'];
                        $controlGroups = $harmonydevice['controlGroup'];
                        //Kategorien anlegen
                        //Prüfen ob Kategorie schon existiert
                        $MainCatID = @IPS_GetObjectIDByIdent('Logitech_Device_Cat' . $harmonydevice['id'], $HubCategoryID);
                        if ($MainCatID === false) {
                            $MainCatID = IPS_CreateCategory();
                            IPS_SetName($MainCatID, utf8_decode($harmonydevice['label']));
                            IPS_SetInfo($MainCatID, $harmonydevice['id']);
                            IPS_SetIdent($MainCatID, 'Logitech_Device_Cat' . $harmonydevice['id']);
                            IPS_SetParent($MainCatID, $HubCategoryID);
                        }
                        foreach ($controlGroups as $controlGroup) {
                            $commands = $controlGroup['function']; //Function Array

                            //Prüfen ob Kategorie schon existiert
                            $CGID = @IPS_GetObjectIDByIdent(
                                'Logitech_Device_' . $harmonydevice['id'] . '_Controllgroup_' . $controlGroup['name'], $MainCatID
                            );
                            if ($CGID === false) {
                                $CGID = IPS_CreateCategory();
                                IPS_SetName($CGID, $controlGroup['name']);
                                IPS_SetIdent($CGID, 'Logitech_Device_' . $harmonydevice['id'] . '_Controllgroup_' . $controlGroup['name']);
                                IPS_SetParent($CGID, $MainCatID);
                            }

                            $assid = 0;
                            foreach ($commands as $command) {
                                $harmonycommand = json_decode($command['action'], true); // command, type, deviceId
                                //Prüfen ob Script schon existiert
                                $Scriptname         = $command['label'];
                                $controllgroupident =
                                    $this->CreateIdent('Logitech_Device_' . $harmonydevice['id'] . '_Command_' . $harmonycommand['command']);
                                $ScriptID           = @IPS_GetObjectIDByIdent($controllgroupident, $CGID);
                                if ($ScriptID === false) {
                                    $ScriptID = IPS_CreateScript(0);
                                    IPS_SetName($ScriptID, $Scriptname);
                                    IPS_SetParent($ScriptID, $CGID);
                                    IPS_SetIdent($ScriptID, $controllgroupident);
                                    $content = '<? LHD_Send(' . $harmony_objid . ', "' . $harmonycommand['command'] . '");?>';
                                    IPS_SetScriptContent($ScriptID, $content);
                                }
                                $assid++;
                            }
                        }
                    }
                }
            }
        }
    }

    public function SetupActivityScripts(int $HubCategoryID)
    {
        $MyParent        = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $hubip           = $this->SendData('GetHubIP');
        $hubipident      = str_replace('.', '_', $hubip); // Replaces all . with underline.
        $hubname         = GetValue(IPS_GetObjectIDByIdent('HarmonyHubName', $MyParent));
        $activities_json = $this->SendData('GetAvailableAcitivities');
        $this->SendDebug('Harmony Hub Activities', $activities_json, 0);
        if (!empty($activities_json)) {
            $activities = json_decode($activities_json, true);
            //Prüfen ob Kategorie schon existiert
            $this->SendDebug('Top Category', IPS_GetName($HubCategoryID), 0);
            $MainCatID = @IPS_GetObjectIDByIdent('LogitechActivitiesScripts_' . $hubipident, $HubCategoryID);
            if ($MainCatID === false) {
                $MainCatID = IPS_CreateCategory();
                IPS_SetName($MainCatID, $hubname . $this->Translate(' Activities'));
                IPS_SetInfo($MainCatID, $hubname . $this->Translate(' Activities'));
                //IPS_SetIcon($NeueInstance, $Quellobjekt['ObjectIcon']);
                //IPS_SetPosition($NeueInstance, $Quellobjekt['ObjectPosition']);
                //IPS_SetHidden($NeueInstance, $Quellobjekt['ObjectIsHidden']);
                IPS_SetIdent($MainCatID, 'LogitechActivitiesScripts_' . $hubipident);
                IPS_SetParent($MainCatID, $HubCategoryID);
            }
            $this->SendDebug('Activity Category', strval($MainCatID), 0);
            $ScriptID = false;
            foreach ($activities as $activityname => $activity) {
                //Prüfen ob Script schon existiert
                $ScriptID = $this->CreateActivityScript($activityname, $MainCatID, $hubip, $activity);
            }

            return $ScriptID;
        }

        return false;
    }

    protected function CreateActivityScript($Scriptname, $MainCatID, $hubip, $activity)
    {
        $MyParent    = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $Scriptname  = $this->ReplaceSpecialCharacters($Scriptname);
        $hubipident  = str_replace('.', '_', $hubip); // Replaces all . with underline.
        $Ident       = 'Script_Hub_' . $hubipident . '_' . $activity;
        $scriptident = $this->CreateIdent($Ident);
        $ScriptID    = @IPS_GetObjectIDByIdent($scriptident, $MainCatID);

        if ($ScriptID === false) {
            $ScriptID = IPS_CreateScript(0);
            IPS_SetName($ScriptID, $Scriptname);
            IPS_SetParent($ScriptID, $MainCatID);
            IPS_SetIdent($ScriptID, $scriptident);
            $content = '<?
Switch ($_IPS[\'SENDER\']) 
    { 
    Default: 
    Case "RunScript": 
		HarmonyHub_startActivity(' . $MyParent . ', ' . $activity . ');
    Case "Execute": 
        HarmonyHub_startActivity(' . $MyParent . ', ' . $activity . ');
    Case "TimerEvent": 
        break; 

    Case "Variable": 
    Case "AlexaSmartHome": // Schalten durch den Alexa SmartHomeSkill
           
    if ($_IPS[\'VALUE\'] == True) 
        { 
            // einschalten
            HarmonyHub_startActivity(' . $MyParent . ', ' . $activity . ');   
        } 
    else 
        { 
            //ausschalten
            HarmonyHub_startActivity(' . $MyParent . ', -1);
        } 
       break;
    Case "WebFront":        // Zum schalten im Webfront 
        HarmonyHub_startActivity(' . $MyParent . ', ' . $activity . ');   
    }  
?>';
            IPS_SetScriptContent($ScriptID, $content);
        }

        return $ScriptID;
    }

    protected function ReplaceSpecialCharacters($string)
    {
        $string = str_replace('Ã¼', 'ü', $string);

        return $string;
    }

    protected function CreateIdent($str)
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

    //Link für Harmony Activity anlegen
    public function CreateAktivityLink()
    {
        $MyParent      = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $hubip         = $this->SendData('GetHubIP');
        $hubipident    = str_replace('.', '_', $hubip); // Replaces all . with underline.
        $hubname       = GetValue(IPS_GetObjectIDByIdent('HarmonyHubName', $MyParent));
        $HubCategoryID = $this->CreateHarmonyHubCategory();
        //Prüfen ob Instanz schon vorhanden
        $InstanzID = @IPS_GetObjectIDByIdent('Logitech_Harmony_Hub_Activities_' . $hubipident, $HubCategoryID);
        if ($InstanzID === false) {
            $InsID = IPS_CreateInstance('{485D0419-BE97-4548-AA9C-C083EB82E61E}');
            IPS_SetName($InsID, $hubname); // Instanz benennen
            IPS_SetIdent($InsID, 'Logitech_Harmony_Hub_Activities_' . $hubipident);
            IPS_SetParent($InsID, $HubCategoryID); // Instanz einsortieren unter dem Objekt mit der ID "$HubCategoryID"

            // Anlegen eines neuen Links für Harmony Aktivity
            $LinkID = IPS_CreateLink();             // Link anlegen
            IPS_SetName($LinkID, 'Logitech Harmony Hub Activity'); // Link benennen
            IPS_SetParent($LinkID, $InsID); // Link einsortieren
            IPS_SetLinkTargetID($LinkID, IPS_GetObjectIDByIdent('HarmonyActivity', $MyParent));    // Link verknüpfen
        }
    }

    //Profile
    protected function RegisterProfileIntegerHarmony($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
    {
        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != 1) {
                $this->SendDebug('Harmony Hub', 'Variable profile type does not match for profile ' . $Name, 0);
            }
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues(
            $Name, $MinValue, $MaxValue, $StepSize
        ); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
    }

    protected function RegisterProfileIntegerHarmonyAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Associations)
    {
        if (count($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        }
        /*
        else {
            //undefiened offset
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        */
        $this->RegisterProfileIntegerHarmony($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits);

        //boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, int $Farbe )
        foreach ($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
    }

    public function RefreshListConfiguration()
    {
        $this->Get_ListConfiguration();
    }

    /**
     * Liefert alle Geräte.
     *
     * @return array configlist all devices
     */
    private function Get_ListConfiguration()
    {
        $config_list           = [];
        $HarmonyInstanceIDList = IPS_GetInstanceListByModuleID('{B0B4D0C2-192E-4669-A624-5D5E72DBB555}'); // Harmony Devices
        $MyParent              = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $this->SendDebug('Harmony Config', 'Configurator ConnectionID: ' . $MyParent, 0);
        $hostname = GetValue(IPS_GetObjectIDByIdent('HarmonyHubName', $MyParent));
        $hubip    = $this->SendData('GetHubIP');
        $config   = $this->SendData('GetHarmonyConfigJSON');
        $this->SendDebug('Harmony Config', $config, 0);
        if (!empty($config)) {
            $data    = json_decode($config);
            $devices = $data->device;
            foreach ($devices as $harmonydevice) {
                $instanceID          = 0;
                $harmony_device_name = $harmonydevice->label; //Bezeichnung Harmony Device
                $this->SendDebug('Harmony Config', 'device name: ' . utf8_decode($harmony_device_name), 0);
                $manufacturer = $harmonydevice->manufacturer; // manufacturer
                $this->SendDebug('Harmony Config', 'manufacturer: ' . $manufacturer, 0);
                $commandset            = $harmonydevice->controlGroup;
                $commandset_json       = json_encode($commandset);
                $IsKeyboardAssociated  = $harmonydevice->IsKeyboardAssociated;
                $model                 = $harmonydevice->model;
                $device_id             = intval($harmonydevice->id); //DeviceID des Geräts
                $deviceTypeDisplayName = $harmonydevice->deviceTypeDisplayName;
                foreach ($HarmonyInstanceIDList as $HarmonyInstanceID) {
                    if (IPS_GetInstance($HarmonyInstanceID)['ConnectionID'] == $MyParent
                        && $device_id == IPS_GetProperty(
                            $HarmonyInstanceID, 'DeviceID'
                        )) {
                        // $this->SendDebug('Harmony Config', 'Configurator ConnectionID: '.IPS_GetInstance($HarmonyInstanceID)['ConnectionID'] , 0);
                        $harmony_device_name = IPS_GetName($HarmonyInstanceID);
                        $this->SendDebug('Harmony Config', 'device found: ' . utf8_decode($harmony_device_name) . ' (' . $HarmonyInstanceID . ')', 0);
                        $instanceID = $HarmonyInstanceID;
                    }
                }
                if (property_exists($harmonydevice, 'BTAddress')) {
                    $BluetoothDevice = true;
                    // $blutooth_address = $harmonydevice->BTAddress;
                    $this->SendDebug('Harmony Config', 'device name: ' . utf8_decode($harmony_device_name) . ' use bluetooth', 0);
                } else {
                    $BluetoothDevice = false;
                    $this->SendDebug('Harmony Config', 'device name: ' . utf8_decode($harmony_device_name) . ' does not use bluethooth', 0);
                }
                $config_list[] = [
                    'instanceID'            => $instanceID,
                    'id'                    => $device_id,
                    'name'                  => $harmony_device_name,
                    'manufacturer'          => $manufacturer,
                    'deviceTypeDisplayName' => $deviceTypeDisplayName,
                    'deviceid'              => $device_id,
                    'create'                => [

                        'moduleID'      => '{B0B4D0C2-192E-4669-A624-5D5E72DBB555}',
                        'configuration' => [
                            'devicename'            => $harmony_device_name,
                            'DeviceID'              => $device_id,
                            'ConnectionID'          => $MyParent,
                            'BluetoothDevice'       => $BluetoothDevice,
                            'VolumeControl'         => false,
                            'MaxStepVolume'         => 0,
                            'Manufacturer'          => $manufacturer,
                            'IsKeyboardAssociated'  => $IsKeyboardAssociated,
                            'model'                 => $model,
                            'commandset'            => $commandset_json,
                            'deviceTypeDisplayName' => $deviceTypeDisplayName,
                            'HarmonyVars'           => $this->ReadPropertyBoolean('HarmonyVars'),
                            'HarmonyScript'         => $this->ReadPropertyBoolean('HarmonyScript'), ],
                        'location'      => $this->SetLocation($hostname, $hubip)

                    ], ];
            }
        }

        return $config_list;
    }

    private function SetLocation($hostname, $hubip)
    {
        /*
        $tree_position = [
            $this->Translate('devices'), $this->Translate('harmony devices'), $hostname . " (" . $hubip . ")"
        ];
        */

        $category        = $this->ReadPropertyInteger('ImportCategoryID');
        $tree_position[] = IPS_GetName($category);
        $parent          = IPS_GetObject($category)['ParentID'];
        $tree_position[] = IPS_GetName($parent);
        do {
            $parent          = IPS_GetObject($parent)['ParentID'];
            $tree_position[] = IPS_GetName($parent);
        } while ($parent > 0);
        // delete last key
        end($tree_position);
        $lastkey = key($tree_position);
        unset($tree_position[$lastkey]);
        // reverse array
        $tree_position = array_reverse($tree_position);
        array_push($tree_position, $this->Translate('harmony devices'));
        array_push($tree_position, $hostname . ' (' . $hubip . ')');
        $this->SendDebug('Harmony Location', json_encode($tree_position), 0);

        return $tree_position;
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
        $Form = json_encode(
            [
                'elements' => $this->FormHead(),
                'actions'  => $this->FormActions(),
                'status'   => $this->FormStatus(), ]
        );
        $this->SendDebug('FORM', $Form, 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);

        return $Form;
    }

    /**
     * return form configurations on configuration step.
     *
     * @return array
     */
    protected function FormHead()
    {
        $category = false;

        $form = [
            [
                'type'  => 'Image',
                'image' => 'data:image/png;base64, iVBORw0KGgoAAAANSUhEUgAAAWYAAABuCAYAAAAUArnPAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA4BpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDoxOTNBMzYyQzJCMzExMUU3ODhERUJGMkRGNkE1MThGMCIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDo4M0Q3OTU0Q0ZCRDExMUU4OTU0MDgwMUMwRTVDQThCMiIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDo4M0Q3OTU0QkZCRDExMUU4OTU0MDgwMUMwRTVDQThCMiIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ0MgMjAxNyAoV2luZG93cykiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpmNGI2YTBmYS1mYTFiLTg5NDItYTFkZS1lNjY1ODdmMmU3YzIiIHN0UmVmOmRvY3VtZW50SUQ9ImFkb2JlOmRvY2lkOnBob3Rvc2hvcDo0ZDk4NjMxOS1mYmQxLTExZTgtOTZlMC05Njgx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', ],
            [
                'type'  => 'Label',
                'label' => 'category for Logitech Harmony Hub devices:', ],
            [
                'name'    => 'ImportCategoryID',
                'type'    => 'SelectCategory',
                'caption' => 'category harmony', ],
            [
                'type'  => 'Label',
                'label' => 'Create Harmony devices for remote control:', ],
            [
                'type'  => 'Label',
                'label' => 'Create variables for webfront (Please note: High numbers of variables)', ],
            [
                'name'    => 'HarmonyVars',
                'type'    => 'CheckBox',
                'caption' => 'Harmony variables', ],
            [
                'type'  => 'Label',
                'label' => 'create scripts for remote control (alternative or addition for remote control via webfront):', ],
            [
                'name'    => 'HarmonyScript',
                'type'    => 'CheckBox',
                'caption' => 'Harmony script', ],
            [
                'name'     => 'HarmonyConfiguration',
                'type'     => 'Configurator',
                'rowCount' => 20,
                'add'      => false,
                'delete'   => true,
                'sort'     => [
                    'column'    => 'name',
                    'direction' => 'ascending', ],
                'columns'  => [
                    [
                        'label'   => 'ID',
                        'name'    => 'id',
                        'width'   => '200px',
                        'visible' => false, ],
                    [
                        'label' => 'device name',
                        'name'  => 'name',
                        'width' => 'auto', ],
                    [
                        'label' => 'manufacturer',
                        'name'  => 'manufacturer',
                        'width' => '250px', ],
                    [
                        'label' => 'type',
                        'name'  => 'deviceTypeDisplayName',
                        'width' => '250px', ],
                    [
                        'label' => 'device id',
                        'name'  => 'deviceid',
                        'width' => '200px', ], ],
                'values'   => $this->Get_ListConfiguration(), ], ];

        if ($category) {
            $form = array_merge_recursive(
                $form, [
                    [
                        'name'    => 'script_category',
                        'type'    => 'SelectCategory',
                        'caption' => 'Script category', ], ]
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
        $MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $form     = [
            [
                'type'    => 'Label',
                'caption' => '1. Read Logitech Harmony Hub configuration:', ],
            [
                'type'    => 'Button',
                'caption' => 'Read configuration',
                'onClick' => 'HarmonyHub_getConfig(' . $MyParent . ');', ],
            [
                'type'    => 'Label',
                'caption' => '2. Get device list:', ],
            [
                'type'    => 'Button',
                'caption' => 'Refresh list',
                'onClick' => 'HarmonyConfig_RefreshListConfiguration($id);', ],
            [
                'type'    => 'Label',
                'caption' => '3. Setup Harmony (create activity scripts and scripts for devices created by the configurator):', ],
            [
                'type'    => 'Button',
                'caption' => 'Setup Harmony',
                'onClick' => 'HarmonyConfig_SetupHarmony($id);', ], ];

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
                'caption' => 'Harmony configurator created.', ],
            [
                'code'    => 104,
                'icon'    => 'inactive',
                'caption' => 'interface closed.', ],
            [
                'code'    => 201,
                'icon'    => 'inactive',
                'caption' => 'Please follow the instructions.', ],
            [
                'code'    => 202,
                'icon'    => 'error',
                'caption' => 'no category selected.', ], ];

        return $form;
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
        $result = @$this->SendDataToParent(json_encode($Data));
        $this->SendDebug('Send data result:', $result, 0);

        return $result;
    }
}

<?

class HarmonyfakeRoku extends IPSModule
{
    // helper properties
    private $position = 0;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyInteger('HarmonyHubObjID', 0);
        $this->RegisterPropertyInteger('HarmonyHubActivity', 0);
        $this->CreateActivityProperties();
        //we will wait until the kernel is ready
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }


    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }

        $this->RegisterProfileAssociation(
            'LogitechHarmony.FakeRoku', 'Keyboard', '', '', 0, 1, 0, 0, 1, [
                                          [0, $this->Translate('Up'), '', -1],
                                          [1, $this->Translate('Down'), '', -1],
                                          [2, $this->Translate('Left'), '', -1],
                                          [3, $this->Translate('Right'), '', -1],
                                          [4, $this->Translate('Select'), '', -1],
                                          [5, $this->Translate('Back'), '', -1],
                                          [6, $this->Translate('Play'), '', -1],
                                          [7, $this->Translate('Reverse'), '', -1],
                                          [8, $this->Translate('Forward'), '', -1],
                                          [9, $this->Translate('Search'), '', -1],
                                          [10, $this->Translate('info'), '', -1],
                                          [11, $this->Translate('Home'), '', -1],
                                          [12, $this->Translate('Instant Replay'), '', -1]]
        );

        $this->RegisterVariableInteger("KeyFakeRoku", "Roku Emulator", "LogitechHarmony.FakeRoku", $this->_getPosition());
        $this->EnableAction("KeyFakeRoku");
        $LastKeystrokeFakeRokuID = $this->RegisterVariableString("LastKeystrokeFakeRoku", "Letzter Tastendruck", "", $this->_getPosition());
        IPS_SetIcon($LastKeystrokeFakeRokuID, "Keyboard");
        $this->ValidateConfiguration();

    }

    /**
     * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
     * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
     *
     */
    private function ValidateConfiguration()
    {
        $this->RegisterHook("/hook/fhem/fakeRoku");
        $this->SetStatus(IS_ACTIVE);

    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {

        switch ($Message) {
            case IM_CHANGESTATUS:
                if ($Data[0] === IS_ACTIVE) {
                    $this->ApplyChanges();
                }
                break;

            case IPS_KERNELMESSAGE:
                if ($Data[0] === KR_READY) {
                    $this->ApplyChanges();
                }
                break;

            default:
                break;
        }
    }

    public function RequestAction($Ident, $Value)
    {
        $this->SetValue($Ident, $Value);
    }

    private function RegisterHook($WebHook)
    {
        $ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
        if (sizeof($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found                     = true;
                }
            }
            if (!$found) {
                $hooks[] = ["Hook" => $WebHook, "TargetID" => $this->InstanceID];
            }
            IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

    protected function StartRokuKeyscript($command)
    {
        $activity     = $this->GetCurrentActivity();
        $activityname = $activity["activityname"];
        $activityid   = $activity["activityid"];
        $this->SendDebug("Logitech Roku", "Current activity: " . $activityname, 0);
        $this->SendDebug("Logitech Roku", "Current activity id: " . $activityid, 0);
        $harmonyid = $this->ReadPropertyInteger("HarmonyHubObjID");
        $this->SendDebug("Logitech Roku", "Harmony hub object ID: " . $harmonyid, 0);
        $list_json = $this->ReadPropertyString("rokukeys_" . $harmonyid . '_' . abs($activityid));
        $this->SendDebug("Logitech Roku", "Property: " . "rokukeys_" . $harmonyid . '_' . abs($activityid), 0);
        $this->SendDebug("Logitech Roku", "List Roku Command: " . $list_json, 0);
        $list = json_decode($list_json, true);
        foreach ($list as $rokucommand) {
            if ($command == $rokucommand["command"]) {
                if (!empty($rokucommand["rokuscript"])) {
                    $this->SendDebug(
                        "Logitech Roku",
                        "Roku starts script: " . utf8_decode(IPS_GetName($rokucommand["rokuscript"])) . " (" . $rokucommand["rokuscript"] . ")", 0
                    );
                    $this->SendDebug("Logitech Roku", "Command " . $command . " for activity " . $activityname, 0);
                    IPS_RunScriptEx($rokucommand["rokuscript"], ["Command" => $command, "Activity" => $activityname]);
                } else {
                    $this->SendDebug("Logitech Roku", "no script to lauch selected", 0);
                    $this->SendDebug("Logitech Roku", "Command " . $command . " for activity " . $activityname, 0);
                }
            }
        }
    }

    protected function GetCurrentActivity()
    {
        $HarmonyHubObjID = $this->ReadPropertyInteger("HarmonyHubObjID");
        $activityname    = GetValueFormatted(IPS_GetObjectIDByIdent("HarmonyActivity", $HarmonyHubObjID));
        $activityid      = GetValue(IPS_GetObjectIDByIdent("HarmonyActivity", $HarmonyHubObjID));
        $activity        = ["activityname" => $activityname, "activityid" => $activityid];
        return $activity;
    }

    /**
     * This function will be called by the hook control. Visibility should be protected!
     */

    protected function ProcessHookData()
    {
        //workaround for bug
        if (!isset($_IPS)) {
            global $_IPS;
        }
        if ($_IPS['SENDER'] == "Execute") {
            echo "This script cannot be used this way.";
            return;
        }
        //Auswerten von Events von FHEM fakeRoku
        // FHEM nutzt GET
        if (isset($_GET["fhemevent"])) {
            $data = $_GET["fhemevent"];
            $this->SendDebug("Logitech Harmony Hub", "Roku Command: " . $data, 0);
            $this->WriteValues($data);
        }
    }

    protected function WriteValues($data)
    {
        if ($data == "Up") {
            $this->SetValue("KeyFakeRoku", 0);
            $this->SetValue("LastKeystrokeFakeRoku", "Up");
            $this->StartRokuKeyscript("Up");
        } elseif ($data == "Down") {
            $this->SetValue("KeyFakeRoku", 1);
            $this->SetValue("LastKeystrokeFakeRoku", "Down");
            $this->StartRokuKeyscript("Down");
        } elseif ($data == "Left") {
            $this->SetValue("KeyFakeRoku", 2);
            $this->SetValue("LastKeystrokeFakeRoku", "Left");
            $this->StartRokuKeyscript("Left");
        } elseif ($data == "Right") {
            $this->SetValue("KeyFakeRoku", 3);
            $this->SetValue("LastKeystrokeFakeRoku", "Right");
            $this->StartRokuKeyscript("Right");
        } elseif ($data == "Select") {
            $this->SetValue("KeyFakeRoku", 4);
            $this->SetValue("LastKeystrokeFakeRoku", "Select");
            $this->StartRokuKeyscript("Select");
        } elseif ($data == "Back") {
            $this->SetValue("KeyFakeRoku", 5);
            $this->SetValue("LastKeystrokeFakeRoku", "Back");
            $this->StartRokuKeyscript("Back");
        } elseif ($data == "Play") {
            $this->SetValue("KeyFakeRoku", 6);
            $this->SetValue("LastKeystrokeFakeRoku", "Play");
            $this->StartRokuKeyscript("Play");
        } elseif ($data == "Rev") {
            $this->SetValue("KeyFakeRoku", 7);
            $this->SetValue("LastKeystrokeFakeRoku", "Rev");
            $this->StartRokuKeyscript("Rev");
        } elseif ($data == "Fwd") {
            $this->SetValue("KeyFakeRoku", 8);
            $this->SetValue("LastKeystrokeFakeRoku", "Fwd");
            $this->StartRokuKeyscript("Fwd");
        } elseif ($data == "Search") {
            $this->SetValue("KeyFakeRoku", 9);
            $this->SetValue("LastKeystrokeFakeRoku", "Search");
            $this->StartRokuKeyscript("Search");
        } elseif ($data == "Info") {
            $this->SetValue("KeyFakeRoku", 10);
            $this->SetValue("LastKeystrokeFakeRoku", "Info");
            $this->StartRokuKeyscript("Info");
        } elseif ($data == "Home") {
            $this->SetValue("KeyFakeRoku", 11);
            $this->SetValue("LastKeystrokeFakeRoku", "Home");
            $this->StartRokuKeyscript("Home");
        } elseif ($data == "InstantReplay") {
            $this->SetValue("KeyFakeRoku", 12);
            $this->SetValue("LastKeystrokeFakeRoku", "InstantReplay");
            $this->StartRokuKeyscript("InstantReplay");
        }
    }

    /**
     * register profiles
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
     * register profile association
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
        if (is_array($Associations) && sizeof($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        }
        $this->RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype);

        if (is_array($Associations)) {
            foreach ($Associations AS $Association) {
                IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
            }
        } else {
            $Associations = $this->$Associations;
            foreach ($Associations AS $code => $association) {
                IPS_SetVariableProfileAssociation($Name, $code, $this->Translate($association), $Icon, -1);
            }
        }

    }

    //Variablen anlegen
    public function SetupVariable(string $VarIdent, string $VarName, string $VarProfile)
    {
        $variablenID = $this->RegisterVariableInteger($VarIdent, $VarName, $VarProfile);
        $this->EnableAction($VarIdent);
        return $variablenID;
    }

    protected function GetHarmonyHubs()
    {
        $harmonyhubs = IPS_GetInstanceListByModuleID("{03B162DB-7A3A-41AE-A676-2444F16EBEDF}"); // Harmony Hub;
        return $harmonyhubs;
    }

    protected function GetHarmonyHubList()
    {
        $harmonyhubs = $this->GetHarmonyHubs();
        $options     = [
            [
                'label' => 'Please choose',
                'value' => 0]];
        foreach ($harmonyhubs as $harmonyhub) {
            $options[] = [
                'label' => IPS_GetName($harmonyhub),
                'value' => $harmonyhub];
        }
        return $options;
    }

    protected function GetHubActivities($HubID)
    {
        $activities = HarmonyHub_GetAvailableAcitivities($HubID);
        return $activities;
    }

    protected function GetHubActivitiesExpansionPanels($HubID, $form)
    {
        if (strlen($HubID) == 5) {
            $activities        = $this->GetHubActivities($HubID);
            $number_activities = count($activities);
            if ($number_activities > 0) {
                foreach ($activities as $key => $activity) {
                    $form = array_merge_recursive(
                        $form, [
                                 [
                                     'type'    => 'ExpansionPanel',
                                     'caption' => $key,
                                     'items'   => [
                                         [
                                             'type'     => 'List',
                                             'name'     => $this->GetListName($HubID, $activity),
                                             'caption'  => 'Roku Emulator Keys',
                                             'rowCount' => 13,
                                             'add'      => false,
                                             'delete'   => false,
                                             'sort'     => [
                                                 'column'    => 'command',
                                                 'direction' => 'ascending'],
                                             'columns'  => [
                                                 [
                                                     'name'    => 'command',
                                                     'label'   => 'command',
                                                     'width'   => '200px',
                                                     'save'    => true,
                                                     'visible' => true],
                                                 [
                                                     'name'  => 'rokuscript',
                                                     'label' => 'script',
                                                     'width' => 'auto',
                                                     'save'  => true,
                                                     'edit'  => [
                                                         'type' => 'SelectScript']],
                                                 [
                                                     'name'    => 'key_id',
                                                     'label'   => 'Key ID',
                                                     'width'   => 'auto',
                                                     'save'    => true,
                                                     'visible' => false]],
                                             'values'   => [
                                                 [
                                                     'command' => "Up",
                                                     'key_id'  => 0],
                                                 [
                                                     'command' => "Down",
                                                     'key_id'  => 1],
                                                 [
                                                     'command' => "Left",
                                                     'key_id'  => 2],
                                                 [
                                                     'command' => "Right",
                                                     'key_id'  => 3],
                                                 [
                                                     'command' => "Select",
                                                     'key_id'  => 4],
                                                 [
                                                     'command' => "Back",
                                                     'key_id'  => 5],
                                                 [
                                                     'command' => "Play",
                                                     'key_id'  => 6],
                                                 [
                                                     'command' => "Reverse",
                                                     'key_id'  => 7],
                                                 [
                                                     'command' => "Forward",
                                                     'key_id'  => 8],
                                                 [
                                                     'command' => "Search",
                                                     'key_id'  => 9],
                                                 [
                                                     'command' => "Info",
                                                     'key_id'  => 10],
                                                 [
                                                     'command' => "Home",
                                                     'key_id'  => 11],
                                                 [
                                                     'command' => "Instant Replay",
                                                     'key_id'  => 12]]]]]]
                    );
                }
            }
        }
        return $form;
    }


    protected function CreateActivityProperties()
    {
        $harmonyhubs = $this->GetHarmonyHubs();
        foreach ($harmonyhubs as $harmonyhub) {
            $activities = $this->GetHubActivities($harmonyhub);
            foreach ($activities as $key => $activity) {
                $this->RegisterPropertyString('rokukeys_' . $harmonyhub . '_' . abs($activity), '[]');
            }
        }
    }

    protected function GetListName($HarmonyHubObjID, $HarmonyHubActivity)
    {
        $name = 'rokukeys_' . $HarmonyHubObjID . '_' . abs($HarmonyHubActivity);
        return $name;
    }

    /***********************************************************
     * Configuration Form
     ***********************************************************/

    /**
     * build configuration form
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
                'status'   => $this->FormStatus()]
        );
    }

    /**
     * return form configurations on configuration step
     *
     * @return array
     */
    protected function FormHead()
    {
        $form = [
            [
                'type'  => 'Label',
                'label' => 'Roku Emulator IP-Symcon']];

        $harmonyhubs = $this->GetHarmonyHubs();
        $number_hubs = count($harmonyhubs);
        if ($number_hubs == 0) {
            $form = array_merge_recursive(
                $form, [
                         [
                             'type'  => 'Label',
                             'label' => 'No hub found, please configure harmony hub first']]
            );
        } else {
            $form = array_merge_recursive(
                $form, [
                         [
                             'type'  => 'Label',
                             'label' => 'Please select the Harmony Hub for configuration:'],
                         [
                             'name'    => 'HarmonyHubObjID',
                             'type'    => 'Select',
                             'caption' => 'Harmony Hub',
                             'options' => $this->GetHarmonyHubList()]]
            );
        }


        $HarmonyHubObjID = $this->ReadPropertyInteger("HarmonyHubObjID");
        if ($HarmonyHubObjID > 0) {
            $form = array_merge_recursive(
                $form, [
                         [
                             'type'  => 'Label',
                             'label' => 'configure activities']]
            );
            $form = $this->GetHubActivitiesExpansionPanels($HarmonyHubObjID, $form);
        }
        return $form;
    }


    /**
     * return form actions by token
     *
     * @return array
     */
    protected function FormActions()
    {
        $form = [];

        return $form;
    }

    /**
     * return from status
     *
     * @return array
     */
    protected function FormStatus()
    {
        $form = [
            [
                'code'    => 101,
                'icon'    => 'inactive',
                'caption' => 'Creating instance.'],
            [
                'code'    => 102,
                'icon'    => 'active',
                'caption' => 'Roku emulator device created.'],
            [
                'code'    => 104,
                'icon'    => 'inactive',
                'caption' => 'interface closed.'],
            [
                'code'    => 201,
                'icon'    => 'inactive',
                'caption' => 'Please follow the instructions.'],
            [
                'code'    => 202,
                'icon'    => 'error',
                'caption' => 'Device code must not be empty.'],
            [
                'code'    => 203,
                'icon'    => 'error',
                'caption' => 'Device code has not the correct lenght.'],
            [
                'code'    => 204,
                'icon'    => 'error',
                'caption' => 'no Harmony Hub selected.']];

        return $form;
    }

    /***********************************************************
     * Helper methods
     ***********************************************************/

    /**
     * send debug log
     *
     * @param string $notification
     * @param string $message
     * @param int    $format 0 = Text, 1 = Hex
     */
    private function _debug(string $notification = null, string $message = null, $format = 0)
    {
        $this->SendDebug($notification, $message, $format);
    }

    /**
     * return incremented position
     *
     * @return int
     */
    private function _getPosition()
    {
        $this->position++;
        return $this->position;
    }

    protected function GetIPSVersion()
    {
        $ipsversion = floatval(IPS_GetKernelVersion());
        if ($ipsversion < 4.1) // 4.0
        {
            $ipsversion = 0;
        } elseif ($ipsversion >= 4.1 && $ipsversion < 4.2) // 4.1
        {
            $ipsversion = 1;
        } elseif ($ipsversion >= 4.2 && $ipsversion < 4.3) // 4.2
        {
            $ipsversion = 2;
        } elseif ($ipsversion >= 4.3 && $ipsversion < 4.4) // 4.3
        {
            $ipsversion = 3;
        } elseif ($ipsversion >= 4.4 && $ipsversion < 5) // 4.4
        {
            $ipsversion = 4;
        } else   // 5
        {
            $ipsversion = 5;
        }

        return $ipsversion;
    }

    /***********************************************************
     * Migrations
     ***********************************************************/

    /**
     * Polyfill for IP-Symcon 4.4 and older
     *
     * @param $Ident
     * @param $Value
     */
    protected function SetValue($Ident, $Value)
    {
        if (IPS_GetKernelVersion() >= 5) {
            parent::SetValue($Ident, $Value);
        } else if ($id = @$this->GetIDForIdent($Ident)) {
            SetValue($id, $Value);
        }
    }
}

?>
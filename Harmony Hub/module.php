<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
// Modul für Harmony Hub
// Grundlage bilden die Scripte von zapp aus
// https://www.symcon.de/forum/threads/22682-Logitech-Harmony-Ultimate-Smart-Control-Hub-library?highlight=harmony
class HarmonyHub extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        // ClientSocket benötigt
        $this->RequireParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}'); // Logitech Harmony Hub IO

        $this->RegisterPropertyString('Email', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyBoolean('HarmonyVars', false);
        $this->RegisterPropertyBoolean('HarmonyScript', false);
        $this->RegisterPropertyBoolean('Alexa', false);
        $this->RegisterTimer('HarmonyHubSocketTimer', 0, 'HarmonyHub_UpdateSocket(' . $this->InstanceID . ');');
        $this->RegisterAttributeString('HarmonySessionToken', '');
        $this->RegisterAttributeString('HarmonyUserAuthToken', '');
        $this->RegisterAttributeBoolean('Configlock', true);
        $this->RegisterAttributeBoolean('HarmonyInSession', true);
        $this->RegisterAttributeString('HarmonyConfig', '');
        $this->RegisterAttributeInteger('HarmonyConfigTimestamp', 0);
        $this->RegisterAttributeString('HarmonyBuffer', '');
        $this->RegisterAttributeBoolean('HarmonyReachable', true);
        $this->SetBuffer('BufferIN', '');
        $this->SetBuffer('ConfigComplete', '0');
        $this->SetMultiBuffer('HarmonyBufferIN', '');

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

        $this->RegisterVariableString('HarmonyIdentity', 'Identity', '', 7); //uuid
        $this->RegisterVariableString('FirmwareVersion', 'Firmware Version', '', 10);
        $this->RegisterVariableString('HarmonyHubName', 'Harmony Hub Name', '', 11);
        IPS_SetHidden($this->GetIDForIdent('HarmonyIdentity'), true);
        $this->ValidateConfiguration();
        $this->SetCyclicTimerInterval();
    }

    /**
     * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
     * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:.
     */
    protected $lockgetConfig = false;

    private function ValidateConfiguration()
    {
        $ip       = $this->GetParentIP();
        $email    = $this->ReadPropertyString('Email');
        $password = $this->ReadPropertyString('Password');

        //IP prüfen
        if (!filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $this->SendDebug('Harmony Hub', 'IP adress ok', 0);
        } else {
            $this->SetStatus(203); //IP Adresse ist ungültig
        }

        //Email und Passwort prüfen
        if ($email == '' || $password == '') {
            $this->SetStatus(205); //Felder dürfen nicht leer sein
        } elseif ($email !== '' && $password !== '' && (!filter_var($ip, FILTER_VALIDATE_IP) === false)) {
            $userauthtoken = $this->ReadAttributeString('HarmonyUserAuthToken');
            if ($userauthtoken == '') {
                $this->RegisterUser($email, $password);
            }
        }
        // Status Aktiv
        $this->SetStatus(102);
    }

    protected function SetCyclicTimerInterval()
    {
        $this->SetTimerInterval('HarmonyHubSocketTimer', 40000);
    }

    public function HarmonyReachable(bool $reachable)
    {
        if ($reachable) {
            $this->WriteAttributeBoolean('HarmonyReachable', true);
            $this->SetTimerInterval('HarmonyHubSocketTimer', 40000);
        } else {
            $this->WriteAttributeBoolean('HarmonyReachable', false);
            $this->SetTimerInterval('HarmonyHubSocketTimer', 0);
        }
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

    protected function configFilePath()
    {
        $IPSDir         = IPS_GetKernelDir();
        $HarmonyDir     = 'webfront/user/Harmony_Config.txt';
        $configFilePath = $IPSDir . $HarmonyDir;

        return $configFilePath;
    }

    public function UpdateSocket()
    {
        $IOHarmonyHub = $this->GetParent();
        if ($IOHarmonyHub > 0) {
            IPS_ApplyChanges($IOHarmonyHub);
        }
        //$this->getDiscoveryInfo();
        $this->WriteAttributeBoolean('HarmonyInSession', false);
    }

    private function GetParentIP()
    {
        $host     = '';
        $ParentID = $this->GetParent();
        if ($ParentID > 0) {
            $host = IPS_GetProperty($ParentID, 'Host');
            // @IPS_SetName($ParentID, "Logitech Harmony Hub IO Socket (" . $this->ReadPropertyString('Host') . ")");
        } else {
            $this->SendDebug('Harmony Hub', 'Could not find IO. Please connect IO to Splitter', 0);
            $this->SetStatus(207);
        }

        return $host;
    }

    private function GetHarmonyConfigTimestamp()
    {
        $HarmonyConfigTimestamp = $this->ReadAttributeInteger('HarmonyConfigTimestamp');

        return $HarmonyConfigTimestamp;
    }

    //Profile zuweisen und Geräte anlegen
    public function SetupHarmony()
    {
        //Konfig prüfen
        $HarmonyConfig = $this->ReadAttributeString('HarmonyConfig');
        if ($HarmonyConfig == '') {
            $timestamp = time();
            $this->getConfig();
            $i = 0;
            do {
                IPS_Sleep(10);
                // $updatetimestamp = $this->GetBuffer("ConfigComplete");
                $updatetimestamp = $this->ReadAttributeInteger('HarmonyConfigTimestamp');

                //echo $i."\n";
                $i++;
            } while ($updatetimestamp <= $timestamp);
        }

        // $this->SetBuffer("ConfigComplete", "0");
        //Activity Profil anlegen
        $this->SetHarmonyActivityProfile();

        //Harmony Firmware und Name auslesen
        $this->getDiscoveryInfo();
    }

    protected function SetupActivityScripts($HubCategoryID, $hubname)
    {
        $hubip      = $this->GetParentIP();
        $hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline.
        $activities = $this->GetAvailableAcitivities();
        //Prüfen ob Kategorie schon existiert
        $MainCatID = @IPS_GetObjectIDByIdent('LogitechActivitiesScripts_' . $hubipident, $HubCategoryID);
        if ($MainCatID === false) {
            $MainCatID = IPS_CreateCategory();
            IPS_SetName($MainCatID, $hubname . ' Aktivitäten');
            IPS_SetInfo($MainCatID, $hubname . ' Aktivitäten');
            //IPS_SetIcon($NeueInstance, $Quellobjekt['ObjectIcon']);
            //IPS_SetPosition($NeueInstance, $Quellobjekt['ObjectPosition']);
            //IPS_SetHidden($NeueInstance, $Quellobjekt['ObjectIsHidden']);
            IPS_SetIdent($MainCatID, 'LogitechActivitiesScripts_' . $hubipident);
            IPS_SetParent($MainCatID, $HubCategoryID);
        }
        $ScriptID = false;
        foreach ($activities as $activityname => $activity) {
            //Prüfen ob Script schon existiert
            $ScriptID = $this->CreateActivityScript($activityname, $MainCatID, $hubip, $activity);
        }

        return $ScriptID;
    }

    protected function CreateActivityScript($Scriptname, $MainCatID, $hubip, $activity)
    {
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
		HarmonyHub_startActivity(' . $this->InstanceID . ', ' . $activity . ');
    Case "Execute": 
        HarmonyHub_startActivity(' . $this->InstanceID . ', ' . $activity . ');
    Case "TimerEvent": 
        break; 

    Case "Variable": 
    Case "VoiceControl": // Schalten durch den Alexa SmartHomeSkill
           
    if ($_IPS[\'VALUE\'] == True) 
        { 
            // einschalten
            HarmonyHub_startActivity(' . $this->InstanceID . ', ' . $activity . ');   
        } 
    else 
        { 
            //ausschalten
            HarmonyHub_startActivity(' . $this->InstanceID . ', -1);
        } 
       break;
    Case "WebFront":        // Zum schalten im Webfront 
        HarmonyHub_startActivity(' . $this->InstanceID . ', ' . $activity . ');   
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
            '=)',];
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
            '',];

        $str = str_replace($search, $replace, $str);
        $str = str_replace(' ', '_', $str); // Replaces all spaces with underline.
        $how = '_';
        //$str = strtolower(preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str));
        $str = preg_replace('/[^a-zA-Z0-9]+/', trim($how), $str);

        return $str;
    }

    protected function SetHarmonyActivityProfile()
    {
        $hubip      = $this->GetParentIP();
        $hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline.
        $config     = $this->GetHarmonyConfigJSON();
        if (isset($config['device'])) {
            $activities[]         = $config['activity'];
            $devices[]            = $config['device'];
            $ProfileAssActivities = [];
            $assid                = 1;
            foreach ($activities as $activitieslist) {
                foreach ($activitieslist as $activity) {
                    $label            = $activity['label'];
                    $suggestedDisplay = $activity['suggestedDisplay'];
                    $this->SendDebug('Harmony Activity', 'suggested display ' . $suggestedDisplay, 0);
                    $id                      = $activity['id'];
                    $activityTypeDisplayName = $activity['activityTypeDisplayName'];
                    $this->SendDebug('Harmony Activity', 'activity type display name ' . $activityTypeDisplayName, 0);
                    $controlGroup = $activity['controlGroup'];
                    $this->SendDebug('Harmony Activity', 'control group ' . json_encode($controlGroup), 0);
                    if (isset($activity['isTuningDefault'])) {
                        $isTuningDefault = $activity['isTuningDefault'];
                        $this->SendDebug('Harmony Activity', 'is tuning default ' . json_encode($isTuningDefault), 0);
                    }
                    $sequences = $activity['sequences'];
                    $this->SendDebug('Harmony Activity', 'sequences ' . json_encode($sequences), 0);
                    if (isset($activity['activityOrder'])) {
                        $activityOrder = $activity['activityOrder'];
                        $this->SendDebug('Harmony Activity', 'activity order ' . json_encode($activityOrder), 0);
                    }
                    $fixit = $activity['fixit'];
                    $this->SendDebug('Harmony Activity', 'fixit ' . json_encode($fixit), 0);
                    $type = $activity['type'];
                    $this->SendDebug('Harmony Activity', 'type ' . $type, 0);
                    $icon = $activity['icon'];
                    $this->SendDebug('Harmony Activity', 'icon ' . $icon, 0);
                    if (isset($activity['baseImageUri'])) {
                        $baseImageUri = $activity['baseImageUri'];
                        $this->SendDebug('Harmony Activity', 'base image uri ' . $baseImageUri, 0);
                    }
                    if ($label == 'PowerOff') {
                        $ProfileAssActivities[$assid] = [$id, 'Power Off', '', 0xFA5858];
                    } else {
                        $ProfileAssActivities[$assid] = [$id, utf8_decode($label), '', -1];
                    }
                    $assid++;
                }
            }
            $profilemax = count($ProfileAssActivities);
            $this->RegisterProfileIntegerHarmonyAss(
                'LogitechHarmony.Activity' . $hubipident, 'Popcorn', '', '', -1, ($profilemax + 1), 0, 0, $ProfileAssActivities
            );
            $this->RegisterVariableInteger('HarmonyActivity', 'Harmony Activity', 'LogitechHarmony.Activity' . $hubipident, 12);
            $this->EnableAction('HarmonyActivity');
            SetValueInteger($this->GetIDForIdent('HarmonyActivity'), -1);
        }
    }

    //################# DUMMYS / WOARKAROUNDS - protected

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);

        return $instance['ConnectionID'];
    }

    // Testfunktion Data an Child weitergeben
    public function SendTest(string $Text)
    {
        // Weiterleitung zu allen Gerät-/Device-Instanzen
        $this->SendDebug('Logitech Harmony Hub', 'Send :' . $Text, 0);
        $this->SendDataToChildren(
            json_encode(['DataID' => '{7924862A-0EEA-46B9-B431-97A3108BA380}', 'Buffer' => $Text])
        ); //Harmony Splitter Interface GUI
    }

    // Data an Child weitergeben
    public function ReceiveData($JSONString)
    {

        // Empfangene Daten vom I/O
        $data   = json_decode($JSONString);
        $dataio = utf8_decode($data->Buffer);
        //$dataiomessage = json_encode($dataio);
        $this->SendDebug('Logitech Harmony Hub IO In', $dataio, 0);

        //Daten müssen erst zusammengesetzt werden
        $this->BufferIn($data->Buffer);

        // Weiterleitung zu allen Gerät-/Device-Instanzen
        //$this->SendDataToChildren(json_encode(Array("DataID" => "{7924862A-0EEA-46B9-B431-97A3108BA380}", "Buffer" => $data->Buffer))); //Harmony Splitter Interface GUI
    }

    //Buffer
    protected $BufferHarmonyIn;

    protected function BufferIn($data)
    {
        // bereits im Puffer der Instanz vorhandene Daten in $databuffer kopieren
        $databuffer = $this->GetBufferIN();

        // neu empfangene Daten an $databuffer anhängen
        $databuffer .= $data;
        $this->SendDebug('Logitech Harmony Hub Buffer In', $databuffer, 0);

        // auf Inhalt prüfen und nach Typ auswerten
        preg_match('/^<[a-z]*/', $databuffer, $tag);
        $tag          = str_replace('<', '', $tag);
        $tag          = $tag[0];
        $bufferdelete = false;
        $configlock   = $this->ReadAttributeBoolean('Configlock');

        if (strpos($databuffer, '</iq>') && ($configlock == true)) {
            //Daten komplett, weiterreichen
            $this->SendDebug('Logitech Harmony Hub Config', $databuffer, 0);
            $this->WriteAttributeString('HarmonyConfig', $databuffer);
            $timestamp = time();
            $this->WriteAttributeInteger('HarmonyConfigTimestamp', $timestamp);
            $this->DeleteBuffer();
            $bufferdelete = true;
            //$this->lockgetConfig = false;
            $this->WriteAttributeBoolean('Configlock', false);
            //Daten zur Auswertung übergeben
            $this->ReadPayload($databuffer, $tag);
        }

        if (strpos($databuffer, '</stream:stream>')) {
            $this->WriteAttributeBoolean('HarmonyInSession', false);
            $this->SendDebug('Logitech Harmony Hub', 'In Session false', 0);
        }

        //Line Feed trennen
        $payload = explode('<LF>', $databuffer);
        foreach ($payload as $content) {
            if (strpos($content, 'harmony.engine?startActivityFinished')) { //Message wenn Activity abgeschlossen
                $this->DeleteBuffer();
                $bufferdelete = true;
                //CDATA auslesen
                $content = $this->XMPP_getPayload($content);
                // $type = $content['type']; // startActivityFinished
                $CurrentActivity = intval($content['activityId']);
                $activities      = $this->GetAvailableAcitivities();
                $ActivityName    = array_search($CurrentActivity, $activities);
                IPS_LogMessage('Logitech Harmony Hub', 'Activity ' . $ActivityName . ' finished');
                SetValueInteger($this->GetIDForIdent('HarmonyActivity'), $CurrentActivity);
            } elseif (strpos($content, 'connect.stateDigest?notify')) { // Notify Message
                $this->DeleteBuffer();
                $bufferdelete = true;
                //CDATA auslesen
                $content = $this->XMPP_getPayload($content);
                // $type = $content['type']; // notify
                //  activityStatus	0 = Hub is off, 1 = Activity is starting, 2 = Activity is started, 3 = Hub is turning off
                if (isset($content['activityId'])) {
                    $CurrentActivity = intval($content['activityId']);
                    $activityStatus  = intval($content['activityStatus']);
                    $activities      = $this->GetAvailableAcitivities();
                    $ActivityName    = array_search($CurrentActivity, $activities);
                    if ($activityStatus == 2) {
                        IPS_LogMessage('Logitech Harmony Hub', 'Activity ' . $ActivityName . ' is started');
                        SetValueInteger($this->GetIDForIdent('HarmonyActivity'), $CurrentActivity);
                    } elseif ($activityStatus == 1) {
                        IPS_LogMessage('Logitech Harmony Hub', 'Activity ' . $ActivityName . ' is starting');
                    } elseif ($activityStatus == 0) {
                        IPS_LogMessage('Logitech Harmony Hub', 'Hub Status is off');
                    }
                }
            }
        }

        if (strpos($databuffer, 'stream:stream')) {
            $this->DeleteBuffer();
            //Daten zur Auswertung übergeben
            $this->ReadPayload($databuffer, 'stream');
        } elseif ($tag == 'success') {
            $this->DeleteBuffer();
            //Daten zur Auswertung übergeben
            $this->ReadPayload($databuffer, $tag);
        } elseif ($tag == 'failure') {
            $this->DeleteBuffer();
            //Daten zur Auswertung übergeben
            $this->ReadPayload($databuffer, $tag);
        } elseif (strpos($databuffer, '</iq>')) {
            $this->DeleteBuffer();
            //Daten zur Auswertung übergeben
            $this->ReadPayload($databuffer, $tag);
        } elseif ($bufferdelete == false) {
            $this->WriteBuffer($databuffer);
        }
    }

    private function DeleteBuffer()
    {
        $this->SetMultiBuffer('HarmonyBufferIN', '');
    }

    private function WriteBuffer($databuffer)
    {
        // Inhalt von $databuffer im Puffer speichern
        $this->SetMultiBuffer('HarmonyBufferIN', $databuffer);
    }

    private function GetBufferIN()
    {
        // bereits im Puffer der Instanz vorhandene Daten in $databuffer kopieren
        $databuffer = $this->GetMultiBuffer('HarmonyBufferIN');

        return $databuffer;
    }

    public function ReadHarmonyConfig()
    {
        $harmonyconfig = $this->ReadAttributeString('HarmonyConfig');

        return $harmonyconfig;
    }

    //read incoming data
    protected function ReadPayload($payload, $tag)
    {
        switch ($tag) {
            case 'iq':
                $this->processIQ($payload); // Step 5 - Received IQ message for bind response (see function for further steps)
                break;
            case 'message':
                $content = $this->XMPP_getPayload($payload);
                $type    = $content['type'];
                if ($type == 'short') { //Message bei Tastendruck

                } elseif ($type == 'startActivityFinished') { // Message bei Activity
                    $CurrentActivity = intval($content['activityId']);
                    $activities      = $this->GetAvailableAcitivities();
                    $ActivityName    = array_search($CurrentActivity, $activities);
                    $this->SendDebug('Logitech Harmony Hub', 'Activity  ' . $ActivityName . ' started und finished', 0);
                    SetValueInteger($this->GetIDForIdent('HarmonyActivity'), $CurrentActivity);
                } //  activityStatus	0 = Hub is off, 1 = Activity is starting, 2 = Activity is started, 3 = Hub is turning off
                elseif ($type == 'notify') { // Notify z.B. Hue oder Activity
                    if (isset($content['activityId'])) {
                        $CurrentActivity = intval($content['activityId']);
                        $activityStatus  = intval($content['activityStatus']);
                        $activities      = $this->GetAvailableAcitivities();
                        $ActivityName    = array_search($CurrentActivity, $activities);
                        SetValueInteger($this->GetIDForIdent('HarmonyActivity'), $CurrentActivity);
                        if ($activityStatus == 2) {
                            $this->SendDebug('Logitech Harmony Hub', 'Activity ' . $ActivityName . ' is started', 0);
                        } elseif ($activityStatus == 1) {
                            $this->SendDebug('Logitech Harmony Hub', 'Activity ' . $ActivityName . ' is starting', 0);
                        } elseif ($activityStatus == 0) {
                            $this->SendDebug('Logitech Harmony Hub', 'Hub Status is off', 0);
                        }
                    }
                }
                break;
            case 'stream':
                $this->SendDebug('Logitech Harmony Hub', 'RECV: STREAM Confirmation received', 0);
                preg_match('/id=\'([a-zA-Z0-9-_]+)\'\s/', $payload, $id);
                $this->SendDebug('Logitech Harmony Hub', 'HARMONY XMPP -> id: ' . $id[1], 0);
                if (!strpos($payload, '<bind')) {
                    // Step 2 - Received stream response: mechanism feature advertisement. We authenticate.
                    $this->processAuth();
                } else {
                    // Step 4 - Received stream response: resource binding feature advertisement
                    $resource = $this->HARMONY_CLIENT_RESOURCE;
                    $this->XMPP_Bind($resource);   // Now bind a resource (defined in library)
                }
                //print_r($xml);
                break;
            case 'success':
                $this->SendDebug('Logitech Harmony Hub', 'RECV: Authentication SUCCESS', 0);

                //$this->XMPP_OpenStream(); // Step 3 - Open  new stream for binding
                break;
            case 'failure':
                $this->SendDebug('Logitech Harmony Hub', 'RECV: Authentication FAILED', 0);
                break;
            default:
                // We suppose that if there is no XMPP tag, we received a contination of an OA message and have to aggregate.
                $this->processIQ($payload);
                break;
        }
    }

    /**
     * Internal XMPP processing function.
     *
     * @param $xml
     */
    protected function processIQ($xml)
    {
        // $parentID = $this->GetParent();

        preg_match('/<([a-z:]+)\s.*<([a-z:]+)\s/', $xml, $tag);
        if (isset($tag[2])) { // to avoid message of "undefined offset"
            if ($tag[2] == 'oa') {
                // $responseacitvity = @strpos($xml, "getCurrentActivity");
                if (strpos($xml, 'getCurrentActivity')) { //Response Activity
                    preg_match('/<!\[CDATA\[\s*(.*)\s*/', $xml, $cdata); // <!\[(CDATA)\[\s*(.*?)\s*>
                    $data = $cdata[1];
                    if (!strpos($data, 'result=')) {
                        $posend   = strpos($data, ']]');
                        $activity = substr($data, 7, ($posend - 7));
                        $this->SendDebug('Logitech Harmony Hub', 'HarmonyActivity: ' . $activity, 0);
                        SetValue($this->GetIDForIdent('HarmonyActivity'), $activity);
                    }
                } elseif (strpos($xml, 'discoveryinfo')) { //Response discoveryinfo
                    preg_match('/<!\[CDATA\[\s*(.*)\s*/', $xml, $cdata); // <!\[(CDATA)\[\s*(.*?)\s*>
                    $jsonrawstring = $cdata[1];
                    if (strpos($jsonrawstring, 'current_fw_version')) {
                        $jsonrawlength     = strlen($jsonrawstring);
                        $jsonend           = strripos($jsonrawstring, ']]>');
                        $jsondiscoveryinfo = substr($jsonrawstring, 0, ($jsonend - $jsonrawlength));
                        $discoveryinfo     = json_decode($jsondiscoveryinfo, true);
                        // Auslesen Firmware und Name
                        $FirmwareVersion = $discoveryinfo['current_fw_version'];
                        $HarmonyHubName  = $discoveryinfo['friendlyName'];
                        // $hubProfiles = $discoveryinfo['hubProfiles'];
                        $uuid = $discoveryinfo['uuid'];
                        // $remoteId = $discoveryinfo['remoteId'];
                        $this->SendDebug('Logitech Harmony Hub', 'FirmwareVersion: ' . $FirmwareVersion, 0);
                        SetValue($this->GetIDForIdent('FirmwareVersion'), $FirmwareVersion);
                        $this->SendDebug('Logitech Harmony Hub', 'HarmonyHubName: ' . $HarmonyHubName, 0);
                        SetValue($this->GetIDForIdent('HarmonyHubName'), $HarmonyHubName);
                        $this->SendDebug('Logitech Harmony Hub', 'HarmonyIdentity: ' . $uuid, 0);
                        SetValue($this->GetIDForIdent('HarmonyIdentity'), $uuid);
                    }
                } elseif (strpos($xml, 'identity')) { // We got an identity response message
                    $content = $this->XMPP_getPayload($xml);
                    $this->SendDebug(
                        'Logitech Harmony Hub',
                        'Hub Name: ' . $content['friendlyName'] . ', identity = ' . $content['identity'] . ' - status = ' . $content['status'], 0
                    ); // Info/Query Stanza
                    $identityVariableId = $this->GetIDForIdent('HarmonyIdentity');
                    // Store Identity in String variable
                    SetValue($identityVariableId, $content['identity']);

                    // STEP 7 - If we did the guest authentication (Session Auth == true), we start again now for the session authentication
                    /*
                    $inSessionVarId = @GetIDForIdent("HarmonyInSession");
                    if ($inSessionVarId === false)
                        {
                        IPS_LogMessage("HARMONY XMPP", "ERROR in processIQ(): Session Auth Variable not found (after Session token received)");
                        }
                    else
                        {
                        if (!GetValue($inSessionVarId))
                            {
                            //init();
                            SetValue($inSessionVarId, true); // We are in the Session Auth Process
                            }
                        }
                    */
                }
            }
            if ($tag[2] == 'bind') { // This is not an OA message, we suppose it is a resource binding or identity request reply
                if (!strpos($xml, '<bind')) {
                    $this->SendDebug('Logitech Harmony Hub', 'RECV: Unknown IQ Stanza', 0); // Info/Query Stanza
                } else { // STEP 5 - Binding Response (Continuation from Harmony_Read Script)
                    preg_match('/<jid>(.*)<\/jid>/', $xml, $jid);
                    $this->SendDebug('Logitech Harmony Hub', 'RECV: IQ Stanza resource binding result - JID: ' . $jid[1], 0);
                    // Replace 2 lines below by a proper function to get the User Auth Token Value (IPS Tools Library)
                    $tokenVariableId = @$this->GetIDForIdent('HarmonyUserAuthToken');
                    if ($tokenVariableId === false) {
                        $this->SendDebug('Logitech Harmony Hub', 'ERROR in processIQ(): User Auth Token not defined (after binding reponse).', 0);
                    } else {
                        $this->SendDebug('Logitech Harmony Hub', 'SEND: Sending Session Request', 0);
                        $this->XMPP_Session(); // Test: Request session
                        IPS_Sleep(200);
                        if (!$this->ReadAttributeBoolean('HarmonyInSession')) { // We request the Session token only if we are authenticated as guest
                            $this->SendDebug('Logitech Harmony Hub', 'SEND: Sending Session Token Request', 0);
                            // $UserAuthToken = GetValue($tokenVariableId);
                            $this->sendSessionTokenRequest();
                            IPS_Sleep(500); // We need to wait to ensure that we receive the identity back from the server
                        }
                    }
                }
            }
        } else { // There is no tag, we continue aggregationg the OA data

            //Konfig Auslesen
            $data = $this->ReadAttributeString('HarmonyConfig');
            str_replace(['\\', '"'], ['', ''], $xml);
            if (!strpos($data, '</oa>')) {
                // $data .= $xml;// continue aggregating until we get the closing OA tag
                if (!strpos($xml, '</oa>')) {
                    $this->SendDebug('Logitech Harmony Hub', 'RECV: Aggregation of CDATA ended.', 0);
                } else {
                    $this->SendDebug('Logitech Harmony Hub', 'RECV: Continuing CDATA aggregation...', 0);
                }
            }
        }
    }

    //################# DATAPOINT RECEIVE FROM CHILD

    // Type String, Declaration can be used when PHP 7 is available
    //public function ForwardData(string $JSONString)
    public function ForwardData($JSONString)
    {
        $this->SendDebug('Forward data', $JSONString, 0);
        // Empfangene Daten von der Device Instanz
        $data     = json_decode($JSONString);
        $datasend = $data->Buffer;
        if (property_exists($datasend, 'Method')) {
            $this->SendDebug('Forward data', 'Method: ' . $datasend->Method, 0);
            if ($datasend->Method == 'GetHarmonyConfigJSON') {
                $devices_json = $this->GetHarmonyConfig();
                $this->SendDebug('Logitech Harmony Hub', 'Get Harmony Config', 0);

                return $devices_json;
            }
            if ($datasend->Method == 'GetHarmonyConfigTimestamp') {
                $GetHarmonyConfigTimestamp = $this->GetHarmonyConfigTimestamp();

                return $GetHarmonyConfigTimestamp;
            }
            if ($datasend->Method == 'GetHubIP') {
                $hubip = $this->GetParentIP();
                $this->SendDebug('Logitech Harmony Hub', 'IP Adress: ' . $hubip, 0);

                return $hubip;
            }
            if ($datasend->Method == 'getConfig') {
                $this->getConfig();
            }
            if ($datasend->Method == 'GetAvailableAcitivities') {
                $currentactivities      = $this->GetAvailableAcitivities();
                $currentactivities_json = json_encode($currentactivities);
                $this->SendDebug('Forward data', 'Send: ' . $currentactivities_json, 0);

                return $currentactivities_json;
            }
        }

        if (property_exists($datasend, 'DeviceID')) {
            $DeviceID        = $datasend->DeviceID;
            $Command         = $datasend->Command;
            $BluetoothDevice = $datasend->BluetoothDevice;
            $commandoutobjid = @$this->GetIDForIdent('CommandOut');
            if ($commandoutobjid > 0) {
                SetValueString($commandoutobjid, 'DeviceID: ' . $DeviceID . ', Command: ' . $Command . ', BluetoothDevice: ' . $BluetoothDevice);
            }
            $this->SendDebug(
                'Logitech Harmony Hub',
                'ForwardData HarmonyHub Splitter: DeviceID: ' . $DeviceID . ', Command: ' . $Command . ', BluetoothDevice: ' . $BluetoothDevice, 0
            );
            $this->sendcommand($DeviceID, $Command, $BluetoothDevice);

            return true;
        }

        return false;
    }

    /**
     * RequestAction.
     *
     * @param string $Ident
     * @param        $Value
     */
    //Type String, Declaration can be used when PHP 7 is available
    //public function RequestAction(string $Ident, $Value)
    public function RequestAction($Ident, $Value)
    {
        if ($Ident == 'HarmonyActivity') {
            $activityID = $Value;
            $this->startActivity($activityID);
        }
    }

    public function Send(string $payload)
    {
        $this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => $payload]));
    }

    public function Ping()
    {
        $iqString = "<iq type='get' id='2320426445' from='guest'>
			<oa xmlns='connect.logitech.com' mime='vnd.logitech.connect/vnd.logitech.pingvnd.logitech.ping'>
			</oa>
			</iq>";
        $this->XMPP_Send($iqString);
    }

    /**
     * Sends a request to swap Auth Token for a Session token to the XMPP Server
     * The returned IQ Message with the Session token is processed by processIQ().
     *
     **/
    public function sendSessionTokenRequest()
    {
        $token       = GetValue($this->GetIDForIdent('HarmonyUserAuthToken'));
        $tokenString = $token . ':name=foo#iOS6.0.1#iPhone'; // "token=".

        $this->XMPP_Send(
            "<iq type='get' id='3174962747' from='guest'><oa xmlns='connect.logitech.com' mime='vnd.logitech.connect/vnd.logitech.pair'>token="
            . $tokenString . '</oa></iq>'
        );
    }

    /**
     * Sends request to get Harmony configuration to XMPP Server
     * The server will return the xml encoded config in a IQ/OA reply.
     *
     **/
    public function getConfig()
    {
        //$this->lockgetConfig = true;
        $this->WriteAttributeBoolean('Configlock', true);
        $this->XMPP_OpenStream();
        $iqString = "<iq type='get' id='2320426445' from='guest'>
		  <oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?config'>
		  </oa>
		</iq>";
        $this->XMPP_Send($iqString);
    }

    /**
     * Opens the stream to XMPP Server.
     *
     **/
    public function XMPP_OpenStream()
    {
        $this->XMPP_Send(
            "<stream:stream to='connect.logitech.com' xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client' xml:lang='en' version='1.0'>"
        ); //  xmlns:xml="http://www.w3.org/XML/1998/namespace"
    }

    /**
     * Closes the stream to XMPP Server.
     *
     **/
    public function XMPP_CloseStream()
    {
        $this->XMPP_Send('</stream:stream>');  // <presence type='unavailable'/>
    }

    /**
     * Sends XMPP message to XMPP server
     * If the socket is closed, adds the command to a queue array for subsequent execution.
     *
     * @param string $payload
     *
     * @return bool true if success
     **/
    protected function XMPP_Send($payload)
    {
        $parentactive          = $this->HasActiveParent();
        $instanceHarmonySocket = $this->GetParent();
        // Open the socket if it is disconnected
        if (!$parentactive && $instanceHarmonySocket > 0) {
            IPS_SetProperty($instanceHarmonySocket, 'Open', true);
            IPS_ApplyChanges($instanceHarmonySocket);
        }
        if ($parentactive) {
            $this->Send($payload);
        } else {
            $this->SendDebug('Logitech Harmony Hub', 'could not send to harmony hub, hub is not active', 0);
        }

        return $parentactive;
    }

    protected $HARMONY_CLIENT_RESOURCE = 'ips';  // gatorade. ?

    // This 'from' value can be retrieved from the messages received by the Server ("to" field). But it does not seem necessary.
    protected $from = 'guest';

    /**
     * Sends Auth request to XMPP server.
     *
     * @param string $user
     * @param string $password
     *
     **/
    protected function XMPP_Auth($user, $password)
    {
        $this->SendDebug('Logitech Harmony Hub', 'Authenticating with ' . $user . ' - ' . $password, 0);
        $pass = base64_encode("\x00" . $user . "\x00" . $password);
        $this->XMPP_Send("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>" . $pass . '</auth>');
    }

    /**
     * Sends Bind request to XMPP server.
     *
     * @param string $resource A resource name
     *
     **/
    protected function XMPP_Bind($resource)
    {
        $this->SendDebug('Logitech Harmony Hub', 'Binding with resource ' . $resource, 0);
        $this->XMPP_Send("<iq type='set' id='bind_2'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>$resource</resource></bind></iq>");
    }

    /**
     * Sends Session request to XMPP server.
     *
     **/
    protected function XMPP_Session()
    {
        $this->SendDebug('Logitech Harmony Hub', 'Sending Session request', 0);
        $this->XMPP_Send("<iq id='bind_3' type='set'><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></iq>");
    }

    /**
     * Extracts CDATA payload from XMPP xml message.
     *
     * @param string $xml
     *
     * @return array CDATA content formatted as 'type': Type of message, 'activityId', 'errorCode', 'errorString'
     *               activityId    ID of the current activity.
     *               activityStatus    0 = Hub is off, 1 = Activity is starting, 2 = Activity is started, 3 = Hub is turning off
     **/
    protected function XMPP_getPayload($xml)
    {
        preg_match('/type="[a-zA-Z\.]+\?(.*)">/', $xml, $type);  // type= "connect.stateDigest?notify"
        if (!empty($type)) {
            if (strpos($type[0], 'notify')) {
                $items['type'] = 'notify';
                if (strpos($type[0], 'connect.stateDigest')) {
                    $items['maintype'] = 'state';
                } elseif (strpos($type[0], 'automation.state')) { // message for HUE etc.
                    $items['maintype'] = 'automation';
                }
            } else {
                $items['type'] = $type[1];
            }
        }

        preg_match('/<!\[(CDATA)\[\s*(.*?)\s*\]\]>/', $xml, $cdata); // gibt CDATA aus
        preg_match('/^{(.*?)}/', $cdata[2], $cdatat); // Prüft auf {}
        $nojson = empty($cdatat);
        if (!$nojson) {
            $content = json_decode($cdata[2], true);
            foreach ($content as $key => $item) {
                $items[$key] = $item;
            }
        } else {
            $content = explode(':', $cdata[2]);

            foreach ($content as $item) {
                $itemParts            = explode('=', $item);
                $items[$itemParts[0]] = $itemParts[1];
            }
        }

        return $items;
    }

    /**
     * Sends a request to the XMPP Server to get the current Activity ID.
     *
     **/
    public function getCurrentActivity()
    {
        $iqString = "<iq type='get' id='2320426445' from='" . $this->from . "'>
		  <oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?getCurrentActivity'>
		  </oa>
		</iq>";
        $this->XMPP_Send($iqString);
    }

    /**
     * Sends a request to the XMPP Server to get Infos (Firmware Version, Hub Name).
     *
     **/
    public function getDiscoveryInfo()
    {
        $iqString = "<iq type='get' id='2320426445' from='" . $this->from . "'>
		<oa xmlns='connect.logitech.com' mime='connect.discoveryinfo?get'>format=json</oa>	
		</iq>";
        $this->XMPP_Send($iqString);
    }

    /**
     * Sends request to send an IR command to XMPP Server
     * Device ID and Command name have to be retrieved from the config. No error check is made.
     *
     * @param $DeviceID
     * @param $Command
     * @param $BluetoothDevice
     *
     * @internal param DeviceID DeviceID as retrieved from the Harmony config
     * @internal param Command Command as retrieved from teh Harmony config
     */
    protected function sendcommand($DeviceID, $Command, $BluetoothDevice)
    {
        if ($this->ReadAttributeBoolean('HarmonyInSession')) {
            if ($BluetoothDevice == true) {
                $this->sendcommandAction($DeviceID, $Command);
            } else {
                $iqString =
                    "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$DeviceID\",\"command\"::\"$Command\"}:status=press:timestamp=0</oa></iq>";
                $this->XMPP_Send($iqString);
                IPS_Sleep(100);
                $iqString =
                    "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$DeviceID\",\"command\"::\"$Command\"}:status=release:timestamp=100</oa></iq>";
                $this->XMPP_Send($iqString);
            }
        } else { // Open Stream
            $this->XMPP_OpenStream();
            IPS_Sleep(500); // wait for auth success
            if ($BluetoothDevice == true) {
                $this->sendcommandAction($DeviceID, $Command);
            } else {
                $iqString =
                    "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$DeviceID\",\"command\"::\"$Command\"}:status=press:timestamp=0</oa></iq>";
                $this->XMPP_Send($iqString);
                IPS_Sleep(100);
                $iqString =
                    "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$DeviceID\",\"command\"::\"$Command\"}:status=release:timestamp=100</oa></iq>";
                $this->XMPP_Send($iqString);
            }
        }
    }

    /**
     * Sends request to send an IR command to XMPP Server
     * Device ID and Command name have to be retrieved from the config. No error check is made.
     *
     * @param $deviceID
     * @param $command
     *
     * @internal param DeviceID DeviceID as retrieved from the Harmony config
     * @internal param Command Command as retrieved from teh Harmony config
     *
     **/
    protected function sendcommandAction($deviceID, $command)
    {
        $identityVariableId = $this->GetIDForIdent('HarmonyIdentity');
        $identity           = GetValue($identityVariableId);
        if ($identity == '') {
            $this->sendSessionTokenRequest();
            IPS_Sleep(500);
            $identity = GetValue($identityVariableId);
        }

        $iqString = "<iq id='7725179067' type='render' from='" . $identity
                    . "'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>status=press:action={\"command\"::\"$command\",\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\"}:timestamp=0</oa></iq>";
        $this->SendDebug('Logitech Harmony Hub', 'Sending: ' . $iqString, 0);
        $this->XMPP_Send($iqString);
        IPS_Sleep(100);
        $iqString = "<iq id='7725179067' type='render' from='" . $identity
                    . "'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>status=release:action={\"command\"::\"$command\",\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\"}:timestamp=100</oa></iq>";
        $this->SendDebug('Logitech Harmony Hub', 'Sending: ' . $iqString, 0);
        $this->XMPP_Send($iqString);
    }

    /**
     * Sends request to send an IR command to XMPP Server
     * Device ID and Command name have to be retrieved from the config. No error check is made.
     *
     * @param $deviceID
     * @param $command
     *
     * @internal param DeviceID DeviceID as retrieved from the Harmony config
     * @internal param Command Command as retrieved from teh Harmony config
     **/
    protected function sendcommandRender($deviceID, $command)
    {
        $identityVariableId = $this->GetIDForIdent('HarmonyIdentity');
        $identity           = GetValue($identityVariableId);
        if ($identity == '') {
            $this->sendSessionTokenRequest();
            IPS_Sleep(500);
            $identity = GetValue($identityVariableId);
        }
        $iqString = "<iq id='4191874917' type='render' from='" . $identity
                    . "'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\",\"command\"::\"$command\"}:status=press</oa></iq>";
        $this->SendDebug('Logitech Harmony Hub', 'Sending: ' . $iqString, 0);
        $this->XMPP_Send($iqString);
    }

    /**
     * Sends request to send an IR command to start a given activity to the XMPP Server
     * The Activity ID has to be retrieved from the config. No error check is made.
     *
     * @param $activityID
     *
     * @internal param $activityID ID as retrieved from the Harmony config
     *
     * timestamp A unix timestamp so the hub can identify the order of incoming activity triggering request
     **/
    public function startActivity(int $activityID)
    {
        //$timestamp = time();
        //$iqString = "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?startactivity'>activityId=".$activityID.":timestamp=".$timestamp."</oa></iq>";
        $iqString =
            "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?startactivity'>activityId="
            . $activityID . ':timestamp=0</oa></iq>';
        $this->SendDebug('Logitech Harmony Hub', 'Sending: ' . $iqString, 0);
        $this->XMPP_Send($iqString);
    }

    /**
     * Internal Authentication Processing function.
     **/
    public function processAuth()
    {
        // If we have been in a Sesssion Auth, we authenticate as guest to get the identity
        $identity = GetValue($this->GetIDForIdent('HarmonyIdentity'));
        if ($this->ReadAttributeBoolean('HarmonyInSession')) { // Stream open auth ok
            //XMPP_Auth('guest@x.com', 'guest');
            //$this->XMPP_Auth('guest@connect.logitech.com', 'gatorade.'); // Authenticate as guest
            $this->XMPP_Auth($identity . '@connect.logitech.com', $identity); // Authenticate as session
            //SetValue($inSessionVarId, false);
            $this->WriteAttributeBoolean('HarmonyInSession', true);
        } else { // Stream open no auth
            if ($identity == '') {
                $this->sendSessionTokenRequest();
            } else {
                $this->XMPP_Auth($identity . '@connect.logitech.com', $identity); // Authenticate as session
            }
            $this->WriteAttributeBoolean('HarmonyInSession', true);
        }
    }

    //UserAuthToken abholen falls nicht vorhanden
    public function RegisterUser(string $email, string $password)
    {
        $LOGITECH_AUTH_URL = 'https://svcs.myharmony.com/CompositeSecurityServices/Security.svc/json/GetUserAuthToken';
        $timeout           = 30;

        $credentials = [
            'email'    => $email,
            'password' => $password,];
        $json_string = json_encode($credentials); // '{'.$cmd.'}';

        $ch = curl_init($LOGITECH_AUTH_URL);

        $options = [
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_VERBOSE        => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-type: application/json; charset=utf-8'],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json_string,];

        // Setting curl options
        curl_setopt_array($ch, $options);
        // Getting results
        $json_result = curl_exec($ch);
        if ($json_result === false) {
            die(curl_error($ch));
        }
        $result = json_decode($json_result);

        if (!curl_errno($ch)) {
            // No Error
            if (empty($result->GetUserAuthTokenResult)) {
                $UserAuthToken = '';
                $this->SendDebug('Logitech Harmony Hub', 'No token transmitted', 0);
            } else {
                $UserAuthToken = $result->GetUserAuthTokenResult;
            }
            $this->WriteAttributeString('HarmonyUserAuthToken', $UserAuthToken);
        } else {
            $this->SendDebug('Logitech Harmony Hub', 'Error: Authentification failed', 0);
            $this->SendDebug('Logitech Harmony Hub', 'Error: Curl failed - ' . curl_error($ch), 0);
        }

        //print_r ($result);
        return $json_result;
    }

    //DeviceIDs auslesen
    public function GetHarmonyDeviceIDs()
    {
        $config            = $this->GetHarmonyConfigJSON();
        $currentactivities = [];
        if (isset($config['device'])) {
            $devices[] = $config['device'];
            foreach ($devices as $harmonydevicelist) {
                foreach ($harmonydevicelist as $harmonydevice) {
                    $label                     = $harmonydevice['label'];
                    $harmonyid                 = $harmonydevice['id'];
                    $currentactivities[$label] = $harmonyid;
                }
            }
        }

        return $currentactivities;
    }

    //Verfügbare Aktivitäten ausgeben
    public function GetAvailableAcitivities()
    {
        $config            = $this->GetHarmonyConfigJSON();
        $currentactivities = [];
        if (isset($config['activity'])) {
            $activities[] = $config['activity'];
            foreach ($activities as $activitieslist) {
                foreach ($activitieslist as $activity) {
                    $label                     = $activity['label'];
                    $id                        = $activity['id'];
                    $currentactivities[$label] = $id;
                }
            }
        } else {
            $this->SendDebug('Get Activities', 'Could not find activities', 0);
        }

        return $currentactivities;
    }

    //Get JSON from Harmony Config
    public function GetHarmonyConfigJSON()
    {
        $json    = $this->GetHarmonyConfig();
        $devices = [];
        if ($json != '') {
            $devices = json_decode($json, true);
        } else {
            $this->SendDebug('Get Harmony Config', 'Config ist empty', 0);
        }

        return $devices;
    }

    protected function GetHarmonyConfig()
    {
        $jsonrawstring = $this->ReadAttributeString('HarmonyConfig');
        $jsonstart     = strpos($jsonrawstring, '![CDATA[');
        $jsonrawlength = strlen($jsonrawstring);
        $jsonend       = strripos($jsonrawstring, ']]></oa></iq><iq/>');
        if ($jsonend == false) {
            $jsonend = strripos($jsonrawstring, ']]></oa></iq>');
        }
        $jsonharmony = substr($jsonrawstring, ($jsonstart + 8), ($jsonend - $jsonrawlength));
        if ($jsonharmony == false) {
            $this->SendDebug('Error', 'Could not get harmony config', 0);
            $json = '';
        } else {
            $json = utf8_decode($jsonharmony);
        }

        return $json;
    }

    //Link für Harmony Activity anlegen
    public function CreateAktivityLink()
    {
        $hubname    = GetValue($this->GetIDForIdent('HarmonyHubName'));
        $hubip      = $this->GetParentIP();
        $hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline.
        $CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
        //Prüfen ob Kategorie schon existiert
        $HubCategoryID = @IPS_GetObjectIDByIdent('CatLogitechHub_' . $hubipident, $CategoryID);
        if ($HubCategoryID === false) {
            $HubCategoryID = IPS_CreateCategory();
            IPS_SetName($HubCategoryID, 'Logitech' . $hubname);
            IPS_SetIdent($HubCategoryID, 'CatLogitechHub_' . $hubipident);
            IPS_SetInfo($HubCategoryID, $hubip);
            IPS_SetParent($HubCategoryID, $CategoryID);
        }
        //Prüfen ob Instanz schon vorhanden
        $InstanzID = @IPS_GetObjectIDByIdent('Logitech_Harmony_Hub_' . $hubipident, $HubCategoryID);
        if ($InstanzID === false) {
            $InsID = IPS_CreateInstance('{485D0419-BE97-4548-AA9C-C083EB82E61E}');
            IPS_SetName($InsID, 'Logitech Harmony Hub'); // Instanz benennen
            IPS_SetIdent($InsID, 'Logitech_Harmony_Hub_' . $hubipident);
            IPS_SetParent($InsID, $HubCategoryID); // Instanz einsortieren unter dem Objekt mit der ID "$HubCategoryID"

            // Anlegen eines neuen Links für Harmony Aktivity
            $LinkID = IPS_CreateLink();             // Link anlegen
            IPS_SetName($LinkID, 'Logitech Harmony Hub Activity'); // Link benennen
            IPS_SetParent($LinkID, $InsID); // Link einsortieren
            IPS_SetLinkTargetID($LinkID, $this->GetIDForIdent('HarmonyActivity'));    // Link verknüpfen
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

    //-- Harmony API

    /**
     * Interne Funktion des SDK.
     * Wird von der Console aufgerufen, wenn 'unser' IO-Parent geöffnet wird.
     * Außerdem nutzen wir sie in Applychanges, da wir dort die Daten zum konfigurieren nutzen.
     */
    public function GetConfigurationForParent()
    {
        $Config['Port'] = 5222; // Harmony Port
        return json_encode($Config);
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
                'status'   => $this->FormStatus(),]
        );
    }

    /**
     * return form configurations on configuration step.
     *
     * @return array
     */
    protected function FormHead()
    {
        $form = [
            [
                'type'    => 'Label',
                'caption' => 'MyHarmony access data (email / password)',],
            [
                'name'    => 'Email',
                'type'    => 'ValidationTextBox',
                'caption' => 'Email',],
            [
                'name'    => 'Password',
                'type'    => 'PasswordTextBox',
                'caption' => 'Password',],];

        return $form;
    }

    /**
     * return form actions by token.
     *
     * @return array
     */
    protected function FormActions()
    {
        $form = [
            [
                'type'    => 'Label',
                'caption' => '1. Read Logitech Harmony Hub configuration:',],
            [
                'type'    => 'Button',
                'caption' => 'Read configuration',
                'onClick' => 'HarmonyHub_getConfig($id);',],
            [
                'type'    => 'Label',
                'caption' => '2. Setup Harmony Activities:',],
            [
                'type'    => 'Button',
                'caption' => 'Setup Harmony',
                'onClick' => 'HarmonyHub_SetupHarmony($id);',],
            [
                'type'    => 'Label',
                'caption' => '3. close this instance and open the Harmony configurator for setup of the devices.',],
            [
                'type'    => 'Label',
                'caption' => 'reload firmware version and Logitech Harmony Hub name:',],
            [
                'type'    => 'Button',
                'caption' => 'update Harmony info',
                'onClick' => 'HarmonyHub_getDiscoveryInfo($id);',],];

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
                'caption' => 'Creating instance.',],
            [
                'code'    => 102,
                'icon'    => 'active',
                'caption' => 'Harmony Hub accessible.',],
            [
                'code'    => 104,
                'icon'    => 'inactive',
                'caption' => 'interface closed.',],
            [
                'code'    => 201,
                'icon'    => 'inactive',
                'caption' => 'Please follow the instructions.',],
            [
                'code'    => 202,
                'icon'    => 'error',
                'caption' => 'Harmony Hub IP adress must not empty.',],
            [
                'code'    => 203,
                'icon'    => 'error',
                'caption' => 'No valid IP adress.',],
            [
                'code'    => 204,
                'icon'    => 'error',
                'caption' => 'connection to the Harmony Hub lost.',],
            [
                'code'    => 205,
                'icon'    => 'error',
                'caption' => 'field must not be empty.',],
            [
                'code'    => 206,
                'icon'    => 'error',
                'caption' => 'select category for import.',],
            [
                'code'    => 207,
                'icon'    => 'error',
                'caption' => 'Harmony Hub IO not found.',],];

        return $form;
    }

    protected function GetLinkObjIDs()
    {
        $hubip         = $this->GetParentIP();
        $hubipident    = str_replace('.', '_', $hubip); // Replaces all . with underline.
        $CategoryID    = $this->ReadPropertyInteger('ImportCategoryID');
        $HubCategoryID = @IPS_GetObjectIDByIdent('CatLogitechHub_' . $hubipident, $CategoryID);
        $MainCatID     = @IPS_GetObjectIDByIdent('LogitechActivitiesScripts_' . $hubipident, $HubCategoryID);
        $linkobjids    = IPS_GetChildrenIDs($MainCatID);

        return $linkobjids;
    }

    protected function ScreenCategory($CategoryID)
    {
        $catempty = IPS_GetChildrenIDs($CategoryID);
        if (empty($catempty)) {
            $catempty = true;
        } else {
            $catempty = false;
        }

        return $catempty;
    }

    public function __get($name)
    {
        if (strpos($name, 'Multi_') === 0) {
            $curCount = $this->GetBuffer('BufferCount_' . $name);
            if ($curCount == false) {
                $curCount = 0;
            }
            $data = '';
            for ($i = 0; $i < $curCount; $i++) {
                $data .= $this->GetBuffer('BufferPart' . $i . '_' . $name);
            }
        } else {
            $data = $this->GetBuffer($name);
        }

        return unserialize($data);
    }

    public function __set($name, $value)
    {
        $data = serialize($value);
        if (strpos($name, 'Multi_') === 0) {
            $oldCount = $this->GetBuffer('BufferCount_' . $name);
            if ($oldCount == false) {
                $oldCount = 0;
            }
            $parts    = str_split($data, 8000);
            $newCount = strval(count($parts));
            $this->SetBuffer('BufferCount_' . $name, $newCount);
            for ($i = 0; $i < $newCount; $i++) {
                $this->SetBuffer('BufferPart' . $i . '_' . $name, $parts[$i]);
            }
            for ($i = $newCount; $i < $oldCount; $i++) {
                $this->SetBuffer('BufferPart' . $i . '_' . $name, '');
            }
        } else {
            $this->SetBuffer($name, $data);
        }
    }

    private function SetMultiBuffer($name, $value)
    {
        if (IPS_GetKernelVersion() >= 5) {
            $this->{'Multi_' . $name} = $value;
        } else {
            $this->SetBuffer($name, $value);
        }
    }

    private function GetMultiBuffer($name)
    {
        if (IPS_GetKernelVersion() >= 5) {
            $value = $this->{'Multi_' . $name};
        } else {
            $value = $this->GetBuffer($name);
        }

        return $value;
    }
}

<?
if (@constant('IPS_BASE') == null) //Nur wenn Konstanten noch nicht bekannt sind.
{
// --- BASE MESSAGE
	define('IPS_BASE', 10000);                             //Base Message
	define('IPS_KERNELSHUTDOWN', IPS_BASE + 1);            //Pre Shutdown Message, Runlevel UNINIT Follows
	define('IPS_KERNELSTARTED', IPS_BASE + 2);             //Post Ready Message
// --- KERNEL
	define('IPS_KERNELMESSAGE', IPS_BASE + 100);           //Kernel Message
	define('KR_CREATE', IPS_KERNELMESSAGE + 1);            //Kernel is beeing created
	define('KR_INIT', IPS_KERNELMESSAGE + 2);              //Kernel Components are beeing initialised, Modules loaded, Settings read
	define('KR_READY', IPS_KERNELMESSAGE + 3);             //Kernel is ready and running
	define('KR_UNINIT', IPS_KERNELMESSAGE + 4);            //Got Shutdown Message, unloading all stuff
	define('KR_SHUTDOWN', IPS_KERNELMESSAGE + 5);          //Uninit Complete, Destroying Kernel Inteface
// --- KERNEL LOGMESSAGE
	define('IPS_LOGMESSAGE', IPS_BASE + 200);              //Logmessage Message
	define('KL_MESSAGE', IPS_LOGMESSAGE + 1);              //Normal Message                      | FG: Black | BG: White  | STLYE : NONE
	define('KL_SUCCESS', IPS_LOGMESSAGE + 2);              //Success Message                     | FG: Black | BG: Green  | STYLE : NONE
	define('KL_NOTIFY', IPS_LOGMESSAGE + 3);               //Notiy about Changes                 | FG: Black | BG: Blue   | STLYE : NONE
	define('KL_WARNING', IPS_LOGMESSAGE + 4);              //Warnings                            | FG: Black | BG: Yellow | STLYE : NONE
	define('KL_ERROR', IPS_LOGMESSAGE + 5);                //Error Message                       | FG: Black | BG: Red    | STLYE : BOLD
	define('KL_DEBUG', IPS_LOGMESSAGE + 6);                //Debug Informations + Script Results | FG: Grey  | BG: White  | STLYE : NONE
	define('KL_CUSTOM', IPS_LOGMESSAGE + 7);               //User Message                        | FG: Black | BG: White  | STLYE : NONE
// --- MODULE LOADER
	define('IPS_MODULEMESSAGE', IPS_BASE + 300);           //ModuleLoader Message
	define('ML_LOAD', IPS_MODULEMESSAGE + 1);              //Module loaded
	define('ML_UNLOAD', IPS_MODULEMESSAGE + 2);            //Module unloaded
// --- OBJECT MANAGER
	define('IPS_OBJECTMESSAGE', IPS_BASE + 400);
	define('OM_REGISTER', IPS_OBJECTMESSAGE + 1);          //Object was registered
	define('OM_UNREGISTER', IPS_OBJECTMESSAGE + 2);        //Object was unregistered
	define('OM_CHANGEPARENT', IPS_OBJECTMESSAGE + 3);      //Parent was Changed
	define('OM_CHANGENAME', IPS_OBJECTMESSAGE + 4);        //Name was Changed
	define('OM_CHANGEINFO', IPS_OBJECTMESSAGE + 5);        //Info was Changed
	define('OM_CHANGETYPE', IPS_OBJECTMESSAGE + 6);        //Type was Changed
	define('OM_CHANGESUMMARY', IPS_OBJECTMESSAGE + 7);     //Summary was Changed
	define('OM_CHANGEPOSITION', IPS_OBJECTMESSAGE + 8);    //Position was Changed
	define('OM_CHANGEREADONLY', IPS_OBJECTMESSAGE + 9);    //ReadOnly was Changed
	define('OM_CHANGEHIDDEN', IPS_OBJECTMESSAGE + 10);     //Hidden was Changed
	define('OM_CHANGEICON', IPS_OBJECTMESSAGE + 11);       //Icon was Changed
	define('OM_CHILDADDED', IPS_OBJECTMESSAGE + 12);       //Child for Object was added
	define('OM_CHILDREMOVED', IPS_OBJECTMESSAGE + 13);     //Child for Object was removed
	define('OM_CHANGEIDENT', IPS_OBJECTMESSAGE + 14);      //Ident was Changed
	define('OM_CHANGEDISABLED', IPS_OBJECTMESSAGE + 15);   //Operability has changed
// --- INSTANCE MANAGER
	define('IPS_INSTANCEMESSAGE', IPS_BASE + 500);         //Instance Manager Message
	define('IM_CREATE', IPS_INSTANCEMESSAGE + 1);          //Instance created
	define('IM_DELETE', IPS_INSTANCEMESSAGE + 2);          //Instance deleted
	define('IM_CONNECT', IPS_INSTANCEMESSAGE + 3);         //Instance connectged
	define('IM_DISCONNECT', IPS_INSTANCEMESSAGE + 4);      //Instance disconncted
	define('IM_CHANGESTATUS', IPS_INSTANCEMESSAGE + 5);    //Status was Changed
	define('IM_CHANGESETTINGS', IPS_INSTANCEMESSAGE + 6);  //Settings were Changed
	define('IM_CHANGESEARCH', IPS_INSTANCEMESSAGE + 7);    //Searching was started/stopped
	define('IM_SEARCHUPDATE', IPS_INSTANCEMESSAGE + 8);    //Searching found new results
	define('IM_SEARCHPROGRESS', IPS_INSTANCEMESSAGE + 9);  //Searching progress in %
	define('IM_SEARCHCOMPLETE', IPS_INSTANCEMESSAGE + 10); //Searching is complete
// --- VARIABLE MANAGER
	define('IPS_VARIABLEMESSAGE', IPS_BASE + 600);              //Variable Manager Message
	define('VM_CREATE', IPS_VARIABLEMESSAGE + 1);               //Variable Created
	define('VM_DELETE', IPS_VARIABLEMESSAGE + 2);               //Variable Deleted
	define('VM_UPDATE', IPS_VARIABLEMESSAGE + 3);               //On Variable Update
	define('VM_CHANGEPROFILENAME', IPS_VARIABLEMESSAGE + 4);    //On Profile Name Change
	define('VM_CHANGEPROFILEACTION', IPS_VARIABLEMESSAGE + 5);  //On Profile Action Change
// --- SCRIPT MANAGER
	define('IPS_SCRIPTMESSAGE', IPS_BASE + 700);           //Script Manager Message
	define('SM_CREATE', IPS_SCRIPTMESSAGE + 1);            //On Script Create
	define('SM_DELETE', IPS_SCRIPTMESSAGE + 2);            //On Script Delete
	define('SM_CHANGEFILE', IPS_SCRIPTMESSAGE + 3);        //On Script File changed
	define('SM_BROKEN', IPS_SCRIPTMESSAGE + 4);            //Script Broken Status changed
// --- EVENT MANAGER
	define('IPS_EVENTMESSAGE', IPS_BASE + 800);             //Event Scripter Message
	define('EM_CREATE', IPS_EVENTMESSAGE + 1);             //On Event Create
	define('EM_DELETE', IPS_EVENTMESSAGE + 2);             //On Event Delete
	define('EM_UPDATE', IPS_EVENTMESSAGE + 3);
	define('EM_CHANGEACTIVE', IPS_EVENTMESSAGE + 4);
	define('EM_CHANGELIMIT', IPS_EVENTMESSAGE + 5);
	define('EM_CHANGESCRIPT', IPS_EVENTMESSAGE + 6);
	define('EM_CHANGETRIGGER', IPS_EVENTMESSAGE + 7);
	define('EM_CHANGETRIGGERVALUE', IPS_EVENTMESSAGE + 8);
	define('EM_CHANGETRIGGEREXECUTION', IPS_EVENTMESSAGE + 9);
	define('EM_CHANGECYCLIC', IPS_EVENTMESSAGE + 10);
	define('EM_CHANGECYCLICDATEFROM', IPS_EVENTMESSAGE + 11);
	define('EM_CHANGECYCLICDATETO', IPS_EVENTMESSAGE + 12);
	define('EM_CHANGECYCLICTIMEFROM', IPS_EVENTMESSAGE + 13);
	define('EM_CHANGECYCLICTIMETO', IPS_EVENTMESSAGE + 14);
// --- MEDIA MANAGER
	define('IPS_MEDIAMESSAGE', IPS_BASE + 900);           //Media Manager Message
	define('MM_CREATE', IPS_MEDIAMESSAGE + 1);             //On Media Create
	define('MM_DELETE', IPS_MEDIAMESSAGE + 2);             //On Media Delete
	define('MM_CHANGEFILE', IPS_MEDIAMESSAGE + 3);         //On Media File changed
	define('MM_AVAILABLE', IPS_MEDIAMESSAGE + 4);          //Media Available Status changed
	define('MM_UPDATE', IPS_MEDIAMESSAGE + 5);
// --- LINK MANAGER
	define('IPS_LINKMESSAGE', IPS_BASE + 1000);           //Link Manager Message
	define('LM_CREATE', IPS_LINKMESSAGE + 1);             //On Link Create
	define('LM_DELETE', IPS_LINKMESSAGE + 2);             //On Link Delete
	define('LM_CHANGETARGET', IPS_LINKMESSAGE + 3);       //On Link TargetID change
// --- DATA HANDLER
	define('IPS_DATAMESSAGE', IPS_BASE + 1100);             //Data Handler Message
	define('FM_CONNECT', IPS_DATAMESSAGE + 1);             //On Instance Connect
	define('FM_DISCONNECT', IPS_DATAMESSAGE + 2);          //On Instance Disconnect
// --- SCRIPT ENGINE
	define('IPS_ENGINEMESSAGE', IPS_BASE + 1200);           //Script Engine Message
	define('SE_UPDATE', IPS_ENGINEMESSAGE + 1);             //On Library Refresh
	define('SE_EXECUTE', IPS_ENGINEMESSAGE + 2);            //On Script Finished execution
	define('SE_RUNNING', IPS_ENGINEMESSAGE + 3);            //On Script Started execution
// --- PROFILE POOL
	define('IPS_PROFILEMESSAGE', IPS_BASE + 1300);
	define('PM_CREATE', IPS_PROFILEMESSAGE + 1);
	define('PM_DELETE', IPS_PROFILEMESSAGE + 2);
	define('PM_CHANGETEXT', IPS_PROFILEMESSAGE + 3);
	define('PM_CHANGEVALUES', IPS_PROFILEMESSAGE + 4);
	define('PM_CHANGEDIGITS', IPS_PROFILEMESSAGE + 5);
	define('PM_CHANGEICON', IPS_PROFILEMESSAGE + 6);
	define('PM_ASSOCIATIONADDED', IPS_PROFILEMESSAGE + 7);
	define('PM_ASSOCIATIONREMOVED', IPS_PROFILEMESSAGE + 8);
	define('PM_ASSOCIATIONCHANGED', IPS_PROFILEMESSAGE + 9);
// --- TIMER POOL
	define('IPS_TIMERMESSAGE', IPS_BASE + 1400);            //Timer Pool Message
	define('TM_REGISTER', IPS_TIMERMESSAGE + 1);
	define('TM_UNREGISTER', IPS_TIMERMESSAGE + 2);
	define('TM_SETINTERVAL', IPS_TIMERMESSAGE + 3);
	define('TM_UPDATE', IPS_TIMERMESSAGE + 4);
	define('TM_RUNNING', IPS_TIMERMESSAGE + 5);
// --- STATUS CODES
	define('IS_SBASE', 100);
	define('IS_CREATING', IS_SBASE + 1); //module is being created
	define('IS_ACTIVE', IS_SBASE + 2); //module created and running
	define('IS_DELETING', IS_SBASE + 3); //module us being deleted
	define('IS_INACTIVE', IS_SBASE + 4); //module is not beeing used
// --- ERROR CODES
	define('IS_EBASE', 200);          //default errorcode
	define('IS_NOTCREATED', IS_EBASE + 1); //instance could not be created
// --- Search Handling
	define('FOUND_UNKNOWN', 0);     //Undefined value
	define('FOUND_NEW', 1);         //Device is new and not configured yet
	define('FOUND_OLD', 2);         //Device is already configues (InstanceID should be set)
	define('FOUND_CURRENT', 3);     //Device is already configues (InstanceID is from the current/searching Instance)
	define('FOUND_UNSUPPORTED', 4); //Device is not supported by Module
	define('vtBoolean', 0);
	define('vtInteger', 1);
	define('vtFloat', 2);
	define('vtString', 3);
	define('vtArray', 8);
	define('vtObject', 9);
}

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
		$this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}"); // Logitech Harmony Hub IO

		$this->RegisterPropertyString("Host", "");
		$this->RegisterPropertyInteger("Port", 5222);
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("Email", "");
		$this->RegisterPropertyString("Password", "");
		$this->RegisterPropertyBoolean("HarmonyVars", false);
		$this->RegisterPropertyBoolean("HarmonyScript", false);
		$this->RegisterPropertyBoolean("Alexa", false);
		$this->RegisterTimer('HarmonyHubSocketTimer', 40000, 'HarmonyHub_UpdateSocket(' . $this->InstanceID . ');');
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();

		$this->RegisterVariableString("BufferIN", "BufferIN", "", 1);
		$this->RegisterVariableString("HarmonyConfig", "Harmony Config", "", 4);
		$this->RegisterVariableString("HarmonyIdentity", "Identity", "", 7); //uuid
		$this->RegisterVariableBoolean("HarmonyInSession", "In Session", "", 8);
		$this->RegisterVariableBoolean("Configlock", "Config Lock", "", 9);
		$this->RegisterVariableString("FirmwareVersion", "Firmware Version", "", 10);
		$this->RegisterVariableString("HarmonyHubName", "Harmony Hub Name", "", 11);
		IPS_SetHidden($this->GetIDForIdent('HarmonyInSession'), true);
		IPS_SetHidden($this->GetIDForIdent('HarmonyIdentity'), true);
		IPS_SetHidden($this->GetIDForIdent('HarmonyConfig'), true);
		IPS_SetHidden($this->GetIDForIdent('BufferIN'), true);
		IPS_SetHidden($this->GetIDForIdent('Configlock'), true);
		$this->ValidateConfiguration();

	}

	/**
	 * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
	 * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
	 *
	 *
	 */
	protected $lockgetConfig = false;


	private function ValidateConfiguration()
	{

		$ip = $this->ReadPropertyString('Host');
		$email = $this->ReadPropertyString('Email');
		$password = $this->ReadPropertyString('Password');

		//IP prüfen
		if (!filter_var($ip, FILTER_VALIDATE_IP) === false) {
			$this->SetParentIP();
		} else {
			$this->SetStatus(203); //IP Adresse ist ungültig
		}

		//Email und Passwort prüfen
		if ($email == "" || $password == "") {
			$this->SetStatus(205); //Felder dürfen nicht leer sein
		} elseif ($email !== "" && $password !== "" && (!filter_var($ip, FILTER_VALIDATE_IP) === false)) {
			$userauthtokenid = @$this->GetIDForIdent("HarmonyUserAuthToken");
			if ($userauthtokenid === false) {
				//User Auth Token
				$userauthtokenid = $this->RegisterVariableString("HarmonyUserAuthToken", "User Auth Token", "", 1);
				IPS_SetHidden($userauthtokenid, true);
				$this->EnableAction("HarmonyUserAuthToken");

			} else {
				//Variable UserAuthToken existiert bereits

			}
			//Session Token
			$sessiontokenid = @$this->GetIDForIdent("HarmonySessionToken");
			if ($sessiontokenid === false) {
				$sessiontokenid = $this->RegisterVariableString("HarmonySessionToken", "SessionToken", "", 1);
				IPS_SetHidden($sessiontokenid, true);
				$this->EnableAction("HarmonySessionToken");
			}


			$userauthtoken = GetValue($userauthtokenid);
			if ($userauthtoken == "") {
				$this->RegisterUser($email, $password, $userauthtokenid);
			}

		}

		// Status Aktiv
		$this->SetStatus(102);
	}

	protected function configFilePath()
	{
		$IPSDir = IPS_GetKernelDir();
		$HarmonyDir = "webfront/user/Harmony_Config.txt";
		$configFilePath = $IPSDir . $HarmonyDir;
		return $configFilePath;
	}


	protected function RegisterCyclicTimer($ident, $interval, $script)
	{
		$id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);

		if ($id && IPS_GetEvent($id)['EventType'] <> 1) {
			IPS_DeleteEvent($id);
			$id = 0;
		}

		if (!$id) {
			$id = IPS_CreateEvent(1);
			IPS_SetParent($id, $this->InstanceID);
			IPS_SetIdent($id, $ident);
		}

		IPS_SetName($id, $ident);
		IPS_SetHidden($id, true);
		IPS_SetEventScript($id, "\$id = \$_IPS['TARGET'];\n$script;");

		if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type");

		if (!($interval > 0)) {
			IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);
			IPS_SetEventActive($id, false);
		} else {
			IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval);
			IPS_SetEventActive($id, true);
		}
	}

	public function UpdateSocket()
	{
		$IOHarmonyHub = $this->GetParent();
		IPS_ApplyChanges($IOHarmonyHub);
		$inSessionVarId = $this->GetIDForIdent("HarmonyInSession");
		SetValue($inSessionVarId, false);
	}

	private function SetParentIP()
	{
		$change = false;
		//$this->SetStatus(102); //IP Adresse ist gültig -> aktiv

		// Zwangskonfiguration des ClientSocket
		$ParentID = $this->GetParent();
		if (!($ParentID === false)) {
			if (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('Host')) {
				IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('Host'));
				$change = true;
			}
			if (IPS_GetProperty($ParentID, 'Port') <> $this->ReadPropertyInteger('Port')) {
				IPS_SetProperty($ParentID, 'Port', $this->ReadPropertyInteger('Port'));
				$change = true;
			}
			@IPS_SetName($ParentID, "Logitech Harmony Hub IO Socket (" . $this->ReadPropertyString('Host') . ")");
			$ParentOpen = $this->ReadPropertyBoolean('Open');

			// Keine Verbindung erzwingen wenn IP Harmony Hub leer ist, sonst folgt später Exception.
			if (!$ParentOpen)
				$this->SetStatus(104);

			if ($this->ReadPropertyString('Host') == '') {
				if ($ParentOpen)
					$this->SetStatus(202);
				$ParentOpen = false;
			}
			if (IPS_GetProperty($ParentID, 'Open') <> $ParentOpen) {
				IPS_SetProperty($ParentID, 'Open', $ParentOpen);
				$change = true;
			}
			if ($change) {
				@IPS_ApplyChanges($ParentID);
				// Socket vor Trennung durch Hub wieder neu aufbauen
				$this->RegisterCyclicTimer('Update', 55, 'HarmonyHub_UpdateSocket($id)');
				// Ping senden statt Socket neu Aufbau, Funktioniert zur Zeit noch nicht zuverlässig
				//$this->RegisterCyclicTimer('Update', 55, 'HarmonyHub_Ping($id)');
			}

		}
		return $change;
	}

	//Profile zuweisen und Geräte anlegen
	public function SetupHarmony()
	{
		//Konfig prüfen
		$HarmonyConfig = GetValue($this->GetIDForIdent("HarmonyConfig"));
		if ($HarmonyConfig == "") {
			$timestamp = time();
			$this->getConfig();
			$i = 0;
			do {
				IPS_Sleep(10);
				$updatetimestamp = IPS_GetVariable($this->GetIDForIdent("HarmonyConfig"))["VariableUpdated"];

				//echo $i."\n";
				$i++;
			} while ($updatetimestamp <= $timestamp);
		}
		//Activity Profil anlegen
		$this->SetHarmonyActivityProfile();

		//Harmony Firmware und Name auslesen
		$this->getDiscoveryInfo();
	}

	protected function SetupActivityScripts($HubCategoryID, $hubname)
	{
		$hubip = $this->ReadPropertyString('Host');
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline. 		
		$activities = $this->GetAvailableAcitivities();
		//Prüfen ob Kategorie schon existiert
		$MainCatID = @IPS_GetObjectIDByIdent("LogitechActivitiesScripts_" . $hubipident, $HubCategoryID);
		if ($MainCatID === false) {
			$MainCatID = IPS_CreateCategory();
			IPS_SetName($MainCatID, $hubname . " Aktivitäten");
			IPS_SetInfo($MainCatID, $hubname . " Aktivitäten");
			//IPS_SetIcon($NeueInstance, $Quellobjekt['ObjectIcon']);
			//IPS_SetPosition($NeueInstance, $Quellobjekt['ObjectPosition']);
			//IPS_SetHidden($NeueInstance, $Quellobjekt['ObjectIsHidden']);
			IPS_SetIdent($MainCatID, "LogitechActivitiesScripts_" . $hubipident);
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
		$Scriptname = $this->ReplaceSpecialCharacters($Scriptname);
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline.
		$Ident = "Script_Hub_" . $hubipident . "_" . $activity;
		$scriptident = $this->CreateIdent($Ident);
		$ScriptID = @IPS_GetObjectIDByIdent($scriptident, $MainCatID);

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
    Case "AlexaSmartHome": // Schalten durch den Alexa SmartHomeSkill
           
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
		$search = array("ä", "ö", "ü", "ß", "Ä", "Ö",
			"Ü", "&", "é", "á", "ó",
			" :)", " :D", " :-)", " :P",
			" :O", " ;D", " ;)", " ^^",
			" :|", " :-/", ":)", ":D",
			":-)", ":P", ":O", ";D", ";)",
			"^^", ":|", ":-/", "(", ")", "[", "]",
			"<", ">", "!", "\"", "§", "$", "%", "&",
			"/", "(", ")", "=", "?", "`", "´", "*", "'",
			"-", ":", ";", "²", "³", "{", "}",
			"\\", "~", "#", "+", ".", ",",
			"=", ":", "=)");
		$replace = array("ae", "oe", "ue", "ss", "Ae", "Oe",
			"Ue", "und", "e", "a", "o", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "", "");

		$str = str_replace($search, $replace, $str);
		$str = str_replace(' ', '_', $str); // Replaces all spaces with underline.
		$how = '_';
		//$str = strtolower(preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str));
		$str = preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str);
		return $str;
	}

	protected function SetHarmonyActivityProfile()
	{
		$hubip = $this->ReadPropertyString('Host');
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline.
		$json = $this->GetHarmonyConfigJSON();
		$activities[] = $json["activity"];
		$devices[] = $json["device"];
		$ProfileAssActivities = array();
		$assid = 1;
		foreach ($activities as $activitieslist) {
			foreach ($activitieslist as $activity) {
				$label = $activity["label"];
				$suggestedDisplay = $activity["suggestedDisplay"];
				$this->SendDebug("Harmony Activity", "suggested display " . $suggestedDisplay , 0);
				$id = $activity["id"];
				$activityTypeDisplayName  = $activity["activityTypeDisplayName"];
				$this->SendDebug("Harmony Activity", "activity type display name " . $activityTypeDisplayName, 0);
				$controlGroup  = $activity["controlGroup"];
				$this->SendDebug("Harmony Activity", "control group " . json_encode($controlGroup), 0);
				if (isset($activity["isTuningDefault"])) {
					$isTuningDefault  = $activity["isTuningDefault"];
					$this->SendDebug("Harmony Activity", "is tuning default " . json_encode($isTuningDefault), 0);
				}
				$sequences  = $activity["sequences"];
				$this->SendDebug("Harmony Activity", "sequences " . json_encode($sequences), 0);
				if (isset($activity["activityOrder"])) {
					$activityOrder  = $activity["activityOrder"];
					$this->SendDebug("Harmony Activity", "activity order " . json_encode($activityOrder), 0);
				}
				$fixit  = $activity["fixit"];
				$this->SendDebug("Harmony Activity", "fixit " . json_encode($fixit), 0);
				$type  = $activity["type"];
				$this->SendDebug("Harmony Activity", "type " . $type, 0);
				$icon  = $activity["icon"];
				$this->SendDebug("Harmony Activity", "icon " . $icon, 0);
				if (isset($activity["baseImageUri"])) {
					$baseImageUri  = $activity["baseImageUri"];
					$this->SendDebug("Harmony Activity", "base image uri " . $baseImageUri, 0);
				}
				if ($label == "PowerOff") {
					$ProfileAssActivities[$assid] = Array($id, "Power Off", "", 0xFA5858);
				} else {
					$ProfileAssActivities[$assid] = Array($id, utf8_decode($label), "", -1);
				}
				$assid++;
			}
		}
		$profilemax = count($ProfileAssActivities);
		$this->RegisterProfileIntegerHarmonyAss("LogitechHarmony.Activity".$hubipident, "Popcorn", "", "", -1, ($profilemax + 1), 0, 0, $ProfileAssActivities);
		$this->RegisterVariableInteger("HarmonyActivity", "Harmony Activity", "LogitechHarmony.Activity".$hubipident, 12);
		$this->EnableAction("HarmonyActivity");
		SetValueInteger($this->GetIDForIdent("HarmonyActivity"), -1);
	}


################## DUMMYS / WOARKAROUNDS - protected

	protected function GetParent()
	{
		$instance = IPS_GetInstance($this->InstanceID);
		return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
	}

	protected function HasActiveParent()
	{
		$instance = IPS_GetInstance($this->InstanceID);
		if ($instance['ConnectionID'] > 0) {
			$parent = IPS_GetInstance($instance['ConnectionID']);
			if ($parent['InstanceStatus'] == 102)
				return true;
		}
		return false;
	}

	// Testfunktion Data an Child weitergeben
	public function SendTest(string $Text)
	{
		// Weiterleitung zu allen Gerät-/Device-Instanzen
		$this->SendDebug("Logitech Harmony Hub", "Send :" . $Text, 0);
		$this->SendDataToChildren(json_encode(Array("DataID" => "{7924862A-0EEA-46B9-B431-97A3108BA380}", "Buffer" => $Text))); //Harmony Splitter Interface GUI
	}

	// Data an Child weitergeben
	public function ReceiveData($JSONString)
	{

		// Empfangene Daten vom I/O
		$data = json_decode($JSONString);
		$dataio = utf8_decode($data->Buffer);
		//$dataiomessage = json_encode($dataio);
		$this->SendDebug("Logitech Harmony Hub", "IO In: " . $dataio, 0);

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
		//$databuffer = $this->BufferHarmonyIn;
		$databuffer = GetValueString($this->GetIDForIdent("BufferIN"));

		// neu empfangene Daten an $databuffer anhängen
		$databuffer .= $data;

		// auf Inhalt prüfen und nach Typ auswerten
		preg_match('/^<[a-z]*/', $databuffer, $tag);
		$tag = str_replace("<", "", $tag);
		$tag = $tag[0];
		$bufferdelete = false;
		$configlock = GetValue($this->GetIDForIdent("Configlock"));

		if (strpos($databuffer, '</iq>') && ($configlock == true)) {
			//Daten komplett, weiterreichen
			$this->SendDebug("Logitech Harmony Hub Config", $databuffer, 0);
			SetValueString($this->GetIDForIdent("HarmonyConfig"), $databuffer);
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			$bufferdelete = true;
			//$this->lockgetConfig = false;
			SetValue($this->GetIDForIdent("Configlock"), false);
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, $tag);
		}

		if (strpos($databuffer, '</stream:stream>')) {
			$inSessionVarId = $this->GetIDForIdent("HarmonyInSession");
			$this->SendDebug("Logitech Harmony Hub", "In Session false", 0);
			SetValue($inSessionVarId, false);
		}

		//Line Feed trennen
		$payload = explode("<LF>", $databuffer);
		foreach ($payload as $content) {
			if (strpos($content, 'harmony.engine?startActivityFinished')) //Message wenn Activity abgeschlossen
			{
				SetValueString($this->GetIDForIdent("BufferIN"), "");
				//$this->BufferHarmonyIn = "";
				$bufferdelete = true;
				//CDATA auslesen
				$content = $this->XMPP_getPayload($content);
				// $type = $content['type']; // startActivityFinished
				$CurrentActivity = intval($content['activityId']);
				$activities = $this->GetAvailableAcitivities();
				$ActivityName = array_search($CurrentActivity, $activities);
				IPS_LogMessage("Logitech Harmony Hub", "Activity " . $ActivityName . " finished");
				SetValueInteger($this->GetIDForIdent("HarmonyActivity"), $CurrentActivity);
			} elseif (strpos($content, 'connect.stateDigest?notify')) // Notify Message
			{
				SetValueString($this->GetIDForIdent("BufferIN"), "");
				//$this->BufferHarmonyIn = "";
				$bufferdelete = true;
				//CDATA auslesen
				$content = $this->XMPP_getPayload($content);
				// $type = $content['type']; // notify
				//  activityStatus	0 = Hub is off, 1 = Activity is starting, 2 = Activity is started, 3 = Hub is turning off
				if (isset($content['activityId'])) {
					$CurrentActivity = intval($content['activityId']);
					$activityStatus = intval($content['activityStatus']);
					$activities = $this->GetAvailableAcitivities();
					$ActivityName = array_search($CurrentActivity, $activities);
					if ($activityStatus == 2) {
						IPS_LogMessage("Logitech Harmony Hub", "Activity " . $ActivityName . " is started");
						SetValueInteger($this->GetIDForIdent("HarmonyActivity"), $CurrentActivity);
					} elseif ($activityStatus == 1) {
						IPS_LogMessage("Logitech Harmony Hub", "Activity " . $ActivityName . " is starting");
					} elseif ($activityStatus == 0) {
						IPS_LogMessage("Logitech Harmony Hub", "Hub Status is off");
					}
				}
			}
		}

		if (strpos($databuffer, 'stream:stream')) {
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, "stream");
		} elseif ($tag == 'success') {
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, $tag);
		} elseif ($tag == 'failure') {
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, $tag);
		} elseif (strpos($databuffer, '</iq>')) {
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, $tag);
		} elseif ($bufferdelete == false) {
			// Inhalt von $databuffer im Puffer speichern
			//$this->BufferHarmonyIn = $databuffer;
			SetValueString($this->GetIDForIdent("BufferIN"), $databuffer);
		}
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
				$type = $content['type'];
				if ($type == "short") //Message bei Tastendruck
				{

				} elseif ($type == "startActivityFinished") // Message bei Activity
				{
					$CurrentActivity = intval($content['activityId']);
					$activities = $this->GetAvailableAcitivities();
					$ActivityName = array_search($CurrentActivity, $activities);
					$this->SendDebug("Logitech Harmony Hub", "Activity  " . $ActivityName . " started und finished", 0);
					SetValueInteger($this->GetIDForIdent("HarmonyActivity"), $CurrentActivity);
				} //  activityStatus	0 = Hub is off, 1 = Activity is starting, 2 = Activity is started, 3 = Hub is turning off
				elseif ($type == "notify") // Notify z.B. Hue oder Activity
				{
					if (isset($content['activityId'])) {
						$CurrentActivity = intval($content['activityId']);
						$activityStatus = intval($content['activityStatus']);
						$activities = $this->GetAvailableAcitivities();
						$ActivityName = array_search($CurrentActivity, $activities);
						SetValueInteger($this->GetIDForIdent("HarmonyActivity"), $CurrentActivity);
						if ($activityStatus == 2) {
							$this->SendDebug("Logitech Harmony Hub", "Activity " . $ActivityName . " is started", 0);
						} elseif ($activityStatus == 1) {
							$this->SendDebug("Logitech Harmony Hub", "Activity " . $ActivityName . " is starting", 0);
						} elseif ($activityStatus == 0) {
							$this->SendDebug("Logitech Harmony Hub", "Hub Status is off", 0);
						}
					}
				}
				break;
			case 'stream':
				$this->SendDebug("Logitech Harmony Hub", "RECV: STREAM Confirmation received", 0);
				preg_match('/id=\'([a-zA-Z0-9-_]+)\'\s/', $payload, $id);
				$this->SendDebug("Logitech Harmony Hub", "HARMONY XMPP -> id: " . $id[1], 0);
				if (!strpos($payload, "<bind")) {
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
				$this->SendDebug("Logitech Harmony Hub", "RECV: Authentication SUCCESS", 0);

				//$this->XMPP_OpenStream(); // Step 3 - Open  new stream for binding
				break;
			case 'failure':
				$this->SendDebug("Logitech Harmony Hub", "RECV: Authentication FAILED", 0);
				break;
			default:
				// We suppose that if there is no XMPP tag, we received a contination of an OA message and have to aggregate.
				$this->processIQ($payload);
				break;
		}

	}

	/**
	 * Internal XMPP processing function
	 * @param $xml
	 */
	protected function processIQ($xml)
	{
		// $parentID = $this->GetParent();

		preg_match('/<([a-z:]+)\s.*<([a-z:]+)\s/', $xml, $tag);
		if (isset($tag[2])) { // to avoid message of "undefined offset"
			if ($tag[2] == "oa") {
				// $responseacitvity = @strpos($xml, "getCurrentActivity");
				if (strpos($xml, "getCurrentActivity")) //Response Activity
				{
					preg_match('/<!\[CDATA\[\s*(.*)\s*/', $xml, $cdata); // <!\[(CDATA)\[\s*(.*?)\s*>
					$data = $cdata[1];
					if (!strpos($data, "result=")) {
						$posend = strpos($data, "]]");
						$activity = substr($data, 7, ($posend - 7));
						$this->SendDebug("Logitech Harmony Hub", "HarmonyActivity: " . $activity, 0);
						SetValue($this->GetIDForIdent("HarmonyActivity"), $activity);
					}
				} elseif (strpos($xml, "discoveryinfo")) //Response discoveryinfo
				{
					preg_match('/<!\[CDATA\[\s*(.*)\s*/', $xml, $cdata); // <!\[(CDATA)\[\s*(.*?)\s*>
					$jsonrawstring = $cdata[1];
					if (strpos($jsonrawstring, "current_fw_version")) {
						$jsonrawlength = strlen($jsonrawstring);
						$jsonend = strripos($jsonrawstring, "]]>");
						$jsondiscoveryinfo = substr($jsonrawstring, 0, ($jsonend - $jsonrawlength));
						$discoveryinfo = json_decode($jsondiscoveryinfo, true);
						// Auslesen Firmware und Name
						$FirmwareVersion = $discoveryinfo['current_fw_version'];
						$HarmonyHubName = $discoveryinfo['friendlyName'];
						// $hubProfiles = $discoveryinfo['hubProfiles'];
						$uuid = $discoveryinfo['uuid'];
						// $remoteId = $discoveryinfo['remoteId'];
						$this->SendDebug("Logitech Harmony Hub", "FirmwareVersion: " . $FirmwareVersion, 0);
						SetValue($this->GetIDForIdent("FirmwareVersion"), $FirmwareVersion);
						$this->SendDebug("Logitech Harmony Hub", "HarmonyHubName: " . $HarmonyHubName, 0);
						SetValue($this->GetIDForIdent("HarmonyHubName"), $HarmonyHubName);
						$this->SendDebug("Logitech Harmony Hub", "HarmonyIdentity: " . $uuid, 0);
						SetValue($this->GetIDForIdent("HarmonyIdentity"), $uuid);
					}
				} elseif (strpos($xml, "identity")) // We got an identity response message
				{
					$content = $this->XMPP_getPayload($xml);
					$this->SendDebug("Logitech Harmony Hub", "Hub Name: " . $content['friendlyName'] . ", identity = " . $content['identity'] . " - status = " . $content['status'], 0); // Info/Query Stanza
					$identityVariableId = $this->GetIDForIdent("HarmonyIdentity");
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
			if ($tag[2] == "bind") { // This is not an OA message, we suppose it is a resource binding or identity request reply
				if (!strpos($xml, "<bind")) {
					$this->SendDebug("Logitech Harmony Hub", "RECV: Unknown IQ Stanza", 0); // Info/Query Stanza
				} else { // STEP 5 - Binding Response (Continuation from Harmony_Read Script)
					preg_match('/<jid>(.*)<\/jid>/', $xml, $jid);
					$this->SendDebug("Logitech Harmony Hub", "RECV: IQ Stanza resource binding result - JID: " . $jid[1], 0);
					// Replace 2 lines below by a proper function to get the User Auth Token Value (IPS Tools Library)
					$tokenVariableId = @$this->GetIDForIdent("HarmonyUserAuthToken");
					if ($tokenVariableId === false) {
						$this->SendDebug("Logitech Harmony Hub", "ERROR in processIQ(): User Auth Token not defined (after binding reponse).", 0);
					} else {
						$this->SendDebug("Logitech Harmony Hub", "SEND: Sending Session Request", 0);
						$this->XMPP_Session(); // Test: Request session
						IPS_Sleep(200);
						$inSessionVarId = @$this->GetIDForIdent("HarmonyInSession");
						if ($inSessionVarId === false) {
							$this->SendDebug("Logitech Harmony Hub", "ERROR in processIQ(): Session Auth Variable not found (before requesting Session token)", 0);
						} else {
							if (!GetValue($inSessionVarId)) { // We request the Session token only if we are authenticated as guest
								$this->SendDebug("Logitech Harmony Hub", "SEND: Sending Session Token Request", 0);
								// $UserAuthToken = GetValue($tokenVariableId);
								$this->sendSessionTokenRequest();
								IPS_Sleep(500); // We need to wait to ensure that we receive the identity back from the server
							}
						}
					}
				}
			}
		} else { // There is no tag, we continue aggregationg the OA data

			//Konfig Auslesen
			$data = GetValue($this->GetIDForIdent("HarmonyConfig"));
			str_replace(array("\\", "\""), array("", ""), $xml);
			if (!strpos($data, "</oa>")) {
				// $data .= $xml;// continue aggregating until we get the closing OA tag
				if (!strpos($xml, "</oa>")) {
					$this->SendDebug("Logitech Harmony Hub", "RECV: Aggregation of CDATA ended.", 0);
				} else {
					$this->SendDebug("Logitech Harmony Hub", "RECV: Continuing CDATA aggregation...", 0);
				}
			}
		}

	}


	################## DATAPOINT RECEIVE FROM CHILD

	// Type String, Declaration can be used when PHP 7 is available
	//public function ForwardData(string $JSONString)
	public function ForwardData($JSONString)
	{
		$this->SendDebug("Forward data", $JSONString, 0);
		// Empfangene Daten von der Device Instanz
		$data = json_decode($JSONString);
		$datasend = $data->Buffer;
		if(property_exists($datasend, 'Method'))
		{
			$this->SendDebug("Forward data", "Method: " . $datasend->Method, 0);
			if($datasend->Method == "GetHarmonyConfigJSON")
			{
				$devices_json = $this->GetHarmonyConfig();
				$this->SendDebug("Logitech Harmony Hub", "Get Harmony Config", 0);
				return $devices_json;
			}
			if($datasend->Method == "getConfig")
			{
				$this->getConfig();
			}
			if($datasend->Method == "GetAvailableAcitivities")
			{
				$currentactivities = $this->GetAvailableAcitivities();
				$currentactivities_json = json_encode($currentactivities);
				$this->SendDebug("Forward data", "Send: " . $currentactivities_json, 0);
				return $currentactivities_json;
			}
		}

		if(property_exists($datasend, 'DeviceID'))
		{
			$DeviceID = $datasend->DeviceID;
			$Command = $datasend->Command;
			$BluetoothDevice = $datasend->BluetoothDevice;
			$commandoutobjid = @$this->GetIDForIdent("CommandOut");
			if ($commandoutobjid > 0) {
				SetValueString($commandoutobjid, "DeviceID: " . $DeviceID . ", Command: " . $Command . ", BluetoothDevice: " . $BluetoothDevice);
			}
			$this->SendDebug("Logitech Harmony Hub", "ForwardData HarmonyHub Splitter: DeviceID: " . $DeviceID . ", Command: " . $Command . ", BluetoothDevice: " . $BluetoothDevice, 0);
			$this->sendcommand($DeviceID, $Command, $BluetoothDevice);
			return true;
		}
		return false;
	}

	/**
	 * RequestAction
	 * @param string $Ident
	 * @param $Value
	 */
	//Type String, Declaration can be used when PHP 7 is available
	//public function RequestAction(string $Ident, $Value)
	public function RequestAction($Ident, $Value)
	{
		if ($Ident == "HarmonyActivity") {
			$activityID = $Value;
			$this->startActivity($activityID);
		}
	}


	public function Send(string $payload)
	{
		$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $payload)));
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
		$token = GetValue($this->GetIDForIdent("HarmonyUserAuthToken"));
		$tokenString = $token . ":name=foo#iOS6.0.1#iPhone"; // "token=".

		$this->XMPP_Send("<iq type='get' id='3174962747' from='guest'><oa xmlns='connect.logitech.com' mime='vnd.logitech.connect/vnd.logitech.pair'>token=" . $tokenString . "</oa></iq>");
	}

	/**
	 * Sends request to get Harmony configuration to XMPP Server
	 * The server will return the xml encoded config in a IQ/OA reply.
	 *
	 **/

	public function getConfig()
	{
		//$this->lockgetConfig = true;
		SetValue($this->GetIDForIdent("Configlock"), true);
		$this->XMPP_OpenStream();
		$iqString = "<iq type='get' id='2320426445' from='guest'>
		  <oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?config'>
		  </oa>
		</iq>";
		$this->XMPP_Send($iqString);

	}

	/**
	 * Opens the stream to XMPP Server
	 *
	 **/
	public function XMPP_OpenStream()
	{
		$this->XMPP_Send("<stream:stream to='connect.logitech.com' xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client' xml:lang='en' version='1.0'>"); //  xmlns:xml="http://www.w3.org/XML/1998/namespace"
	}

	/**
	 * Closes the stream to XMPP Server
	 *
	 **/
	public function XMPP_CloseStream()
	{
		$this->XMPP_Send("</stream:stream>");  // <presence type='unavailable'/>
	}

	/**
	 * Sends XMPP message to XMPP server
	 * If the socket is closed, adds the command to a queue array for subsequent execution.
	 *
	 * @param string $payload
	 *
	 * @return boolean true if success
	 **/
	protected function XMPP_Send($payload)
	{
		$parentactive = $this->HasActiveParent();
		$instanceHarmonySocket = $this->GetParent();
		// Open the socket if it is disconnected
		if (!$parentactive) {
			IPS_SetProperty($instanceHarmonySocket, 'Open', true);
			IPS_ApplyChanges($instanceHarmonySocket);
		}
		$this->Send($payload);
		return $parentactive;
	}


	protected $HARMONY_CLIENT_RESOURCE = 'ips';  // gatorade. ?

	// This 'from' value can be retrieved from the messages received by the Server ("to" field). But it does not seem necessary.
	protected $from = "guest";


	/**
	 * Sends Auth request to XMPP server
	 *
	 * @param string $user
	 * @param string $password
	 *
	 **/
	protected function XMPP_Auth($user, $password)
	{
		$this->SendDebug("Logitech Harmony Hub", "Authenticating with " . $user . " - " . $password, 0);
		$pass = base64_encode("\x00" . $user . "\x00" . $password);
		$this->XMPP_Send("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>" . $pass . "</auth>");
	}

	/**
	 * Sends Bind request to XMPP server
	 *
	 * @param string $resource A resource name
	 *
	 **/
	protected function XMPP_Bind($resource)
	{
		$this->SendDebug("Logitech Harmony Hub", "Binding with resource " . $resource, 0);
		$this->XMPP_Send("<iq type='set' id='bind_2'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>$resource</resource></bind></iq>");
	}

	/**
	 * Sends Session request to XMPP server
	 *
	 **/
	protected function XMPP_Session()
	{
		$this->SendDebug("Logitech Harmony Hub", "Sending Session request", 0);
		$this->XMPP_Send("<iq id='bind_3' type='set'><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></iq>");
	}


	/**
	 * Extracts CDATA payload from XMPP xml message
	 *
	 * @param string $xml
	 *
	 * @return array CDATA content formatted as 'type': Type of message, 'activityId', 'errorCode', 'errorString'
	 * activityId    ID of the current activity.
	 * activityStatus    0 = Hub is off, 1 = Activity is starting, 2 = Activity is started, 3 = Hub is turning off
	 **/

	protected function XMPP_getPayload($xml)
	{
		preg_match('/type="[a-zA-Z\.]+\?(.*)">/', $xml, $type);  // type= "connect.stateDigest?notify" 
		if (!empty($type)) {
			if (strpos($type[0], 'notify')) {
				$items['type'] = "notify";
				if (strpos($type[0], 'connect.stateDigest')) {
					$items['maintype'] = "state";
				} elseif (strpos($type[0], 'automation.state')) // message for HUE etc.
				{
					$items['maintype'] = "automation";
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
				$itemParts = explode('=', $item);
				$items[$itemParts[0]] = $itemParts[1];
			}
		}

		return $items;
	}


	/**
	 * Sends a request to the XMPP Server to get the current Activity ID
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
	 * Sends a request to the XMPP Server to get Infos (Firmware Version, Hub Name)
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
	 * @internal param DeviceID DeviceID as retrieved from the Harmony config
	 * @internal param Command Command as retrieved from teh Harmony config
	 *
	 */
	protected function sendcommand($DeviceID, $Command, $BluetoothDevice)
	{
		$inSessionVarId = $this->GetIDForIdent("HarmonyInSession");
		$insession = GetValue($inSessionVarId);
		if ($insession == true) {
			if ($BluetoothDevice == true) {
				$this->sendcommandAction($DeviceID, $Command);
			} else {
				$iqString = "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$DeviceID\",\"command\"::\"$Command\"}:status=press</oa></iq>";
				$this->XMPP_Send($iqString);
			}
		} else // Open Stream
		{
			$this->XMPP_OpenStream();
			IPS_Sleep(500); // wait for auth success
			if ($BluetoothDevice == true) {
				$this->sendcommandAction($DeviceID, $Command);
			} else {
				$iqString = "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$DeviceID\",\"command\"::\"$Command\"}:status=press</oa></iq>";
				$this->XMPP_Send($iqString);
			}
		}

	}

	/**
	 * Sends request to send an IR command to XMPP Server
	 * Device ID and Command name have to be retrieved from the config. No error check is made.
	 * @param $deviceID
	 * @param $command
	 * @internal param DeviceID DeviceID as retrieved from the Harmony config
	 * @internal param Command Command as retrieved from teh Harmony config
	 *
	 **/
	protected function sendcommandAction($deviceID, $command)
	{
		$identityVariableId = $this->GetIDForIdent("HarmonyIdentity");
		$identity = GetValue($identityVariableId);
		if ($identity == "") {
			$this->sendSessionTokenRequest();
			IPS_Sleep(500);
			$identity = GetValue($identityVariableId);
		}

		$iqString = "<iq id='7725179067' type='render' from='" . $identity . "'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>status=press:action={\"command\"::\"$command\",\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\"}:timestamp=0</oa></iq>";
		$this->SendDebug("Logitech Harmony Hub", "Sending: " . $iqString, 0);
		$this->XMPP_Send($iqString);
		IPS_Sleep(100);
		$iqString = "<iq id='7725179067' type='render' from='" . $identity . "'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>status=release:action={\"command\"::\"$command\",\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\"}:timestamp=100</oa></iq>";
		$this->SendDebug("Logitech Harmony Hub", "Sending: " . $iqString, 0);
		$this->XMPP_Send($iqString);
	}

	/**
	 * Sends request to send an IR command to XMPP Server
	 * Device ID and Command name have to be retrieved from the config. No error check is made.
	 *
	 * @param $deviceID
	 * @param $command
	 * @internal param DeviceID DeviceID as retrieved from the Harmony config
	 * @internal param Command Command as retrieved from teh Harmony config
	 **/

	protected function sendcommandRender($deviceID, $command)
	{
		$identityVariableId = $this->GetIDForIdent("HarmonyIdentity");
		$identity = GetValue($identityVariableId);
		if ($identity == "") {
			$this->sendSessionTokenRequest();
			IPS_Sleep(500);
			$identity = GetValue($identityVariableId);
		}
		$iqString = "<iq id='4191874917' type='render' from='" . $identity . "'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\",\"command\"::\"$command\"}:status=press</oa></iq>";
		$this->SendDebug("Logitech Harmony Hub", "Sending: " . $iqString, 0);
		$this->XMPP_Send($iqString);
	}

	/**
	 * Sends request to send an IR command to start a given activity to the XMPP Server
	 * The Activity ID has to be retrieved from the config. No error check is made.
	 *
	 * @param $activityID
	 * @internal param $activityID ID as retrieved from the Harmony config
	 *
	 * timestamp A unix timestamp so the hub can identify the order of incoming activity triggering request
	 **/
	public function startActivity(int $activityID)
	{
		//$timestamp = time();
		//$iqString = "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?startactivity'>activityId=".$activityID.":timestamp=".$timestamp."</oa></iq>";
		$iqString = "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?startactivity'>activityId=" . $activityID . ":timestamp=0</oa></iq>";
		$this->SendDebug("Logitech Harmony Hub", "Sending: " . $iqString, 0);
		$this->XMPP_Send($iqString);

	}

	/**
	 * Internal Authentication Processing function
	 **/
	public function processAuth()
	{
		// If we have been in a Sesssion Auth, we authenticate as guest to get the identity
		$inSessionVarId = $this->GetIDForIdent("HarmonyInSession");
		$inSession = GetValue($inSessionVarId);
		$identity = GetValue($this->GetIDForIdent("HarmonyIdentity"));
		if ($inSession) // Stream open auth ok
		{
			//XMPP_Auth('guest@x.com', 'guest');
			//$this->XMPP_Auth('guest@connect.logitech.com', 'gatorade.'); // Authenticate as guest
			$this->XMPP_Auth($identity . '@connect.logitech.com', $identity); // Authenticate as session
			//SetValue($inSessionVarId, false);
			SetValue($inSessionVarId, true);
		} else // Stream open no auth
		{
			if ($identity == "") {
				$this->sendSessionTokenRequest();
			} else {
				$this->XMPP_Auth($identity . '@connect.logitech.com', $identity); // Authenticate as session
			}
			SetValue($inSessionVarId, true);
		}
	}

	//UserAuthToken abholen falls nicht vorhanden
	public function RegisterUser(string $email, string $password, string $userauthtokenid)
	{
		$LOGITECH_AUTH_URL = "https://svcs.myharmony.com/CompositeSecurityServices/Security.svc/json/GetUserAuthToken";
		$timeout = 30;

		$credentials = array(
			'email' => $email,
			'password' => $password
		);
		$json_string = json_encode($credentials);// '{'.$cmd.'}';

		$ch = curl_init($LOGITECH_AUTH_URL);

		$options = array(
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_CONNECTTIMEOUT => $timeout,
			CURLOPT_VERBOSE => 1,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array('Content-type: application/json; charset=utf-8'),
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $json_string
		);

		// Setting curl options
		curl_setopt_array($ch, $options);
		// Getting results
		$json_result = curl_exec($ch);
		if ($json_result === FALSE) {
			die(curl_error($ch));
		}
		$result = json_decode($json_result);

		if (!curl_errno($ch)) {
			// No Error
			if (empty($result->GetUserAuthTokenResult)) {
				$UserAuthToken = "";
				$this->SendDebug("Logitech Harmony Hub", "No token transmitted", 0);
			} else {
				$UserAuthToken = $result->GetUserAuthTokenResult;
			}
			SetValue($userauthtokenid, $UserAuthToken);
		} else {
			$this->SendDebug("Logitech Harmony Hub", "Error: Authentification failed", 0);
			$this->SendDebug("Logitech Harmony Hub", "Error: Curl failed - " . curl_error($ch), 0);
		}

		//print_r ($result);
		return $json_result;

	}

	protected function SetHarmonyInstanceVars($InsIDList, $HubCategoryID)
	{
		$json = $this->GetHarmonyConfigJSON();
		$devices[] = $json["device"];

		foreach ($devices as $harmonydevicelist) {
			$harmonydeviceid = 0;
			foreach ($harmonydevicelist as $harmonydevice) {
				// $InstName = utf8_decode($harmonydevice["label"]); //Bezeichnung Harmony Device

				$controlGroups = $harmonydevice["controlGroup"];

				//Variablen anlegen
				$InsID = $InsIDList[$harmonydeviceid];
				foreach ($controlGroups as $controlGroup) {
					$commands = $controlGroup["function"]; //Function Array
					$profilemax = (count($commands)) - 1;
					$ProfileAssActivities = array();

					$assid = 0;
					$description = array();
					foreach ($commands as $command) {
						$harmonycommand = json_decode($command["action"], true); // command, type, deviceId
						//Wert , Name, Icon , Farbe
						$ProfileAssActivities[] = Array($assid, utf8_decode($harmonycommand["command"]), "", -1);
						$description[$assid] = utf8_decode($harmonycommand["command"]);
						$assid++;
					}
					$descriptionjson = json_encode($description);
					$profiledevicename = str_replace(" ", "", $harmonydevice["label"]);
					$profiledevicename = preg_replace('/[^A-Za-z0-9\-]/', '', $profiledevicename); // Removes special chars.
					$profilegroupname = str_replace(" ", "", $controlGroup["name"]);
					$profilegroupname = preg_replace('/[^A-Za-z0-9\-]/', '', $profilegroupname); // Removes special chars.
					//Variablenprofil anlegen
					$NumberAss = count($ProfileAssActivities);
					$VarIdent = $controlGroup["name"];//Command Group Name
					$VarName = $controlGroup["name"];//Command Group Name
					$varid = LHD_SetupVariable($InsID, $VarIdent, $VarName, "LogitechHarmony." . $profiledevicename . "." . $profilegroupname);
					if ($NumberAss >= 32)//wenn mehr als 32 Assoziationen splitten
					{
						$splitProfileAssActivities = array_chunk($ProfileAssActivities, 32);
						$splitdescription = array_chunk($description, 32);
						//2. Array neu setzten
						$id = 0;
						$SecondProfileAssActivities = array();
						$seconddescription = array();
						foreach ($splitProfileAssActivities[1] as $Activity) {
							$SecondProfileAssActivities[] = Array($id, $Activity[1], "", -1);
							$seconddescription[] = $Activity[1];
							$id++;
						}

						//Association 1
						$this->RegisterProfileIntegerHarmonyAss("LogitechHarmony." . $profiledevicename . "." . $profilegroupname, "Execute", "", "", 0, 31, 0, 0, $splitProfileAssActivities[0]); //32 Associationen

						//Association 2
						//var_dump($SecondProfileAssActivities);
						$this->RegisterProfileIntegerHarmonyAss("LogitechHarmony." . $profiledevicename . "." . $profilegroupname . "1", "Execute", "", "", 0, ($profilemax - 32), 0, 0, $SecondProfileAssActivities);

						$VarIdent1 = ($controlGroup["name"]) . "1";//Command Group Name
						$VarName1 = ($controlGroup["name"]) . "1";//Command Group Name
						$seconddescriptionjson = json_encode($seconddescription);
						$varid1 = LHD_SetupVariable($InsID, $VarIdent1, $VarName1, "LogitechHarmony." . $profiledevicename . "." . $profilegroupname . "1");
						IPS_SetInfo($varid1, $seconddescriptionjson);
						$firstdescriptionjson = json_encode($splitdescription[0]);
						IPS_SetInfo($varid, $firstdescriptionjson);
					} else {
						$this->RegisterProfileIntegerHarmonyAss("LogitechHarmony." . $profiledevicename . "." . $profilegroupname, "Execute", "", "", 0, $profilemax, 0, 0, $ProfileAssActivities);
						IPS_SetInfo($varid, $descriptionjson);
					}
				}
				$harmonydeviceid++;
			}
		}
	}

	//DeviceIDs auslesen
	public function GetHarmonyDeviceIDs()
	{
		$json = $this->GetHarmonyConfigJSON();
		$devices[] = $json["device"];
		$currentactivities = array();
		foreach ($devices as $harmonydevicelist) {
			foreach ($harmonydevicelist as $harmonydevice) {
				$label = $harmonydevice["label"];
				$harmonyid = $harmonydevice["id"];
				$currentactivities[$label] = $harmonyid;
			}
		}
		return $currentactivities;
	}

	//Verfügbare Aktivitäten ausgeben
	public function GetAvailableAcitivities()
	{
		$json = $this->GetHarmonyConfigJSON();
		$activities[] = $json["activity"];
		$currentactivities = array();
		foreach ($activities as $activitieslist) {
			foreach ($activitieslist as $activity) {
				$label = $activity["label"];
				$id = $activity["id"];
				$currentactivities[$label] = $id;
			}
		}
		return $currentactivities;
	}

	//Get JSON from Harmony Config
	public function GetHarmonyConfigJSON()
	{
		$json = $this->GetHarmonyConfig();
		$devices = json_decode($json, true);
		return $devices;
	}

	protected function GetHarmonyConfig()
	{
		$jsonrawstring = GetValue($this->GetIDForIdent("HarmonyConfig"));
		$jsonstart = strpos($jsonrawstring, '![CDATA[');
		$jsonrawlength = strlen($jsonrawstring);
		$jsonend = strripos($jsonrawstring, "]]></oa></iq><iq/>");
		if ($jsonend == false) {
			$jsonend = strripos($jsonrawstring, "]]></oa></iq>");
		}
		$jsonharmony = substr($jsonrawstring, ($jsonstart + 8), ($jsonend - $jsonrawlength));
		$json = utf8_decode($jsonharmony);
		return $json;
	}

	//Link für Harmony Activity anlegen
	public function CreateAktivityLink()
	{
		$hubname = GetValue($this->GetIDForIdent("HarmonyHubName"));
		$hubip = $this->ReadPropertyString('Host');
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline. 
		$CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		//Prüfen ob Kategorie schon existiert
		$HubCategoryID = @IPS_GetObjectIDByIdent("CatLogitechHub_" . $hubipident, $CategoryID);
		if ($HubCategoryID === false) {
			$HubCategoryID = IPS_CreateCategory();
			IPS_SetName($HubCategoryID, "Logitech" . $hubname);
			IPS_SetIdent($HubCategoryID, "CatLogitechHub_" . $hubipident);
			IPS_SetInfo($HubCategoryID, $hubip);
			IPS_SetParent($HubCategoryID, $CategoryID);
		}
		//Prüfen ob Instanz schon vorhanden
		$InstanzID = @IPS_GetObjectIDByIdent("Logitech_Harmony_Hub_" . $hubipident, $HubCategoryID);
		if ($InstanzID === false) {
			$InsID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
			IPS_SetName($InsID, "Logitech Harmony Hub"); // Instanz benennen
			IPS_SetIdent($InsID, "Logitech_Harmony_Hub_" . $hubipident);
			IPS_SetParent($InsID, $HubCategoryID); // Instanz einsortieren unter dem Objekt mit der ID "$HubCategoryID"

			// Anlegen eines neuen Links für Harmony Aktivity
			$LinkID = IPS_CreateLink();             // Link anlegen
			IPS_SetName($LinkID, "Logitech Harmony Hub Activity"); // Link benennen
			IPS_SetParent($LinkID, $InsID); // Link einsortieren
			IPS_SetLinkTargetID($LinkID, $this->GetIDForIdent("HarmonyActivity"));    // Link verknüpfen
		}
	}

	//Profile
	protected function RegisterProfileIntegerHarmony($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{

		if (!IPS_VariableProfileExists($Name)) {
			IPS_CreateVariableProfile($Name, 1);
		} else {
			$profile = IPS_GetVariableProfile($Name);
			if ($profile['ProfileType'] != 1)
			{
				$this->SendDebug("Harmony Hub", "Variable profile type does not match for profile " . $Name, 0);
			}
		}

		IPS_SetVariableProfileIcon($Name, $Icon);
		IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
		IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite

	}

	protected function RegisterProfileIntegerHarmonyAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Associations)
	{
		if (sizeof($Associations) === 0) {
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
	 * @access public
	 */
	public function GetConfigurationForParent()
	{
		$Config['Port'] = 5222; // Harmony Port
		return json_encode($Config);
	}

	//Configuration Form
	public function GetConfigurationForm()
	{
		$formhead = $this->FormHead();
		$formactions = $this->FormActions();
		$formelementsend = '{ "type": "Label", "label": "__________________________________________________________________________________________________" }';
		$formstatus = $this->FormStatus();
		return '{ ' . $formhead . $formelementsend . '],' . $formactions . $formstatus . ' }';
	}

	protected function FormHead()
	{
		$form = '"elements":
            [
                {
                    "name": "Open",
                    "type": "CheckBox",
                    "caption": "Open"
                },
				{ "type": "Label", "label": "IP adress Harmony Hub" },
                {
                    "name": "Host",
                    "type": "ValidationTextBox",
                    "caption": "IP adress"
                },
				{ "type": "Label", "label": "MyHarmony access data (email / password)" },
                {
                    "name": "Email",
                    "type": "ValidationTextBox",
                    "caption": "Email"
                },
				{
                    "name": "Password",
                    "type": "PasswordTextBox",
                    "caption": "Password"
                },';

		return $form;
	}

	protected function FormActions()
	{
		$form = '"actions":
			[
				{ "type": "Label", "label": "1. Read Logitech Harmony Hub configuration:" },
				{ "type": "Button", "label": "Read configuration", "onClick": "HarmonyHub_getConfig($id);" },
				{ "type": "Label", "label": "2. Setup Harmony Activities:" },
				{ "type": "Button", "label": "Setup Harmony", "onClick": "HarmonyHub_SetupHarmony($id);" },
				{ "type": "Label", "label": "3. close this instance and open the Harmony configurator for setup of the devices." },
				{ "type": "Label", "label": "reload firmware version and Logitech Harmony Hub name:" },
				{ "type": "Button", "label": "update Harmony info", "onClick": "HarmonyHub_getDiscoveryInfo($id);" }
			],';
		return $form;
	}

	protected function FormStatus()
	{
		$form = '"status":
            [
                {
                    "code": 101,
                    "icon": "inactive",
                    "caption": "Creating instance."
                },
				{
                    "code": 102,
                    "icon": "active",
                    "caption": "Harmony Hub accessible."
                },
                {
                    "code": 104,
                    "icon": "inactive",
                    "caption": "interface closed."
                },
                {
                    "code": 202,
                    "icon": "error",
                    "caption": "Harmony Hub IP adress must not empty."
                },
				{
                    "code": 203,
                    "icon": "error",
                    "caption": "No valid IP adress."
                },
                {
                    "code": 204,
                    "icon": "error",
                    "caption": "connection to the Harmony Hub lost."
                },
				{
                    "code": 205,
                    "icon": "error",
                    "caption": "field must not be empty."
                },
				{
                    "code": 206,
                    "icon": "error",
                    "caption": "select category for import."
                }
            ]';
		return $form;
	}

	protected function GetLinkObjIDs()
	{
		$hubip = $this->ReadPropertyString('Host');
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline. 
		$CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		$HubCategoryID = @IPS_GetObjectIDByIdent("CatLogitechHub_" . $hubipident, $CategoryID);
		$MainCatID = @IPS_GetObjectIDByIdent("LogitechActivitiesScripts_" . $hubipident, $HubCategoryID);
		$linkobjids = IPS_GetChildrenIDs($MainCatID);
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


}

?>
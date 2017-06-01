<?
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
		$this->RegisterPropertyInteger("ImportCategoryID", 0);
		$this->RegisterPropertyBoolean("HarmonyVars", false);
		$this->RegisterPropertyBoolean("HarmonyScript", false);
		$this->RegisterPropertyBoolean("Alexa", false);
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
		$change = false;
				
		$ip = $this->ReadPropertyString('Host');
		$email = $this->ReadPropertyString('Email');
		$password = $this->ReadPropertyString('Password');
		
		//IP prüfen
		if (!filter_var($ip, FILTER_VALIDATE_IP) === false)
			{
				$this->SetParentIP();
			}
		else
			{
			$this->SetStatus(203); //IP Adresse ist ungültig 
			}
		$change = false;	
		//Email und Passwort prüfen
		if ($email == "" || $password == "")
			{
				$this->SetStatus(205); //Felder dürfen nicht leer sein
			}
		elseif ($email !== "" && $password !== "" && (!filter_var($ip, FILTER_VALIDATE_IP) === false))
			{
				$userauthtokenid = @$this->GetIDForIdent("HarmonyUserAuthToken");
				if ($userauthtokenid === false)
					{
						//User Auth Token
						$userauthtokenid = $this->RegisterVariableString("HarmonyUserAuthToken", "User Auth Token", "~String", 1);
						IPS_SetHidden($userauthtokenid, true);
						$this->EnableAction("HarmonyUserAuthToken");
					
					}
				else
					{
						//Variable UserAuthToken existiert bereits
						
					}
				//Session Token
				$sessiontokenid = @$this->GetIDForIdent("HarmonySessionToken");
				if ($sessiontokenid === false)
					{
						$sessiontokenid = $this->RegisterVariableString("HarmonySessionToken", "SessionToken", "~String", 1);
						IPS_SetHidden($sessiontokenid, true);
						$this->EnableAction("HarmonySessionToken");
					}
				
				
				$userauthtoken = GetValue($userauthtokenid);	
				if($userauthtoken == "")
					{
						$this->RegisterUser($email, $password, $userauthtokenid);
					}
				$change = true;	
			}
		
		//Import Kategorie für HarmonyHub Geräte
		$ImportCategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		if ( $ImportCategoryID === 0)
			{
				// Status Error Kategorie zum Import auswählen
				$this->SetStatus(206);
			}
		elseif ( $ImportCategoryID != 0)	
			{
				// Status Aktiv
				$this->SetStatus(102);
			}
		
		//Konfig prüfen
		/*
		$HarmonyConfig = GetValue($this->GetIDForIdent("HarmonyConfig"));
		if($HarmonyConfig == "" && $change == true) //Config und IP vergeben
		{
			$timestamp = time();
			$this->lockgetConfig = true;
			$this->getConfig();
			$i = 0;
			do
			{
			IPS_Sleep(10);
			$updatetimestamp = IPS_GetVariable($this->GetIDForIdent("HarmonyConfig"))["VariableUpdated"];

			//echo $i."\n";
			$i++;
			}
			while($updatetimestamp <= $timestamp);
			//Aktivity Anlegen
			//Activity Profil anlegen
			
			$HarmonyActivityID = @$this->GetIDForIdent("HarmonyActivity");
			if ($HarmonyActivityID == false)
			{
				$this->SetHarmonyActivityProfile();
			}
			else
			{
				$HarmonyActivity = GetValue($this->GetIDForIdent("HarmonyActivity"));

				if ($HarmonyActivity == 0)
				{
					SetValue($this->GetIDForIdent("HarmonyActivity"), -1);
				}
			}	
		}
		*/		
		
		// Alexa Link anlegen
		if($this->ReadPropertyBoolean('Alexa'))
			{
				$this->CreateAlexaLinks();
			}
		else
			{
				$this->DeleteAlexaLinks();
			}
	}
	
	protected function configFilePath()
	{
		$IPSDir = 	IPS_GetKernelDir();
		$HarmonyDir = "webfront/user/Harmony_Config.txt";
		$configFilePath = $IPSDir.$HarmonyDir;
		return $configFilePath;
	}
	
	
	protected function RegisterTimer($ident, $interval, $script)
	{
		$id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);

		if ($id && IPS_GetEvent($id)['EventType'] <> 1)
		{
		  IPS_DeleteEvent($id);
		  $id = 0;
		}

		if (!$id)
		{
		  $id = IPS_CreateEvent(1);
		  IPS_SetParent($id, $this->InstanceID);
		  IPS_SetIdent($id, $ident);
		}

		IPS_SetName($id, $ident);
		IPS_SetHidden($id, true);
		IPS_SetEventScript($id, "\$id = \$_IPS['TARGET'];\n$script;");

		if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type");

		if (!($interval > 0))
		{
		  IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);
		  IPS_SetEventActive($id, false);
		}
		else
		{
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
		if (!($ParentID === false))
			{
				if (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('Host'))
					{
						IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('Host'));
						$change = true;
					}
				if (IPS_GetProperty($ParentID, 'Port') <> $this->ReadPropertyInteger('Port'))
					{
						IPS_SetProperty($ParentID, 'Port', $this->ReadPropertyInteger('Port'));
						$change = true;
					}
					@IPS_SetName($ParentID, "Logitech Harmony Hub IO Socket (".$this->ReadPropertyString('Host').")");
					$ParentOpen = $this->ReadPropertyBoolean('Open');
						
			// Keine Verbindung erzwingen wenn IP Harmony Hub leer ist, sonst folgt später Exception.
				if (!$ParentOpen)
						$this->SetStatus(104);

				if ($this->ReadPropertyString('Host') == '')
					{
						if ($ParentOpen)
								$this->SetStatus(202);
						$ParentOpen = false;
					}
				if (IPS_GetProperty($ParentID, 'Open') <> $ParentOpen)
					{
						IPS_SetProperty($ParentID, 'Open', $ParentOpen);
						$change = true;	
					}
				if ($change)
				{
					@IPS_ApplyChanges($ParentID);
					// Socket vor Trennung durch Hub wieder neu aufbauen
					$this->RegisterTimer('Update', 55, 'HarmonyHub_UpdateSocket($id)');
					// Ping senden statt Socket neu Aufbau, Funktioniert zur Zeit noch nicht zuverlässig
					//$this->RegisterTimer('Update', 55, 'HarmonyHub_Ping($id)');
				}
					
			}
		return $change;		
	}
	
	//Profile zuweisen und Geräte anlegen
	public function SetupHarmony()
	{
		//Konfig prüfen
		$HarmonyConfig = GetValue($this->GetIDForIdent("HarmonyConfig"));
		if($HarmonyConfig == "")
		{
			$timestamp = time();
			$this->getConfig();
			$i = 0;
			do
			{
			IPS_Sleep(10);
			$updatetimestamp = IPS_GetVariable($this->GetIDForIdent("HarmonyConfig"))["VariableUpdated"];

			//echo $i."\n";
			$i++;
			}
			while($updatetimestamp <= $timestamp);
		}
		//Activity Profil anlegen
		$this->SetHarmonyActivityProfile();
			
		//Harmony Devices anlegen
		$this->SetupHarmonyInstance();
		
		//Harmony Aktivity Link setzten
		$this->CreateAktivityLink();
				
		//Harmony Firmware und Name auslesen
		$this->getDiscoveryInfo();
	}
	
	protected function SetupActivityScripts($HubCategoryID, $hubname)
	{
		$hubip = $this->ReadPropertyString('Host');
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline. 		
		$activities = $this->GetAvailableAcitivities();
		//Prüfen ob Kategorie schon existiert
		$MainCatID = @IPS_GetObjectIDByIdent("LogitechActivitiesScripts_".$hubipident, $HubCategoryID);
		if ($MainCatID === false)
			{
			$MainCatID = IPS_CreateCategory();
			IPS_SetName($MainCatID, $hubname." Aktivitäten");
			IPS_SetInfo($MainCatID, $hubname." Aktivitäten");
			//IPS_SetIcon($NeueInstance, $Quellobjekt['ObjectIcon']);
			//IPS_SetPosition($NeueInstance, $Quellobjekt['ObjectPosition']);
			//IPS_SetHidden($NeueInstance, $Quellobjekt['ObjectIsHidden']);
			IPS_SetIdent($MainCatID, "LogitechActivitiesScripts_".$hubipident);
			IPS_SetParent($MainCatID, $HubCategoryID);
			}
			
		foreach ($activities as $activityname => $activity)
			{
				//Prüfen ob Script schon existiert
				$ScriptID = $this->CreateActivityScript($activityname, $MainCatID, $hubip, $activity);									
			}	
	}
		
	protected function CreateActivityScript($Scriptname, $MainCatID, $hubip, $activity)
	{
		$Scriptname = $this->ReplaceSpecialCharacters($Scriptname);
		$Ident = $this->CreateIdent($Scriptname);
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline. 
		$Ident = "Script_Hub_".$hubipident."_".$activity;
		$scriptident = $this->CreateIdent($Ident);
		$ScriptID = @IPS_GetObjectIDByIdent($scriptident, $MainCatID);
								
		if ($ScriptID === false)
			{
				$ScriptID = IPS_CreateScript(0);
				IPS_SetName($ScriptID, $Scriptname);
				IPS_SetParent($ScriptID, $MainCatID);
				IPS_SetIdent($ScriptID, $scriptident);
				$content = '<?
Switch ($_IPS[\'SENDER\']) 
    { 
    Default: 
    Case "RunScript": 
		HarmonyHub_startActivity('.$this->InstanceID.', '.$activity.');
    Case "Execute": 
        HarmonyHub_startActivity('.$this->InstanceID.', '.$activity.');
    Case "TimerEvent": 
        break; 

    Case "Variable": 
    Case "AlexaSmartHome": // Schalten durch den Alexa SmartHomeSkill
           
    if ($_IPS[\'VALUE\'] == True) 
        { 
            // einschalten
            HarmonyHub_startActivity('.$this->InstanceID.', '.$activity.');   
        } 
    else 
        { 
            //ausschalten
            HarmonyHub_startActivity('.$this->InstanceID.', -1);
        } 
       break;
    Case "WebFront":        // Zum schalten im Webfront 
        HarmonyHub_startActivity('.$this->InstanceID.', '.$activity.');   
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
		$harmonyconfig = GetValue($this->GetIDForIdent("HarmonyConfig"));
		$IPSDir = 	IPS_GetKernelDir();
		//$HarmonyDir = "webfront/user/Harmony_Config.txt";
		//$configFilePath = $IPSDir.$HarmonyDir;
		// File
		//$jsonrawstring = file_get_contents($configFilePath);
		
		//IPS Var
		$json = $this->GetHarmonyConfigJSON();
		$activities[] = $json["activity"];
		$devices[] =  $json["device"];
		$ProfileAssActivities = array();
		$assid = 1;
		foreach ($activities as $activitieslist)
			{
			foreach ($activitieslist as $activity)
				{
				$label = $activity["label"];
				$suggestedDisplay = $activity["suggestedDisplay"];
				$id  = $activity["id"];
				$activityTypeDisplayName  = $activity["activityTypeDisplayName"];
				$controlGroup  = $activity["controlGroup"];
				if (isset($activity["isTuningDefault"]))
					{
					   $isTuningDefault  = $activity["isTuningDefault"];
					}
				$sequences  = $activity["sequences"];
				if (isset($activity["activityOrder"]))
					{
					 $activityOrder  = $activity["activityOrder"];
					}
				$fixit  = $activity["fixit"];
				$type  = $activity["type"];
				$icon  = $activity["icon"];
				if (isset($activity["baseImageUri"]))
					{
					 $baseImageUri  = $activity["baseImageUri"];
					}
				if($label == "PowerOff")
					{
					   $ProfileAssActivities[$assid] = Array($id, "Power Off",  "", 0xFA5858);
					}
				else
					{
					   $ProfileAssActivities[$assid] = Array($id, utf8_decode($label),  "", -1);
					}
				$assid++;
				}
			}
		$profilemax = count($ProfileAssActivities);
		$this->RegisterProfileIntegerHarmonyAss("LogitechHarmony.Activity" , "Popcorn", "", "", -1 , ($profilemax+1), 0, 0, $ProfileAssActivities);
		$this->RegisterVariableInteger("HarmonyActivity", "Harmony Activity", "LogitechHarmony.Activity", 12);
		$this->EnableAction("HarmonyActivity");
		SetValueInteger($this->GetIDForIdent("HarmonyActivity"), -1);
		//IPS_SetVariableCustomProfile($this->GetIDForIdent("HarmonyActivity"), "LogitechHarmony.Activity");
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
        if ($instance['ConnectionID'] > 0)
        {
            $parent = IPS_GetInstance($instance['ConnectionID']);
            if ($parent['InstanceStatus'] == 102)
                return true;
        }
        return false;
    }

    protected function RequireParent($ModuleID, $Name = '')
    {

        $instance = IPS_GetInstance($this->InstanceID);
        if ($instance['ConnectionID'] == 0)
        {

            $parentID = IPS_CreateInstance($ModuleID);
            $instance = IPS_GetInstance($parentID);
            if ($Name == '')
                IPS_SetName($parentID, $instance['ModuleInfo']['ModuleName']);
            else
                IPS_SetName($parentID, $Name);
            IPS_ConnectInstance($this->InstanceID, $parentID);
        }
    }

    private function SetValueBoolean($Ident, $value)
    {
        $id = $this->GetIDForIdent($Ident);
        if (GetValueBoolean($id) <> $value)
        {
            SetValueBoolean($id, $value);
            return true;
        }
        return false;
    }

    private function SetValueInteger($Ident, $value)
    {
        $id = $this->GetIDForIdent($Ident);
        if (GetValueInteger($id) <> $value)
        {
            SetValueInteger($id, $value);
            return true;
        }
        return false;
    }

    private function SetValueString($Ident, $value)
    {
        $id = $this->GetIDForIdent($Ident);
        if (GetValueString($id) <> $value)
        {
            SetValueString($id, $value);
            return true;
        }
        return false;
    }

    protected function SetStatus($InstanceStatus)
    {
        if ($InstanceStatus <> IPS_GetInstance($this->InstanceID)['InstanceStatus'])
            parent::SetStatus($InstanceStatus);
    }

	// Testfunktion Data an Child weitergeben
	public function SendTest(string $Text)
	{	 
		// Weiterleitung zu allen Gerät-/Device-Instanzen
		$this->SendDebug("Logitech Harmony Hub","Send :".$Text,0);
		$this->SendDataToChildren(json_encode(Array("DataID" => "{7924862A-0EEA-46B9-B431-97A3108BA380}", "Buffer" => $Text))); //Harmony Splitter Interface GUI
	}
	
	// Data an Child weitergeben
	public function ReceiveData($JSONString)
	{
	 
		// Empfangene Daten vom I/O
		$data = json_decode($JSONString);
		$dataio = $data->Buffer;
		//$dataiomessage = json_encode($dataio);
		$this->SendDebug("Logitech Harmony Hub","IO In: ".$dataio,0);
				
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
		$tag = str_replace ( "<" , "" , $tag);
		$tag = $tag[0];
		$bufferdelete = false;
		$configlock = GetValue($this->GetIDForIdent("Configlock"));
		
		if (strpos($databuffer, '</iq>') && ($configlock == true))	
		{
			//Daten komplett, weiterreichen
			$this->SendDebug("Logitech Harmony Hub Config",$databuffer,0);
			SetValueString($this->GetIDForIdent("HarmonyConfig"), $databuffer);
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			$bufferdelete = true;
			//$this->lockgetConfig = false;
			SetValue($this->GetIDForIdent("Configlock"), false);
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, $tag);
		}
		
		if (strpos($databuffer, '</stream:stream>'))
		{
			$inSessionVarId = $this->GetIDForIdent("HarmonyInSession");
			$this->SendDebug("Logitech Harmony Hub","In Session false",0);
			SetValue($inSessionVarId, false);
		}
		
		//Line Feed trennen
		$payload = explode("<LF>", $databuffer);
		foreach ($payload as $content)
		{
			if (strpos($content, 'harmony.engine?startActivityFinished')) //Message wenn Activity abgeschlossen
				{
				SetValueString($this->GetIDForIdent("BufferIN"), "");
				//$this->BufferHarmonyIn = "";
				$bufferdelete = true;
				//CDATA auslesen
				$content = $this->XMPP_getPayload($content);
				$type = $content['type']; // startActivityFinished
				$CurrentActivity = intval($content['activityId']);
				$activities = $this->GetAvailableAcitivities();
				$ActivityName = array_search($CurrentActivity, $activities);
				IPS_LogMessage("Logitech Harmony Hub", "Activity ". $ActivityName." finished");
				SetValueInteger($this->GetIDForIdent("HarmonyActivity"), $CurrentActivity);
				}
			elseif (strpos($content, 'connect.stateDigest?notify')) // Notify Message
				{
				SetValueString($this->GetIDForIdent("BufferIN"), "");
				//$this->BufferHarmonyIn = "";
				$bufferdelete = true;
				//CDATA auslesen
				$content = $this->XMPP_getPayload($content);
				$type = $content['type']; // notify
				//  activityStatus	0 = Hub is off, 1 = Activity is starting, 2 = Activity is started, 3 = Hub is turning off
				if (isset($content['activityId']))
					{
						$CurrentActivity = intval($content['activityId']);
						$activityStatus = intval($content['activityStatus']);
						$activities = $this->GetAvailableAcitivities();
						$ActivityName = array_search($CurrentActivity, $activities);
						if ($activityStatus == 2)
						{
							IPS_LogMessage("Logitech Harmony Hub", "Activity ". $ActivityName." is started");
							SetValueInteger($this->GetIDForIdent("HarmonyActivity"), $CurrentActivity);
						}
						elseif ($activityStatus == 1)
						{
							IPS_LogMessage("Logitech Harmony Hub", "Activity ". $ActivityName." is starting");	
						}
						elseif ($activityStatus == 0)
						{
							IPS_LogMessage("Logitech Harmony Hub", "Hub Status is off");	
						}
					}		
				}
		}
		
		if (strpos($databuffer, 'stream:stream'))
		{
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			$bufferdelete = true;
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, "stream");
		}
		elseif ($tag == 'success')
		{
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			$bufferdelete = true;
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, $tag);
		}
		elseif ($tag == 'failure')
		{
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			$bufferdelete = true;
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, $tag);
		}
		elseif (strpos($databuffer, '</iq>'))
		{
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			$bufferdelete = true;
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, $tag);
		}
		elseif ($bufferdelete == false)
		{
			// Inhalt von $databuffer im Puffer speichern
			//$this->BufferHarmonyIn = $databuffer;
			SetValueString($this->GetIDForIdent("BufferIN"), $databuffer);	
		}
	}
	
	//read incoming data
	protected function ReadPayload($payload, $tag)
	{
		switch($tag)
		{
		   case 'iq':
				$this->processIQ($payload); // Step 5 - Received IQ message for bind response (see function for further steps)
				break;
			case 'message':
				$content = $this->XMPP_getPayload($payload);
				$type = $content['type'];
				if ($type == "short") //Message bei Tastendruck
				{
					
				}
				elseif ($type == "startActivityFinished") // Message bei Activity
				{
					$CurrentActivity = intval($content['activityId']);
					$activities = $this->GetAvailableAcitivities();
					$ActivityName = array_search($CurrentActivity, $activities);
					$this->SendDebug("Logitech Harmony Hub","Activity  ". $ActivityName." started und finished",0);
					SetValueInteger($this->GetIDForIdent("HarmonyActivity"), $CurrentActivity);
				}
				//  activityStatus	0 = Hub is off, 1 = Activity is starting, 2 = Activity is started, 3 = Hub is turning off
				elseif ($type == "notify") // Notify z.B. Hue oder Activity
				{
					if (isset($content['activityId']))
					{
						$CurrentActivity = intval($content['activityId']);
						$activityStatus = intval($content['activityStatus']);
						$activities = $this->GetAvailableAcitivities();
						$ActivityName = array_search($CurrentActivity, $activities);
						SetValueInteger($this->GetIDForIdent("HarmonyActivity"), $CurrentActivity);
						if ($activityStatus == 2)
						{
							$this->SendDebug("Logitech Harmony Hub","Activity ". $ActivityName." is started",0);
						}
						elseif ($activityStatus == 1)
						{
							$this->SendDebug("Logitech Harmony Hub","Activity ". $ActivityName." is starting",0);	
						}
						elseif ($activityStatus == 0)
						{
							$this->SendDebug("Logitech Harmony Hub","Hub Status is off",0);	
						}
					}
				}
				break;
			case 'stream':
				$this->SendDebug("Logitech Harmony Hub","RECV: STREAM Confirmation received",0);	
				preg_match('/id=\'([a-zA-Z0-9-_]+)\'\s/', $payload, $id);
				$this->SendDebug("Logitech Harmony Hub","HARMONY XMPP -> id: ".$id[1],0);
				if (!strpos($payload, "<bind"))
					{
					// Step 2 - Received stream response: mechanism feature advertisement. We authenticate.
					$this->processAuth();
					}
				else
				{
				// Step 4 - Received stream response: resource binding feature advertisement
				$resource = $this->HARMONY_CLIENT_RESOURCE;
				$this->XMPP_Bind($resource);   // Now bind a resource (defined in library)
				}
				//print_r($xml);
				break;
			case 'success':
			   $this->SendDebug("Logitech Harmony Hub","RECV: Authentication SUCCESS",0);	
			   			   
			   //$this->XMPP_OpenStream(); // Step 3 - Open  new stream for binding
			   break;
			case 'failure':
			   $this->SendDebug("Logitech Harmony Hub","RECV: Authentication FAILED",0);
			   break;
			default:
			   // We suppose that if there is no XMPP tag, we received a contination of an OA message and have to aggregate.
			   $this->processIQ($payload);
			   break;
		}
	
	}
	
	/**
	* Internal XMPP processing function
	**/
	protected function processIQ($xml)
	{
		$parentID = $this->GetParent();
				
		preg_match('/<([a-z:]+)\s.*<([a-z:]+)\s/', $xml, $tag);
		if (isset($tag[2]))
		{ // to avoid message of "undefined offset"
			if ($tag[2] == "oa")
			{
				$responseacitvity = @strpos($xml, "getCurrentActivity");
				if(strpos($xml, "getCurrentActivity")) //Response Activity
				{
					preg_match('/<!\[CDATA\[\s*(.*)\s*/', $xml, $cdata); // <!\[(CDATA)\[\s*(.*?)\s*>
					$data = $cdata[1];			
					if (!strpos($data, "result="))
					{ 
						$posend = strpos($data, "]]");
						$activity = substr($data, 7, ($posend-7));
						$this->SendDebug("Logitech Harmony Hub","HarmonyActivity: ".$activity,0);
						SetValue($this->GetIDForIdent("HarmonyActivity"), $activity);
					} 
				}
				elseif(strpos($xml, "discoveryinfo")) //Response discoveryinfo
				{
					preg_match('/<!\[CDATA\[\s*(.*)\s*/', $xml, $cdata); // <!\[(CDATA)\[\s*(.*?)\s*>
					$jsonrawstring = $cdata[1];
					if (strpos($jsonrawstring, "current_fw_version"))
					{
						$jsonrawlength = strlen($jsonrawstring);
						$jsonend = strripos($jsonrawstring, "]]>");
						$jsondiscoveryinfo = substr($jsonrawstring, 0, ($jsonend-$jsonrawlength));
						$discoveryinfo = json_decode($jsondiscoveryinfo, true);
						// Auslesen Firmware und Name
						$FirmwareVersion = $discoveryinfo['current_fw_version'];
						$HarmonyHubName = $discoveryinfo['friendlyName'];
						$hubProfiles = $discoveryinfo['hubProfiles'];
						$uuid = $discoveryinfo['uuid'];
						$remoteId = $discoveryinfo['remoteId'];
						$this->SendDebug("Logitech Harmony Hub","FirmwareVersion: ".$FirmwareVersion,0);
						SetValue($this->GetIDForIdent("FirmwareVersion"), $FirmwareVersion);
						$this->SendDebug("Logitech Harmony Hub","HarmonyHubName: ".$HarmonyHubName,0);
						SetValue($this->GetIDForIdent("HarmonyHubName"), $HarmonyHubName);
						$this->SendDebug("Logitech Harmony Hub","HarmonyIdentity: ".$uuid,0);
						SetValue($this->GetIDForIdent("HarmonyIdentity"), $uuid);
					}
				}
				elseif(strpos($xml, "identity")) // We got an identity response message
				{ 
					$content = $this->XMPP_getPayload($xml);
					$this->SendDebug("Logitech Harmony Hub","Hub Name: ".$content['friendlyName'].", identity = ".$content['identity']." - status = ".$content['status'],0); // Info/Query Stanza
					$identityVariableId = $this->GetIDForIdent("HarmonyIdentity");
					// Store Identity in String variable
					SetValue($identityVariableId , $content['identity']);
						
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
			if ($tag[2] == "bind")
			{ // This is not an OA message, we suppose it is a resource binding or identity request reply
				if (!strpos($xml, "<bind"))
					{
						$this->SendDebug("Logitech Harmony Hub","RECV: Unknown IQ Stanza",0); // Info/Query Stanza
					}
				else
					{ // STEP 5 - Binding Response (Continuation from Harmony_Read Script)
						preg_match('/<jid>(.*)<\/jid>/', $xml, $jid);
						$this->SendDebug("Logitech Harmony Hub","RECV: IQ Stanza resource binding result - JID: ".$jid[1],0); 
						// Replace 2 lines below by a proper function to get the User Auth Token Value (IPS Tools Library)
						$tokenVariableId = @GetIDForIdent("HarmonyUserAuthToken");
						if ($tokenVariableId === false)
							{
							$this->SendDebug("Logitech Harmony Hub","ERROR in processIQ(): User Auth Token not defined (after binding reponse).",0); 
							}
						else
							{
							$this->SendDebug("Logitech Harmony Hub","SEND: Sending Session Request",0); 
							XMPP_Session(); // Test: Request session
							IPS_Sleep(200);
							$inSessionVarId = @GetIDForIdent("HarmonyInSession");
							if ($inSessionVarId === false)
								{
								$this->SendDebug("Logitech Harmony Hub","ERROR in processIQ(): Session Auth Variable not found (before requesting Session token)",0); 
								}
							else
								{
								if (!GetValue($inSessionVarId))
									{ // We request the Session token only if we are authenticated as guest
									$this->SendDebug("Logitech Harmony Hub","SEND: Sending Session Token Request",0);
									$UserAuthToken = GetValue($tokenVariableId);
									$this->sendSessionTokenRequest();
									IPS_Sleep(500); // We need to wait to ensure that we receive the identity back from the server
									}
								}
							}	
					}
			} 
		}
		else 
		{ // There is no tag, we continue aggregationg the OA data
			
			$isAggregationEnd = false;
			//Konfig Auslesen
			$data = GetValue($configVariableId);
			str_replace(array("\\", "\""), array("", ""), $xml);
			if (!strpos($data, "</oa>"))
				{
					$data .= $xml;// continue aggregating until we get the closing OA tag
					if (!strpos($xml, "</oa>"))
						{
						$isAggregationEnd = true;
						$this->SendDebug("Logitech Harmony Hub","RECV: Aggregation of CDATA ended.",0);
						} 
					else
						{
						$this->SendDebug("Logitech Harmony Hub","RECV: Continuing CDATA aggregation...",0);
						}
				}			
		}
			
	}
	
		
	################## DATAPOINT RECEIVE FROM CHILD
	

	public function ForwardData($JSONString)
	{
	 
		// Empfangene Daten von der Device Instanz
		$data = json_decode($JSONString);
		$datasend = $data->Buffer;
		$DeviceID = $datasend->DeviceID;
		$Command = $datasend->Command;
		$BluetoothDevice = $datasend->BluetoothDevice;
		$commandoutobjid = @$this->GetIDForIdent("CommandOut");
		if($commandoutobjid > 0)
		{
			SetValueString($commandoutobjid, "DeviceID: ".$DeviceID.", Command: ".$Command.", BluetoothDevice: ".$BluetoothDevice);
		}
		$this->SendDebug("Logitech Harmony Hub","ForwardData HarmonyHub Splitter: DeviceID: ".$DeviceID.", Command: ".$Command.", BluetoothDevice: ".$BluetoothDevice,0);
			 
		// Hier würde man den Buffer im Normalfall verarbeiten
		// z.B. CRC prüfen, in Einzelteile zerlegen
		/*
		try
		{
			//
		}
		catch (Exception $ex)
		{
			echo $ex->getMessage();
			echo ' in '.$ex->getFile().' line: '.$ex->getLine().'.';
		}
		*/
		// Weiterleiten zur I/O Instanz
		//$resultat = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $data->Buffer))); //TX GUID
	 
		// Weiterverarbeiten und durchreichen
		//return $resultat;
		
		$this->sendcommand($DeviceID, $Command, $BluetoothDevice);
	 
	}
	
	public function RequestAction($Ident, $Value)
    {
        if($Ident == "HarmonyActivity")
		{
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
	* @return none
	**/
	public function sendSessionTokenRequest()
	{
		$token = GetValue($this->GetIDForIdent("HarmonyUserAuthToken"));
		$tokenString = $token.":name=foo#iOS6.0.1#iPhone"; // "token=".
		
		$this->XMPP_Send("<iq type='get' id='3174962747' from='guest'><oa xmlns='connect.logitech.com' mime='vnd.logitech.connect/vnd.logitech.pair'>token=".$tokenString."</oa></iq>");
	}
	
	/**
	* Sends request to get Harmony configuration to XMPP Server
	* The server will return the xml encoded config in a IQ/OA reply.
	*
	* @return none
	**/
	
	public function getConfig() {
		//$this->lockgetConfig = true;
		SetValue($this->GetIDForIdent("Configlock"), true);
		$this->XMPP_OpenStream();
		$iqString = "<iq type='get' id='2320426445' from='guest'>
		  <oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?config'>
		  </oa>
		</iq>";
		$config = $this->XMPP_Send($iqString);
		
	}
	
	/**
	* Opens the stream to XMPP Server
	*
	* @return none
	**/
	public function XMPP_OpenStream()
	{
	   $this->XMPP_Send("<stream:stream to='connect.logitech.com' xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client' xml:lang='en' version='1.0'>"); //  xmlns:xml="http://www.w3.org/XML/1998/namespace"
	}

	/**
	* Closes the stream to XMPP Server
	*
	* @return none
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
		if (!$parentactive)
		{
			IPS_SetProperty($instanceHarmonySocket, 'Open', true);
			IPS_ApplyChanges($instanceHarmonySocket);
		}	
		$this->Send($payload);	
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
	* @return none
	**/
	protected function XMPP_Auth($user, $password)
	{
		$this->SendDebug("Logitech Harmony Hub","Authenticating with ".$user." - ".$password,0);
		$pass = base64_encode("\x00" . $user . "\x00" . $password);
		$this->XMPP_Send("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>" . $pass . "</auth>");
	}

	/**
	* Sends Bind request to XMPP server
	*
	* @param string $resource A resource name
	*
	* @return none
	**/
	protected function XMPP_Bind($resource)
	{
		$this->SendDebug("Logitech Harmony Hub","Binding with resource ".$resource,0);
		$this->XMPP_Send("<iq type='set' id='bind_2'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>$resource</resource></bind></iq>");
	}

	/**
	* Sends Session request to XMPP server
	*
	* @return none
	**/
	protected function XMPP_Session()
	{
		$this->SendDebug("Logitech Harmony Hub","Sending Session request",0);
		$this->XMPP_Send("<iq id='bind_3' type='set'><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></iq>");
	}


	/**
	* Extracts CDATA payload from XMPP xml message
	*
	* @param string $xml
	*
	* @return array CDATA content formatted as 'type': Type of message, 'activityId', 'errorCode', 'errorString'
	* activityId	ID of the current activity.
	* activityStatus	0 = Hub is off, 1 = Activity is starting, 2 = Activity is started, 3 = Hub is turning off
	**/

	protected function XMPP_getPayload($xml)
	{
		preg_match('/type="[a-zA-Z\.]+\?(.*)">/', $xml, $type);  // type= "connect.stateDigest?notify" 
		if (!empty($type))
		{
			if(strpos($type[0], 'notify'))
			{
				$items['type'] = "notify";
				if(strpos($type[0], 'connect.stateDigest'))
				{
					$items['maintype'] = "state";
				}
				elseif(strpos($type[0], 'automation.state')) // message for HUE etc.
				{
					$items['maintype'] = "automation";
				}
			}
			else
			{
				$items['type'] = $type[1];
			}	
		}

		preg_match('/<!\[(CDATA)\[\s*(.*?)\s*\]\]>/', $xml, $cdata); // gibt CDATA aus
		preg_match('/^{(.*?)}/', $cdata[2], $cdatat); // Prüft auf {}
		$nojson = empty($cdatat);
		if (!$nojson)
			{
			$content = json_decode($cdata[2], true);
			foreach ($content as $key => $item)
			{
				$items[$key] = $item;
			}
		}
		else
		{
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
	* @return none
	**/
	public function getCurrentActivity()
	{
		$iqString = "<iq type='get' id='2320426445' from='".$this->from."'>
		  <oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?getCurrentActivity'>
		  </oa>
		</iq>";
		$this->XMPP_Send($iqString);
	}
	
	
	/**
	* Sends a request to the XMPP Server to get Infos (Firmware Version, Hub Name)
	*
	* @return none
	**/
	public function getDiscoveryInfo()
	{
		$iqString = "<iq type='get' id='2320426445' from='".$this->from."'>
		<oa xmlns='connect.logitech.com' mime='connect.discoveryinfo?get'>format=json</oa>	
		</iq>";
		$this->XMPP_Send($iqString);
	}
	
	

	/**
	* Sends request to send an IR command to XMPP Server
	* Device ID and Command name have to be retrieved from the config. No error check is made.
	*
	* @param $deviceID ID as retrieved from the Harmony config
	* @param $command command as retrieved from teh Harmony config
	*
	* @return none
	**/
	protected function sendcommand($DeviceID, $Command, $BluetoothDevice)
	{
		$inSessionVarId = $this->GetIDForIdent("HarmonyInSession");
		$insession = GetValue($inSessionVarId);
		if($insession == true)
		{
			if($BluetoothDevice == true)
			{
				$this->sendcommandAction($DeviceID, $Command);
			}
			else
			{
				$iqString = "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$DeviceID\",\"command\"::\"$Command\"}:status=press</oa></iq>";
				$this->XMPP_Send($iqString);
			}	
		}
		else // Open Stream
		{
			$this->XMPP_OpenStream();
			IPS_Sleep(500); // wait for auth success
			if($BluetoothDevice == true)
			{
				$this->sendcommandAction($DeviceID, $Command);
			}
			else
			{
				$iqString = "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$DeviceID\",\"command\"::\"$Command\"}:status=press</oa></iq>";
				$this->XMPP_Send($iqString);
			}	
		}	
		
	}

	/**
	* Sends request to send an IR command to XMPP Server
	* Device ID and Command name have to be retrieved from the config. No error check is made.
	*
	* @param $deviceID ID as retrieved from the Harmony config
	* @param $command command as retrieved from teh Harmony config
	*
	* @return none
	**/
	protected function sendcommandAction($deviceID, $command)
	{
	   $identityVariableId = $this->GetIDForIdent("HarmonyIdentity");
	   $identity = GetValue($identityVariableId);
	   if ($identity == "")
			{
			   $this->sendSessionTokenRequest();
			   IPS_Sleep(500);
			   $identity = GetValue($identityVariableId);
			} 
			
	   $iqString = "<iq id='7725179067' type='render' from='".$identity."'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>status=press:action={\"command\"::\"$command\",\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\"}:timestamp=0</oa></iq>";
		$this->SendDebug("Logitech Harmony Hub","Sending: ".$iqString,0);
	   $this->XMPP_Send($iqString);
	   IPS_Sleep(100);
	   $iqString = "<iq id='7725179067' type='render' from='".$identity."'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>status=release:action={\"command\"::\"$command\",\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\"}:timestamp=100</oa></iq>";
		$this->SendDebug("Logitech Harmony Hub","Sending: ".$iqString,0);
	   $this->XMPP_Send($iqString);
	}

	/**
	* Sends request to send an IR command to XMPP Server
	* Device ID and Command name have to be retrieved from the config. No error check is made.
	*
	* @param $deviceID ID as retrieved from the Harmony config
	* @param $command command as retrieved from the Harmony config
	*
	* @return none
	**/

	protected function sendcommandRender($deviceID, $command)
	{
	   $identityVariableId = $this->GetIDForIdent("HarmonyIdentity");
	   $identity = GetValue($identityVariableId);
	   if ($identity == "")
			{
			   $this->sendSessionTokenRequest();
			   IPS_Sleep(500);
			   $identity = GetValue($identityVariableId);
			} 
	   $iqString = "<iq id='4191874917' type='render' from='".$identity."'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\",\"command\"::\"$command\"}:status=press</oa></iq>";
		$this->SendDebug("Logitech Harmony Hub","Sending: ".$iqString,0);
		$this->XMPP_Send($iqString);
	}



	/**
	* Sends request to send an IR command to start a given activity to the XMPP Server
	* The Activity ID has to be retrieved from the config. No error check is made.
	*
	* @param $activityID ID as retrieved from the Harmony config
	*
	* timestamp A unix timestamp so the hub can identify the order of incoming activity triggering request
	* @return none
	**/
	public function startActivity(int $activityID)
	{
	   //$timestamp = time();
	   //$iqString = "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?startactivity'>activityId=".$activityID.":timestamp=".$timestamp."</oa></iq>";
	   $iqString = "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?startactivity'>activityId=".$activityID.":timestamp=0</oa></iq>";
	   $this->SendDebug("Logitech Harmony Hub","Sending: ".$iqString,0);
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

		if ($inSession) // Stream open auth ok
			{
			//XMPP_Auth('guest@x.com', 'guest');
			//$this->XMPP_Auth('guest@connect.logitech.com', 'gatorade.'); // Authenticate as guest
			$this->XMPP_Auth($identity.'@connect.logitech.com', $identity); // Authenticate as session
			//SetValue($inSessionVarId, false);
			SetValue($inSessionVarId, true);
			}
		else // Stream open no auth
			{
			$identity = GetValue($this->GetIDForIdent("HarmonyIdentity"));
			if ($identity == "")
				{
					$this->sendSessionTokenRequest();
				} 
			else
				{
					$this->XMPP_Auth($identity.'@connect.logitech.com', $identity); // Authenticate as session
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
		curl_setopt_array( $ch, $options );
		// Getting results
		$json_result =  curl_exec($ch);
		if ($json_result === FALSE) {
			die(curl_error($ch));
		}
		$result = json_decode($json_result);

		if(!curl_errno($ch))
		{
			// No Error
			if($result = '{"GetUserAuthTokenResult":null}')
			{
				$UserAuthToken = "";
			}
			else
			{
				$UserAuthToken = $result->GetUserAuthTokenResult->UserAuthToken;
			}
			SetValue($userauthtokenid, $UserAuthToken);
		}
		else
		{
			$this->SendDebug("Logitech Harmony Hub","Error: Authentification failed",0);
			$this->SendDebug("Logitech Harmony Hub","Error: Curl failed - " . curl_error($ch),0);
		}

		//print_r ($result);
		return $json_result;
		
	}
	
	//Installation Harmony Instanzen
	protected function SetupHarmonyInstance()
	{
  		$hubip = $this->ReadPropertyString('Host');
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline. 
		$json = $this->GetHarmonyConfigJSON();
		$activities[] = $json["activity"];
		$devices[] =  $json["device"];
		$hubname = GetValue($this->GetIDForIdent("HarmonyHubName"));
		$CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		//Prüfen ob Kategorie schon existiert
		$HubCategoryID = @IPS_GetObjectIDByIdent("CatLogitechHub_".$hubipident, $CategoryID);
		if ($HubCategoryID === false)
			{
				$HubCategoryID = IPS_CreateCategory();
				IPS_SetName($HubCategoryID, "Logitech ".$hubname." (".$hubip.")");
				IPS_SetIdent($HubCategoryID, "CatLogitechHub_".$hubipident); // Ident muss eindeutig sein
				IPS_SetInfo($HubCategoryID, $hubip);
				IPS_SetParent($HubCategoryID, $CategoryID);
			}		
		foreach ($devices as $harmonydevicelist)
		{
			$InsIDList = array();
			//$InsIDListID = 0;
			foreach ($harmonydevicelist as $harmonydevice)
			{
				$InstName = utf8_decode($harmonydevice["label"]); //Bezeichnung Harmony Device
				$deviceID = intval($harmonydevice["id"]); //DeviceID des Geräts
				if(isset($harmonydevice["BTAddress"]))
				{
					$BluetoothDevice = true;
				}
				else
				{
					$BluetoothDevice = false;
				}
				
				$InsID = $this->HarmonyDeviceCreateInstance($InstName, $HubCategoryID, $deviceID, $BluetoothDevice);
				$InsIDList[] = $InsID;
			}
			
		}
		//Variablen oder Skripte installieren
		$HarmonyVars = $this->ReadPropertyBoolean('HarmonyVars');
		$HarmonyScript = $this->ReadPropertyBoolean('HarmonyScript');
		if ($HarmonyVars == true)
		{
			$this->SetHarmonyInstanceVars($InsIDList, $HubCategoryID);
		}
		if($HarmonyScript == true)
		{
			$this->SetHarmonyInstanceScripts($InsIDList, $HubCategoryID);
		}
		
		//Harmony Aktivity Skripte setzten
		$this->SetupActivityScripts($HubCategoryID, $hubname);
		
	}
	
	protected function SetHarmonyInstanceVars($InsIDList, $HubCategoryID)
	{
		$json = $this->GetHarmonyConfigJSON();
		$activities[] = $json["activity"];
		$devices[] =  $json["device"];
		
		foreach ($devices as $harmonydevicelist)
		{
			$harmonydeviceid = 0;
			foreach ($harmonydevicelist as $harmonydevice)
			{
				$InstName = utf8_decode($harmonydevice["label"]); //Bezeichnung Harmony Device
								
				$controlGroups = $harmonydevice["controlGroup"];
				
				//Variablen anlegen
				$InsID = $InsIDList[$harmonydeviceid];
				foreach ($controlGroups as $controlGroup)
				{
					$commands = $controlGroup["function"]; //Function Array
					$profilemax = (count($commands))-1;
					$ProfileAssActivities = array();

					$assid = 0;
					$description = array();
					foreach ($commands as $command)
						{
							$harmonycommand = json_decode($command["action"], true); // command, type, deviceId
							//Wert , Name, Icon , Farbe
							$ProfileAssActivities[] = Array($assid, utf8_decode($harmonycommand["command"]),  "", -1);
							$description[$assid] = utf8_decode($harmonycommand["command"]);
							$assid++;
						}
					$descriptionjson = json_encode($description);
					$profiledevicename = str_replace(" ","",$harmonydevice["label"]);
  					$profiledevicename = preg_replace('/[^A-Za-z0-9\-]/', '', $profiledevicename); // Removes special chars.
  					$profilegroupname = str_replace(" ","",$controlGroup["name"]);
  					$profilegroupname = preg_replace('/[^A-Za-z0-9\-]/', '', $profilegroupname); // Removes special chars.
					//Variablenprofil anlegen
					$NumberAss = count($ProfileAssActivities);
					if ($NumberAss >= 32)//wenn mehr als 32 Assoziationen splitten
						{
						$splitProfileAssActivities = array_chunk($ProfileAssActivities, 32);
						$splitdescription = array_chunk($description, 32);
						//2. Array neu setzten
						$id = 0;
						$SecondProfileAssActivities = array();
						$seconddescription = array();
						foreach($splitProfileAssActivities[1] as $Activity)
							{
								$SecondProfileAssActivities[] = Array($id, $Activity[1],  "", -1);
								$seconddescription[] = $Activity[1];
								$id++;
							}
						
						//Association 1
						$this->RegisterProfileIntegerHarmonyAss("LogitechHarmony.".$profiledevicename.".".$profilegroupname , "Execute", "", "", 0 , 31, 0, 0, $splitProfileAssActivities[0]); //32 Associationen
						
						//Association 2
						//var_dump($SecondProfileAssActivities);
						$this->RegisterProfileIntegerHarmonyAss("LogitechHarmony.".$profiledevicename.".".$profilegroupname."1" , "Execute", "", "", 0 , ($profilemax-32), 0, 0, $SecondProfileAssActivities);
												
						$VarIdent1 = ($controlGroup["name"])."1";//Command Group Name
						$VarName1 = ($controlGroup["name"])."1";//Command Group Name
						$seconddescriptionjson = json_encode($seconddescription);
						$varid1 = LHD_SetupVariable($InsID, $VarIdent1, $VarName1, "LogitechHarmony.".$profiledevicename.".".$profilegroupname."1");
						IPS_SetInfo($varid1, $seconddescriptionjson);
						$firstdescriptionjson = json_encode($splitdescription[0]);
						}
						else
						{
							$this->RegisterProfileIntegerHarmonyAss("LogitechHarmony.".$profiledevicename.".".$profilegroupname , "Execute", "", "", 0 , $profilemax, 0, 0, $ProfileAssActivities);
						}
						$VarIdent = $controlGroup["name"];//Command Group Name
						$VarName = $controlGroup["name"];//Command Group Name
						$varid = LHD_SetupVariable($InsID, $VarIdent, $VarName, "LogitechHarmony.".$profiledevicename.".".$profilegroupname);
						if(count($description) > 32)
						{
							IPS_SetInfo($varid, $firstdescriptionjson);
						}
						else
						{
							IPS_SetInfo($varid, $descriptionjson);
						}	
				}
				$harmonydeviceid++;
			}
		}
	}
	
	protected function SetHarmonyInstanceScripts($InsIDList, $HubCategoryID)
	{
		$json = $this->GetHarmonyConfigJSON();
		$activities[] = $json["activity"];
		$devices[] =  $json["device"];

		foreach ($devices as $harmonydevicelist)
		{
			$harmonydeviceid = 0;
			foreach ($harmonydevicelist as $harmonydevice)
			{
				$InstName = utf8_decode($harmonydevice["label"]); //Bezeichnung Harmony Device
				$controlGroups = $harmonydevice["controlGroup"];

				//Kategorien anlegen
				$InsID = $InsIDList[$harmonydeviceid];
				//Prüfen ob Kategorie schon existiert
				$MainCatID = @IPS_GetObjectIDByIdent("Logitech_Device_Cat".$harmonydevice["id"], $HubCategoryID);
				if ($MainCatID === false)
				{
					$MainCatID = IPS_CreateCategory();
					IPS_SetName($MainCatID, utf8_decode($harmonydevice["label"]));
					IPS_SetInfo($MainCatID, $harmonydevice["id"]);
					IPS_SetIdent($MainCatID, "Logitech_Device_Cat".$harmonydevice["id"]);
					IPS_SetParent($MainCatID, $HubCategoryID);
				}
				
				foreach ($controlGroups as $controlGroup)
				{
					$commands = $controlGroup["function"]; //Function Array
					
					//Prüfen ob Kategorie schon existiert
					$CGID = @IPS_GetObjectIDByIdent("Logitech_Device_".$harmonydevice["id"]."_Controllgroup_".$controlGroup["name"], $MainCatID);
					if ($CGID === false)
					{
					$CGID = IPS_CreateCategory();
					IPS_SetName($CGID, $controlGroup["name"]);
					IPS_SetIdent($CGID, "Logitech_Device_".$harmonydevice["id"]."_Controllgroup_".$controlGroup["name"]);
					IPS_SetParent($CGID, $MainCatID);
					}

					$assid = 0;
					foreach ($commands as $command)
						{
							$harmonycommand = json_decode($command["action"], true); // command, type, deviceId
							//Prüfen ob Script schon existiert
							$Scriptname = $command["label"];
							$controllgroupident = $this->CreateIdent("Logitech_Device_".$harmonydevice["id"]."_Command_".$harmonycommand["command"]);
							$ScriptID = @IPS_GetObjectIDByIdent($controllgroupident, $CGID);
							if ($ScriptID === false)
							{
							   $ScriptID = IPS_CreateScript(0);
								IPS_SetName($ScriptID, $Scriptname);
								IPS_SetParent($ScriptID, $CGID);
								IPS_SetIdent($ScriptID, $controllgroupident);
								$content = "<? LHD_Send(".$InsID.", \"".$harmonycommand["command"]."\");?>";
								IPS_SetScriptContent($ScriptID, $content);
							}
							$assid++;
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
		$devices[] =  $json["device"];
		$currentactivities = array ();
		foreach ($devices as $harmonydevicelist)
		{
			foreach ($harmonydevicelist as $harmonydevice)
			{
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
		$currentactivities = array ();
		foreach ($activities as $activitieslist)
			{
			foreach ($activitieslist as $activity)
				{
				$label = $activity["label"];
				$id  = $activity["id"];
				$currentactivities[$label] = $id;
				}
			}
		return $currentactivities;
	}
	
	//Get JSON from Harmony Config
	public function GetHarmonyConfigJSON()
	{
		$jsonrawstring = GetValue($this->GetIDForIdent("HarmonyConfig"));
		$jsonstart = strpos($jsonrawstring, '![CDATA[');
		$jsonrawlength = strlen($jsonrawstring);
		$jsonend = strripos($jsonrawstring, "]]></oa></iq><iq/>");
		if($jsonend == false)
		{
			$jsonend = strripos($jsonrawstring, "]]></oa></iq>");
		}
		$jsonharmony = substr($jsonrawstring, ($jsonstart+8), ($jsonend-$jsonrawlength));
		$jsonharmony = str_replace("Ã¼", "ü", $jsonharmony);
		$jsonharmony = str_replace("Ã¤", "ä", $jsonharmony);
		$json = json_decode($jsonharmony, true);
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
		$HubCategoryID = @IPS_GetObjectIDByIdent("CatLogitechHub_".$hubipident, $CategoryID);
		if ($HubCategoryID === false)
			{
				$HubCategoryID = IPS_CreateCategory();
				IPS_SetName($HubCategoryID, "Logitech".$hubname);
				IPS_SetIdent($HubCategoryID, "CatLogitechHub_".$hubipident);
				IPS_SetInfo($HubCategoryID, $hubip);
				IPS_SetParent($HubCategoryID, $CategoryID);
			}	
		//Prüfen ob Instanz schon vorhanden
		$InstanzID = @IPS_GetObjectIDByIdent("Logitech_Harmony_Hub_".$hubipident, $HubCategoryID);
		if ($InstanzID === false)
			{
				$InsID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
				IPS_SetName($InsID, "Logitech Harmony Hub"); // Instanz benennen
				IPS_SetIdent($InsID, "Logitech_Harmony_Hub_".$hubipident);
				IPS_SetParent($InsID, $HubCategoryID); // Instanz einsortieren unter dem Objekt mit der ID "$HubCategoryID"

				// Anlegen eines neuen Links für Harmony Aktivity
				$LinkID = IPS_CreateLink();             // Link anlegen
				IPS_SetName($LinkID, "Logitech Harmony Hub Activity"); // Link benennen
				IPS_SetParent($LinkID, $InsID); // Link einsortieren 
				IPS_SetLinkTargetID($LinkID, $this->GetIDForIdent("HarmonyActivity"));    // Link verknüpfen
			}	
	}
	
	//Create Harmony Device Instance 
	protected function HarmonyDeviceCreateInstance(string $InstName, int $CategoryID, int $deviceID, bool $BluetoothDevice)
	{
		
		//Prüfen ob Instanz schon existiert
		$InstanzID = @IPS_GetObjectIDByIdent("Device_".$deviceID, $CategoryID);
		if ($InstanzID === false)
			{
				//Neue Instanz anlegen
				$InsID = IPS_CreateInstance("{B0B4D0C2-192E-4669-A624-5D5E72DBB555}");
				$InstName = (string)$InstName;
				IPS_SetName($InsID, $InstName); // Instanz benennen
				IPS_SetIdent($InsID, "Device_".$deviceID);
				IPS_SetParent($InsID, $CategoryID); // Instanz einsortieren unter dem Objekt mit der ID "$CategoryID"
				//$DeviceID setzten
				IPS_SetProperty($InsID, "Name", $InstName); //Name setzten.
				IPS_SetProperty($InsID, "DeviceID", $deviceID); //DeviceID setzten.
				IPS_SetProperty($InsID, "BluetoothDevice", $BluetoothDevice); //Bluetooth Device setzten.
				IPS_ApplyChanges($InsID); //Neue Konfiguration übernehmen
				$this->SendDebug("Logitech Harmony Hub","Logitech Instanz Name: ".$InstName." erstellt",0);
				return $InsID;
			}

		else
			{
				return $InstanzID;
			}
		
	}
	
	//Profile
	protected function RegisterProfileIntegerHarmony($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
        
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
        
    }
	
	protected function RegisterProfileIntegerHarmonyAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Associations)
	{
        if ( sizeof($Associations) === 0 ){
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
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }	
	//-- Harmony API
	
	//Configuration Form
	public function GetConfigurationForm()
	{
		$alexashsobjid = $this->GetAlexaSmartHomeSkill();
		$formhead = $this->FormHead();
		$formselection = $this->FormSelection();
		$formactions = $this->FormActions();
		$formelementsend = '{ "type": "Label", "label": "__________________________________________________________________________________________________" }';
		$formstatus = $this->FormStatus();
			
		if($alexashsobjid > 0)
		{
			return	'{ '.$formhead.$formselection.$formelementsend.'],'.$formactions.$formstatus.' }';
		}
		else
		{
			return	'{ '.$formhead.$formelementsend.'],'.$formactions.$formstatus.' }';
		}	
	}
				
	protected function FormSelection()
	{			 
		$AlexaSmartHomeSkill = $this->GetAlexaSmartHomeSkill();
		if($AlexaSmartHomeSkill == false)
		{
			$form = '';
		}
		else
		{
			$form = '{ "type": "Label", "label": "Alexa Smart Home Skill is available in IP-Symcon"},
				{ "type": "Label", "label": "Would you like to create links to the Harmony actions scripts for Alexa in the SmartHomeSkill (IQL4SmartHome) instance?" },
				{ "type": "CheckBox", "name": "Alexa", "caption": "Create links for Amazon Echo / Dot" },';
		}	
		
		return $form;
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
				{ "type": "Label", "label": "IP Adresse des Harmony Hub" },
                {
                    "name": "Host",
                    "type": "ValidationTextBox",
                    "caption": "IP adress"
                },
				{ "type": "Label", "label": "MyHarmony Zugangsdaten (Email/Passwort)" },
                {
                    "name": "Email",
                    "type": "ValidationTextBox",
                    "caption": "Email"
                },
				{
                    "name": "Password",
                    "type": "PasswordTextBox",
                    "caption": "Password"
                },
				{ "type": "Label", "label": "category for Logitech Harmony Hub devices" },
				{ "type": "SelectCategory", "name": "ImportCategoryID", "caption": "Harmony Hub devices" },
				{ "type": "Label", "label": "Create Harmony devices for remote control:" },
				{ "type": "Label", "label": "Create variables for webfront (Please note: High numbers of variables)" },
				{
                    "name": "HarmonyVars",
                    "type": "CheckBox",
                    "caption": "Harmony variables"
                },
				{ "type": "Label", "label": "create scripts for remote control (alternative or addition for remote control via webfront):" },
				{
                    "name": "HarmonyScript",
                    "type": "CheckBox",
                    "caption": "Harmony script"
                },';
			
		return $form;
	}
		
	protected function FormActions()
	{
		$form = '"actions":
			[
				{ "type": "Label", "label": "1. Read Logitech Harmony Hub configuration:" },
				{ "type": "Button", "label": "Read configuration", "onClick": "HarmonyHub_getConfig($id);" },
				{ "type": "Label", "label": "2. Create devices after reading the Logitech Harmony Hub configuration:" },
				{ "type": "Button", "label": "Setup Harmony", "onClick": "HarmonyHub_SetupHarmony($id);" },
				{ "type": "Label", "label": "reload firmware version and Logitech Harmony Hub name:" },
				{ "type": "Button", "label": "update Harmony info", "onClick": "HarmonyHub_getDiscoveryInfo($id);" }
			],';
		return  $form;
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
		
	protected function GetAlexaSmartHomeSkill()
	{
		$InstanzenListe = IPS_GetInstanceListByModuleID("{3F0154A4-AC42-464A-9E9A-6818D775EFC4}"); // IQL4SmartHome
		$IQL4SmartHomeID = @$InstanzenListe[0];
		if(!$IQL4SmartHomeID > 0)
		{
			$IQL4SmartHomeID = false;
		}
		return $IQL4SmartHomeID;
	}

	protected function CreateAlexaLinks()
		{
			$hubip = $this->ReadPropertyString('Host');
			$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline. 
			$IQL4SmartHomeID = $this->GetAlexaSmartHomeSkill();
			//Prüfen ob Kategorie schon existiert
			$AlexaCategoryID = @IPS_GetObjectIDByIdent("AlexaLogitechHarmony", $IQL4SmartHomeID);
			if ($AlexaCategoryID === false)
				{
					$AlexaCategoryID = IPS_CreateCategory();
					IPS_SetName($AlexaCategoryID, "Logitech Harmony Hub");
					IPS_SetIdent($AlexaCategoryID, "AlexaLogitechHarmony");
					IPS_SetInfo($AlexaCategoryID, "Aktivitäten des Logitech Harmony Hubs schalten");
					IPS_SetParent($AlexaCategoryID, $IQL4SmartHomeID);
				}
			//Prüfen ob Unterkategorie schon existiert
			$SubAlexaCategoryID = @IPS_GetObjectIDByIdent("AlexaLogitechHarmony_Hub_".$hubipident, $AlexaCategoryID);
			if ($SubAlexaCategoryID === false)
				{
					$SubAlexaCategoryID = IPS_CreateCategory();
					IPS_SetName($SubAlexaCategoryID, "Logitech Harmony Hub (".$hubip.")");
					IPS_SetIdent($SubAlexaCategoryID, "AlexaLogitechHarmony_Hub_".$hubipident);
					IPS_SetInfo($SubAlexaCategoryID, "Aktivitäten des Logitech Harmony Hubs (".$hubip.") schalten");
					IPS_SetParent($SubAlexaCategoryID, $AlexaCategoryID);
				}
			//Prüfen ob Link schon vorhanden
			$linkobjids = $this->GetLinkObjIDs();
			
			foreach ($linkobjids as $linkobjid)
			{
				$objectinfo = IPS_GetObject($linkobjid);
				$ident = $objectinfo["ObjectIdent"];
				$name = $objectinfo["ObjectName"];
				$LinkID = @IPS_GetObjectIDByIdent("AlexaLink_".$ident, $SubAlexaCategoryID);
				if ($LinkID === false)
				{
					// Anlegen eines neuen Links für die Aktivität
					$LinkID = IPS_CreateLink();             // Link anlegen
					IPS_SetIdent($LinkID, "AlexaLink_".$ident); //ident
					IPS_SetLinkTargetID($LinkID, $linkobjid);    // Link verknüpfen
					IPS_SetInfo($LinkID, "Harmony Hub Aktivität ".$name);
					IPS_SetParent($LinkID, $SubAlexaCategoryID); // Link einsortieren
					IPS_SetName($LinkID, $name); // Link benennen					
				}	
			
			}
		}
			
	protected function DeleteAlexaLinks()
		{
			$hubip = $this->ReadPropertyString('Host');
			$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline. 
			$IQL4SmartHomeID = $this->GetAlexaSmartHomeSkill();
			$AlexaCategoryID = @IPS_GetObjectIDByIdent("AlexaLogitechHarmony", $IQL4SmartHomeID);
			$SubAlexaCategoryID = @IPS_GetObjectIDByIdent("AlexaLogitechHarmony_Hub_".$hubipident, $AlexaCategoryID);
			$linkobjids = $this->GetLinkObjIDs();
			
			foreach ($linkobjids as $linkobjid)
			{
				$objectinfo = IPS_GetObject($linkobjid);
				$ident = $objectinfo["ObjectIdent"];
				$name = $objectinfo["ObjectName"];
				$LinkID = @IPS_GetObjectIDByIdent("AlexaLink_".$ident, $SubAlexaCategoryID);
				if($LinkID > 0)
				{
					IPS_DeleteLink($LinkID);
				}
			}
						
			
			if($SubAlexaCategoryID > 0)
			{
				$catempty = $this->ScreenCategory($SubAlexaCategoryID);
				if($catempty == true)
				{
					IPS_DeleteCategory($SubAlexaCategoryID);
				}
			}
			
			if($AlexaCategoryID > 0)
			{
				$catempty = $this->ScreenCategory($AlexaCategoryID);
				if($catempty == true)
				{
					IPS_DeleteCategory($AlexaCategoryID);
				}
			}
		}
	
	protected function GetLinkObjIDs()
	{
		$linkobjids = false;
		$hubip = $this->ReadPropertyString('Host');
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline. 
		$CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		$HubCategoryID = @IPS_GetObjectIDByIdent("CatLogitechHub_".$hubipident, $CategoryID);
		$MainCatID = @IPS_GetObjectIDByIdent("LogitechActivitiesScripts_".$hubipident, $HubCategoryID);
		$linkobjids = IPS_GetChildrenIDs($MainCatID);
		return $linkobjids;
	}
	
	protected function ScreenCategory($CategoryID)
	{
		$catempty = IPS_GetChildrenIDs($CategoryID);
		if(empty($catempty))
		{
			$catempty = true;
		}
		else
		{
			$catempty = false;
		}	
		return $catempty;
	}		
	
	
}

?>
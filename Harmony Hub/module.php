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
        $this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}", "Logitech Harmony Hub");

        $this->RegisterPropertyString("Host", "");
		$this->RegisterPropertyInteger("Port", 5222);
        $this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("Email", "");
		$this->RegisterPropertyString("Password", "");
		$this->RegisterPropertyInteger("ImportCategoryID", 0);
		$this->RegisterPropertyBoolean("Debug", false);
		$this->RegisterPropertyBoolean("HarmonyVars", false);
		$this->RegisterPropertyBoolean("HarmonyScript", false);
		
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
		$debug = $this->ReadPropertyBoolean('Debug');
		$change = false;
		
		if($debug)
		{
			$this->RegisterVariableString("CommandOut", "CommandOut", "", 2);
			$this->RegisterVariableString("IOIN", "IOIN", "", 3);
			IPS_SetHidden($this->GetIDForIdent('CommandOut'), true);
			IPS_SetHidden($this->GetIDForIdent('IOIN'), true);
		}
		
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
		//umgestellt auf IPS Var
				
		//Datei überprüfen	
		/*
		if (!file_exists($this->configFilePath()))
			{
				//Harmony_Config.txt erstellen
				$data = "";
				$configFileHandle = fopen($this->configFilePath(), "w");
				fwrite($configFileHandle, $data);
				fclose($configFileHandle);	
			}
		*/	
		//Konfig prüfen
		/*
		$filesize = filesize($this->configFilePath());
		if ($filesize == 0 && $change == true) //wenn kein Inhalt Konfig abrufen
		{
			$this->lockgetConfig = true;
			$this->getConfig();
		}
		*/	
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
		$this->RegisterVariableInteger("HarmonyActivity", "Harmony Activity", "LogitechHarmony.Activity", 5);
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
	public function SendTest($Text)
	{	 
		// Weiterleitung zu allen Gerät-/Device-Instanzen
		IPS_LogMessage("HarmonyHub Splitter Test", $Text);
		$this->SendDataToChildren(json_encode(Array("DataID" => "{7924862A-0EEA-46B9-B431-97A3108BA380}", "Buffer" => $Text))); //Harmony Splitter Interface GUI
	}
	
	// Data an Child weitergeben
	public function ReceiveData($JSONString)
	{
	 
		// Empfangene Daten vom I/O
		$data = json_decode($JSONString);
		$dataio = $data->Buffer;
		$debug = $this->ReadPropertyBoolean('Debug');
		if($debug)
		{
		SetValueString($this->GetIDForIdent("IOIN"), $dataio);
		}
		//IPS_LogMessage("ReceiveData HarmonyHub", utf8_decode($data->Buffer));
		
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
		//if (strpos($databuffer, '</iq>') && ($this->lockgetConfig == true))
		if (strpos($databuffer, '</iq>') && ($configlock == true))	
		{
			//Daten komplett, weiterreichen
			SetValueString($this->GetIDForIdent("HarmonyConfig"), $databuffer);
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			$bufferdelete = true;
			//$this->lockgetConfig = false;
			SetValue($this->GetIDForIdent("Configlock"), false);
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, $tag);
			
		}
		elseif ($tag == 'message')
		{
			SetValueString($this->GetIDForIdent("BufferIN"), "");
			//$this->BufferHarmonyIn = "";
			$bufferdelete = true;
			//Daten zur Auswertung übergeben
			$this->ReadPayload($databuffer, $tag);
		}
		elseif (strpos($databuffer, 'stream:stream'))
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
		$debug = $this->ReadPropertyBoolean('Debug');
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
					IPS_LogMessage("Logitech Harmony Hub", "Activity ". $ActivityName." started und finished");	
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
							IPS_LogMessage("Logitech Harmony Hub", "Activity ". $ActivityName." is started");
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
				break;
			case 'stream':
				if ($debug) IPS_LogMessage("HARMONY XMPP", "RECV: STREAM Confirmation received\r\n");
				preg_match('/id=\'([a-zA-Z0-9-_]+)\'\s/', $payload, $id);
				IPS_LogMessage("HARMONY XMPP", " -> id: ".$id[1]);
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
			   if ($debug) IPS_LogMessage("Logitech Harmony Hub", "RECV: Authentication SUCCESS\r\n");
			   
			   //$this->XMPP_OpenStream(); // Step 3 - Open  new stream for binding
			   break;
			case 'failure':
			   if ($debug) IPS_LogMessage("HARMONY XMPP", "RECV: Authentication FAILED\r\n");
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
		$debug = $this->ReadPropertyBoolean('Debug');						
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
						SetValue($this->GetIDForIdent("FirmwareVersion"), $FirmwareVersion);
						SetValue($this->GetIDForIdent("HarmonyHubName"), $HarmonyHubName);
						SetValue($this->GetIDForIdent("HarmonyIdentity"), $uuid);
					}
				}
				elseif(strpos($xml, "identity")) // We got an identity response message
				{ 
					$content = $this->XMPP_getPayload($xml); 
					if ($debug) IPS_LogMessage("Logitech Harmony Hub", "Hub Name: ".$content['friendlyName'].", identity = ".$content['identity']." - status = ".$content['status']."\r\n"); // Info/Query Stanza
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
						if ($debug) IPS_LogMessage("HARMONY XMPP", "RECV: Unknown IQ Stanza\r\n"); // Info/Query Stanza
					}
				else
					{ // STEP 5 - Binding Response (Continuation from Harmony_Read Script)
						preg_match('/<jid>(.*)<\/jid>/', $xml, $jid);
						if ($debug) IPS_LogMessage("HARMONY XMPP", "RECV: IQ Stanza resource binding result - JID: ".$jid[1]."\r\n");
						// Replace 2 lines below by a proper function to get the User Auth Token Value (IPS Tools Library)
						$tokenVariableId = @GetIDForIdent("HarmonyUserAuthToken");
						if ($tokenVariableId === false)
							{
							IPS_LogMessage("HARMONY XMPP", "ERROR in processIQ(): User Auth Token not defined (after binding reponse).");
							}
						else
							{
							if ($debug) IPS_LogMessage("HARMONY XMPP", "SEND: Sending Session Request");
							XMPP_Session(); // Test: Request session
							IPS_Sleep(200);
							$inSessionVarId = @GetIDForIdent("HarmonyInSession");
							if ($inSessionVarId === false)
								{
								IPS_LogMessage("HARMONY XMPP", "ERROR in processIQ(): Session Auth Variable not found (before requesting Session token)");
								}
							else
								{
								if (!GetValue($inSessionVarId))
									{ // We request the Session token only if we are authenticated as guest
									if ($debug) IPS_LogMessage("HARMONY XMPP", "SEND: Sending Session Token Request");
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
						if ($debug) IPS_LogMessage("HARMONY XMPP", "RECV: Aggregation of CDATA ended.\r\n");
						} 
					else
						{
						if ($debug) IPS_LogMessage("HARMONY XMPP", "RECV: Continuing CDATA aggregation...\r\n");
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
		SetValueString($this->GetIDForIdent("CommandOut"), "DeviceID: ".$DeviceID.", Command: ".$Command.", BluetoothDevice: ".$BluetoothDevice);
		IPS_LogMessage("ForwardData HarmonyHub Splitter", "DeviceID: ".$DeviceID.", Command: ".$Command.", BluetoothDevice: ".$BluetoothDevice);
	 
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
	
	
	public function Send($payload)
		{
			$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $payload)));
		}
			
	
	//UserAuthToken abholen falls nicht vorhanden
	protected function RegisterUser($email, $password, $userauthtokenid)
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
			$UserAuthToken = $result->GetUserAuthTokenResult->UserAuthToken;
			SetValue($userauthtokenid, $UserAuthToken);
		}
		else
		{
			echo "	ERROR: Curl failed - " . curl_error($ch);
			IPS_LogMessage("Logitech Harmony Hub", "Error: Authentification failed");
		}

		//print_r ($result);
		
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
		if ($this->ReadPropertyBoolean('Debug')) IPS_LogMessage("HARMONY XMPP", "DEBUG: Authenticating with $user - $password");
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
		if ($this->ReadPropertyBoolean('Debug')) IPS_LogMessage("HARMONY XMPP", "DEBUG: Binding with resource $resource");
		$this->XMPP_Send("<iq type='set' id='bind_2'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>$resource</resource></bind></iq>");
	}

	/**
	* Sends Session request to XMPP server
	*
	* @return none
	**/
	protected function XMPP_Session()
	{
		if ($this->ReadPropertyBoolean('Debug')) IPS_LogMessage("HARMONY XMPP", "DEBUG: Sending Session request");
		$this->XMPP_Send("<iq id='bind_3' type='set'><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></iq>");
	}

	
	/**
	* Sends XMPP message to XMPP server
	* If the socket is closed, adds the command to a queue array for subsequent execution.
	*
	* @param string $payload
	*
	* @return boolean true if success
	**/
	protected function XMPP_SendAction($payload)
	{
		// ! ÜBERARBEITEN
		$instanceHarmonySocket = $this->GetParent();
		// Open the socket if it is disconnected
		if ($instanceHarmonySocket['InstanceStatus'] != 102)
			{
			CSCK_SetOpen($instanceHarmonySocket, true);
			IPS_ApplyChanges($instanceHarmonySocket);
			}
			$result = CSCK_SendText($instanceHarmonySocket, $payload);
			if ($result) {
				if ($this->ReadPropertyBoolean('Debug')) {
					echo "SUCCESS message sent\r\n";
					return true;
				}
			} else {
				IPS_LogMessage("HARMONY XMPP", "ERROR in XMPP_Send(): Sending XMPP message failed (Socket was connected).");
				echo "ERROR sending message\r\n";
				return false;
			}
		// Open the socket after the command is sent if it is disconnected
		if ($instanceHarmonySocket['InstanceStatus'] != 102) {
			CSCK_SetOpen($instanceHarmonySocket, true);
			IPS_ApplyChanges($instanceHarmonySocket);
		}
		// The Code below is an attempt to implement a command queue in case the socket is not connected - Work in Progress (05/01/2014)
		// REMOVED
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
	   $identityVariableId = IPS_GetVariableIDByName("HarmonyIdentity", $parentID);
	   $identity = GetValue($identityVariableId);
	   if ($identity == "")
			{
			   $this->sendSessionTokenRequest();
			   IPS-SLeep(500);
			   $identity = GetValue($identityVariableId);
			} 
			
	   $iqString = "<iq id='7725179067' type='render' from='".$identity."'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>status=press:action={\"command\"::\"$command\",\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\"}:timestamp=0</oa></iq>";
	   $this->XMPP_Send($iqString);
	   $iqString = "<iq id='7725179067' type='render' from='".$identity."'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>status=release:action={\"command\"::\"$command\",\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\"}:timestamp=12</oa></iq>";
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
	   $identityVariableId = IPS_GetVariableIDByName("HarmonyIdentity", $parentID);
	   $identity = GetValue($identityVariableId);
	   if ($identity == "")
			{
			   $this->sendSessionTokenRequest();
			   IPS-SLeep(500);
			   $identity = GetValue($identityVariableId);
			} 
	   $iqString = "<iq id='4191874917' type='render' from='".$identity."'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?holdAction'>action={\"type\"::\"IRCommand\",\"deviceId\"::\"$deviceID\",\"command\"::\"$command\"}:status=press</oa></iq>";
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
	public function startActivity(integer $activityID)
	{
	   //$timestamp = time();
	   //$iqString = "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?startactivity'>activityId=".$activityID.":timestamp=".$timestamp."</oa></iq>";
	   $iqString = "<iq type='get' id='5e518d07-bcc2-4634-ba3d-c20f338d8927-2'><oa xmlns='connect.logitech.com' mime='vnd.logitech.harmony/vnd.logitech.harmony.engine?startactivity'>activityId=".$activityID.":timestamp=0</oa></iq>";
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

		if ($inSession)
			{
			//XMPP_Auth('guest@x.com', 'guest');
			$this->XMPP_Auth('guest@connect.logitech.com', 'gatorade.'); // Authenticate as guest
			SetValue($inSessionVarId, false);
			}
		else
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

	
	
	//Installation Harmony Instanzen
	protected function SetupHarmonyInstance()
	{
  		$debug = $this->ReadPropertyBoolean('Debug');

		$json = $this->GetHarmonyConfigJSON();
		$activities[] = $json["activity"];
		$devices[] =  $json["device"];

		foreach ($devices as $harmonydevicelist)
		{
			$InsIDList = array();
			//$InsIDListID = 0;
			foreach ($harmonydevicelist as $harmonydevice)
			{
				$InstName = $harmonydevice["label"]; //Bezeichnung Harmony Device
				$CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
				$deviceID = intval($harmonydevice["id"]); //DeviceID des Geräts
				if(isset($harmonydevice["BTAddress"]))
				{
					$BluetoothDevice = true;
				}
				else
				{
					$BluetoothDevice = false;
				}
				
				$InsID = $this->HarmonyDeviceCreateInstance($InstName, $CategoryID, $deviceID, $BluetoothDevice);
				$InsIDList[] = $InsID;
			}
			
		}
		//Variablen oder Skripte installieren
		$HarmonyVars = $this->ReadPropertyBoolean('HarmonyVars');
		$HarmonyScript = $this->ReadPropertyBoolean('HarmonyScript');
		if ($HarmonyVars == true)
		{
			$this->SetHarmonyInstanceVars($InsIDList);
		}
		if($HarmonyScript == true)
		{
			$this->SetHarmonyInstanceScripts($InsIDList);
		}
		
	}
	
	protected function SetHarmonyInstanceVars($InsIDList)
	{
		$debug = $this->ReadPropertyBoolean('Debug');

		$json = $this->GetHarmonyConfigJSON();
		$activities[] = $json["activity"];
		$devices[] =  $json["device"];
		
		foreach ($devices as $harmonydevicelist)
		{
			$harmonydeviceid = 0;
			foreach ($harmonydevicelist as $harmonydevice)
			{
				$InstName = $harmonydevice["label"]; //Bezeichnung Harmony Device
				$CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
				
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
	
	protected function SetHarmonyInstanceScripts($InsIDList)
	{
		$debug = $this->ReadPropertyBoolean('Debug');

		$json = $this->GetHarmonyConfigJSON();
		$activities[] = $json["activity"];
		$devices[] =  $json["device"];

		foreach ($devices as $harmonydevicelist)
		{
			$harmonydeviceid = 0;
			foreach ($harmonydevicelist as $harmonydevice)
			{
				$InstName = $harmonydevice["label"]; //Bezeichnung Harmony Device
				$CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
				$controlGroups = $harmonydevice["controlGroup"];

				//Kategorien anlegen
				$InsID = $InsIDList[$harmonydeviceid];
				//Prüfen ob Kategorie schon existiert
				$MainCatID = @IPS_GetCategoryIDByName($InstName, $CategoryID);
				if ($MainCatID === false)
				{
					$MainCatID = IPS_CreateCategory();
					IPS_SetName($MainCatID, $harmonydevice["label"]);
					IPS_SetInfo($MainCatID, $harmonydevice["id"]);
					IPS_SetParent($MainCatID, $CategoryID);
				}
				
				foreach ($controlGroups as $controlGroup)
				{
					$commands = $controlGroup["function"]; //Function Array
					
					//Prüfen ob Kategorie schon existiert
					$CGID = @IPS_GetCategoryIDByName($controlGroup["name"], $MainCatID);
					if ($CGID === false)
					{
					$CGID = IPS_CreateCategory();
					IPS_SetName($CGID, $controlGroup["name"]);
					IPS_SetParent($CGID, $MainCatID);
					}

					$assid = 0;
					foreach ($commands as $command)
						{
							$harmonycommand = json_decode($command["action"], true); // command, type, deviceId
							//Prüfen ob Script schon existiert
							$Scriptname = $command["label"];
							$ScriptID = @IPS_GetScriptIDByName($Scriptname, $CGID);
							if ($ScriptID === false)
							{
							   $ScriptID = IPS_CreateScript(0);
								IPS_SetName($ScriptID, $Scriptname);
								IPS_SetParent($ScriptID, $CGID);
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
		$jsonend = strripos($jsonrawstring, "]]></oa></iq>");
		$jsonharmony = substr($jsonrawstring, ($jsonstart+8), ($jsonend-$jsonrawlength));
		$json = json_decode($jsonharmony, true);
		return $json;
	}
	
	//Link für Harmony Activity anlegen
	public function CreateAktivityLink()
	{
		$CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		//Prüfen ob Instanz schon vorhanden
		$InstanzID = @IPS_GetInstanceIDByName("Logitech Harmony Hub", $CategoryID);
		if ($InstanzID === false)
			{
				$InsID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
				IPS_SetName($InsID, "Logitech Harmony Hub"); // Instanz benennen
				IPS_SetParent($InsID, $CategoryID); // Instanz einsortieren unter dem Objekt mit der ID "$CategoryID"

				// Anlegen eines neuen Links für Harmony Aktivity
				$LinkID = IPS_CreateLink();             // Link anlegen
				IPS_SetName($LinkID, "Logitech Harmony Hub Activity"); // Link benennen
				IPS_SetParent($LinkID, $InsID); // Link einsortieren 
				IPS_SetLinkTargetID($LinkID, $this->GetIDForIdent("HarmonyActivity"));    // Link verknüpfen
			}	
	}
	
	//Create Harmony Device Instance 
	protected function HarmonyDeviceCreateInstance(string $InstName, integer $CategoryID, integer $deviceID, boolean $BluetoothDevice)
	{
		
		//Prüfen ob Instanz schon existiert
		$InstanzID = @IPS_GetInstanceIDByName($InstName, $CategoryID);
		if ($InstanzID === false)
			{
				//Neue Instanz anlegen
				$InsID = IPS_CreateInstance("{B0B4D0C2-192E-4669-A624-5D5E72DBB555}");
				$InstName = (string)$InstName;
				IPS_SetName($InsID, $InstName); // Instanz benennen
				//IPS_SetInfo($InsID, $devices["id"]);
				//IPS_SetInfo($InsID, $devices['deviceTypeDisplayName']);
				//IPS_SetIcon($NeueInstance, $Quellobjekt['ObjectIcon']);
				//IPS_SetPosition($NeueInstance, $Quellobjekt['ObjectPosition']);
				//IPS_SetHidden($NeueInstance, $Quellobjekt['ObjectIsHidden']);
				//IPS_SetIdent($NeueInstance, $Quellobjekt['ObjectIdent']);
				IPS_SetParent($InsID, $CategoryID); // Instanz einsortieren unter dem Objekt mit der ID "$CategoryID"
				//$DeviceID setzten
				IPS_SetProperty($InsID, "Name", $InstName); //Name setzten.
				IPS_SetProperty($InsID, "DeviceID", $deviceID); //DeviceID setzten.
				IPS_SetProperty($InsID, "BluetoothDevice", $BluetoothDevice); //Bluetooth Device setzten.
				IPS_ApplyChanges($InsID); //Neue Konfiguration übernehmen
				IPS_LogMessage( "Logitech Harmony Hub" , "Logitech Instanz Name: ".$InstName." erstellt" );
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
        
		//boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }	
	
	
}

?>
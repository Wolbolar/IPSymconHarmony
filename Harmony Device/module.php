<?

class HarmonyDevice extends IPSModule
{

   
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        // 1. Verfügbarer HarmonySplitter wird verbunden oder neu erzeugt, wenn nicht vorhanden.
        $this->ConnectParent("{03B162DB-7A3A-41AE-A676-2444F16EBEDF}");
		
		$this->RegisterPropertyString("Name", "");
		$this->RegisterPropertyInteger("DeviceID", 0);
		$this->RegisterPropertyBoolean("BluetoothDevice", false);		
		$this->RegisterPropertyBoolean("VolumeControl", false);
		$this->RegisterPropertyInteger("MaxStepVolume", 0);
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
    * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
    *
    */
	private function ValidateConfiguration()
	{			
		//Type und Zone
		$Name = $this->ReadPropertyString('Name');
		$DeviceID = $this->ReadPropertyInteger('DeviceID');
		$VolumeControl = $this->ReadPropertyBoolean('VolumeControl');
		$MaxStepVolume = $this->ReadPropertyInteger('MaxStepVolume');
		if ($VolumeControl)
		{
			if($MaxStepVolume == 0)
			{
				$this->SetStatus(201);
			}
			if($MaxStepVolume > 0)
			{
				$this->RegisterVariableFloat("VolumeSlider", "Volume", "~Intensity.1", 1);
				$this->EnableAction("VolumeSlider");
			}
		}
				
		//Auswahl Prüfen
		if ($Name !== "" && $DeviceID !== "")
			{
				$this->SetStatus(102);	
			}
	}
	
		
	public function RequestAction(string $Ident, $Value)
    {
		$ObjID = $this->GetIDForIdent($Ident);
		$Object = IPS_GetObject($ObjID);
		$ObjectInfo = $Object["ObjectInfo"];
		$commands = json_decode($ObjectInfo, true);
		$command = $commands[$Value];	
		if($Ident == "VolumeSlider")
		{
			$this->SetVolumeSlider($Value);
		}
		else
		{
			$this->Send($command);
		}
		SetValue($ObjID, $Value);
    }

	
	public function SetVolumeSlider(float $Value)
	{
		$MaxStepVolume = $this->ReadPropertyInteger('MaxStepVolume');
		$this->SendDebug("Logitech Hub","Max Step Volume: ".print_r($MaxStepVolume,true),0);
		$CurrentVolume = GetValue($this->GetIDForIdent("VolumeSlider"));
		$this->SendDebug("Logitech Hub","Current Volume: ".print_r($CurrentVolume,true),0);
		$TargetVolume = round($Value*$MaxStepVolume);
		$this->SendDebug("Logitech Hub","Target Volume: ".print_r($Value,true),0);
		$this->SendDebug("Logitech Hub","Steps to Target Volume: ".print_r($TargetVolume,true),0);
        $commandrepeat = 0;
        $command = "Unknown";
		if ($Value > $CurrentVolume)
		{
			$command = "VolumeUp";
			$commandrepeat = $TargetVolume - ($CurrentVolume * $MaxStepVolume);
		}
		elseif ($Value < $CurrentVolume)
		{
			$command = "VolumeDown";
			$commandrepeat = ($CurrentVolume * $MaxStepVolume) - $TargetVolume;
		}
		$commandrepeat = round($commandrepeat);
		$this->SendDebug("Logitech Hub","Send Command: ".print_r($command,true),0);
		$this->SendDebug("Logitech Hub","Repeat Rate: ".print_r($commandrepeat,true),0);
		$this->VolumeControl($command, $commandrepeat);
	}
	
	public function VolumeControl(string $command, int $commandrepeat = NULL)
	{
		for ($i=0; $i <= $commandrepeat; $i++)
		{
			$this->Send($command);
			IPS_Sleep(150);
		}
	}
	
	
	protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);//array
		return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;//ConnectionID
    }
	
		
	//IP Harmony Hub 
	protected function GetIPHarmonyHub(){
		$ParentID = $this->GetParent();
		$IPHarmonyHub = IPS_GetProperty($ParentID, 'Host');
		return $IPHarmonyHub;
	}
	
	
	protected function RegisterProfileIntegerHarmony($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Nachkommastellen)
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
		IPS_SetVariableProfileDigits($Name, 0); //  Nachkommastellen
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        
    }
	
	protected function RegisterProfileIntegerHarmonyAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Nachkommastellen, $Associations)
	{
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileIntegerHarmony($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0, $Nachkommastellen);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }
	
	//Variablen anlegen
	public function SetupVariable(string $VarIdent, string $VarName, string $VarProfile)
	{
		$variablenID = $this->RegisterVariableInteger($VarIdent, $VarName, $VarProfile);
		$this->EnableAction($VarIdent);
		return $variablenID;
	}
	
	
	//Test zum Senden
	public function SendTest(string $Text)
		{
			IPS_LogMessage("HarmonyHub Device Test", $Text);
			$this->SendDataToParent(json_encode(Array("DataID" => "{EF26FF17-6C5B-4EFE-A7E2-63F599B84345}", "Buffer" => $Text))); //Harmony Device Interface GUI
		}
		
				
	
	public function Send(string $Command)
		{
			$DeviceID = $this->ReadPropertyInteger('DeviceID');
			$payload = array("DeviceID" => $DeviceID, "Command" => $Command, "BluetoothDevice" => $this->ReadPropertyBoolean('BluetoothDevice'));
			$this->SendDataToParent(json_encode(Array("DataID" => "{EF26FF17-6C5B-4EFE-A7E2-63F599B84345}", "Buffer" => $payload))); //Harmony Device Interface GUI
		}
	
	//Verfügbare Commands für Instanz ausgeben
	public function GetCommands()
		{
			$currentdeviceid = $this->ReadPropertyInteger('DeviceID');
			$parentID = $this->GetParent();
			$json = HarmonyHub_GetHarmonyConfigJSON($parentID);
            $commandlist = false;
			$devices[] =  $json["device"];
			foreach ($devices as $harmonydevicelist)
			{
				foreach ($harmonydevicelist as $harmonydevice)
				{
					// $InstName = $harmonydevice["label"]; //Bezeichnung Harmony Device
					$DeviceID = $harmonydevice["id"]; // Harmony Device ID
					if($DeviceID == $currentdeviceid)
					{
						$controlGroups = $harmonydevice["controlGroup"];
						$commandlist = array();
						foreach ($controlGroups as $controlGroup)
						{
							$commands = $controlGroup["function"]; //Function Array
							foreach ($commands as $command)
								{
									$harmonycommand = json_decode($command["action"], true); // command, type, deviceId
									$commandlist[] = $harmonycommand["command"];
								}
						}
					}
					
				}
			}
			return $commandlist;
		}	
	
	// Daten vom Splitter Instanz
	public function ReceiveData(string $JSONString)
	{
	 
		// Empfangene Daten vom Splitter
		$data = json_decode($JSONString);
		$datasplitter = $data->Buffer;
		//SetValueString($this->GetIDForIdent("BufferIN"), $datasplitter);
		IPS_LogMessage("ReceiveData Harmony Device", utf8_decode($datasplitter));
	 
		// Hier werden die Daten verarbeitet und in Variablen geschrieben
	 
	}
	
	//Configuration Form
	public function GetConfigurationForm()
	{
		$formhead = $this->FormHead();
		$formselection = $this->FormSelection();
		$formactions = $this->FormActions();
		$formelementsend = '{ "type": "Label", "label": "__________________________________________________________________________________________________" }';
		$formstatus = $this->FormStatus();
		
		return	'{ '.$formhead.$formselection.$formelementsend.'],'.$formactions.$formstatus.' }';	
	}
				
	protected function FormSelection()
	{			 
		$AlexaSmartHomeSkill = $this->GetAlexaSmartHomeSkill();
		$CheckVolumeControl = $this->CheckVolumeControl();
		if($CheckVolumeControl == false)
		{
			$form = '';
		}
		else
		{
			$form = '{
                    "name": "VolumeControl",
                    "type": "CheckBox",
                    "caption": "Volume Control"
                },
				{
                    "name": "MaxStepVolume",
                    "type": "NumberSpinner",
                    "caption": "Steps Volume"
                },';
		}
		if($AlexaSmartHomeSkill == false)
		{
			$form .= $form;
		}
		else
		{
			$form .= '{ "type": "Label", "label": "Alexa Smart Home Skill is available in IP-Symcon"},
				{ "type": "Label", "label": "Would you like to create Scripts for Alexa for Harmony actions and links in the SmartHomeSkill instace?" },
				{ "type": "CheckBox", "name": "Alexa", "caption": "Create Links and Scripts for Amazon Echo / Dot" },';
		}	
		
		return $form;
	}
	
	protected function CheckVolumeControl()
	{
		$CheckVolumeControl = false;
		$commands = $this->GetCommands();
		if($commands)
        {
            foreach ($commands as $key=>$command)
            {
                if($command == "VolumeDown")
                {
                    $CheckVolumeControl = true;
                }
            }
        }
		return $CheckVolumeControl;
	}
	

		protected function SetHarmonyInstanceScripts($InsIDList, $HubCategoryID)
	{
        $parentID = $this->GetParent();
        $json = HarmonyHub_GetHarmonyConfigJSON($parentID);
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
				$MainCatID = @IPS_GetCategoryIDByName($InstName, $HubCategoryID);
				if ($MainCatID === false)
				{
					$MainCatID = IPS_CreateCategory();
					IPS_SetName($MainCatID, utf8_decode($harmonydevice["label"]));
					IPS_SetInfo($MainCatID, $harmonydevice["id"]);
					IPS_SetParent($MainCatID, $HubCategoryID);
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




	
	protected function FormHead()
	{
		$form = '"elements":
            [
                { "type": "Label", "label": "Bitte Instanz Harmony Hub konfigurieren und dort Setup Harmony drücken"},
				{
                    "name": "Name",
                    "type": "ValidationTextBox",
                    "caption": "Name"
                },
				{
                    "name": "DeviceID",
                    "type": "NumberSpinner",
                    "caption": "DeviceID"
                },';
			
		return $form;
	}
		
	protected function FormActions()
	{
		$zapchannel = false;
		$form = '';
		if($zapchannel)
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
		}
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
                    "caption": "configuration valid"
                },
                {
                    "code": 104,
                    "icon": "inactive",
                    "caption": "Harmony Device is inactive"
                },
				{
                    "code": 201,
                    "icon": "error",
                    "caption": "Volume step can not be zero."
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
	
}

?>
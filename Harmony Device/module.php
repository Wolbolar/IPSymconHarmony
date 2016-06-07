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
				
		//Auswahl Prüfen
		if ($Name !== "" && $DeviceID !== "")
			{
				$this->SetStatus(102);	
			}
	}
	
		
	public function RequestAction($Ident, $Value)
    {
        $ObjID = $this->GetIDForIdent($Ident);
		$Object = IPS_GetObject($ObjID);
		$ObjectInfo = $Object["ObjectInfo"];
		$commands = json_decode($ObjectInfo, true);
		$command = $commands[$Value];
		//$command = GetValueFormatted($ObjID);
		SetValue($ObjID, $Value);
		$this->Send($command);
    }
	
	
	
	protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);//array
		return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;//ConnectionID
    }
	
		
	//IP Harmony Hub 
	protected function GetIPHarmonyHub(){
		$ParentID = $this->GetParent();
		$IPDenon = IPS_GetProperty($ParentID, 'Host');
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
        
        $this->RegisterProfileIntegerHarmony($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
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
			$devices[] =  $json["device"];
			foreach ($devices as $harmonydevicelist)
			{
				foreach ($harmonydevicelist as $harmonydevice)
				{
					$InstName = $harmonydevice["label"]; //Bezeichnung Harmony Device
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
	public function ReceiveData($JSONString)
	{
	 
		// Empfangene Daten vom Splitter
		$data = json_decode($JSONString);
		$datasplitter = $data->Buffer;
		//SetValueString($this->GetIDForIdent("BufferIN"), $datasplitter);
		IPS_LogMessage("ReceiveData Harmony Device", utf8_decode($data->Buffer));
	 
		// Hier werden die Daten verarbeitet und in Variablen geschrieben
	 
	}
	
}

?>
<?

class HarmonyfakeRoku extends IPSModule
{

   
    public function Create()
    {
        //Never delete this line!
        parent::Create();
		
    }


    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
		$fakerokuass =  Array(
				Array(0, "Up",  "", -1),
				Array(1, "Down",  "", -1),
				Array(2, "Left",  "", -1),
				Array(3, "Right",  "", -1),
				Array(4, "Select",  "", -1),
				Array(5, "Back",  "", -1),
				Array(6, "Play",  "", -1),
				Array(7, "Rev",  "", -1),
				Array(8, "Fwd",  "", -1),
				Array(9, "Search",  "", -1),
				Array(10, "Info",  "", -1),
				Array(11, "Home",  "", -1),
				Array(12, "Instant Replay",  "", -1)
				);
		$this->RegisterProfileIntegerHarmonyAss("LogitechHarmony.FakeRoku", "Keyboard", "", "", 0, 0, 0, 0, $fakerokuass);
		$this->RegisterVariableInteger("KeyFakeRoku", "Roku Emulator", "LogitechHarmony.FakeRoku", 1);
		$this->EnableAction("KeyFakeRoku");
		$this->RegisterProfileStringHarmony("LogitechHarmony.LastKeyFakeRoku", "Keyboard");
		$this->RegisterVariableString("LastKeystrokeFakeRoku", "Letzter Tastendruck", "LogitechHarmony.LastKeyFakeRoku", 2);
		$this->ValidateConfiguration();
		
	}
		
	/**
    * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
    * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
    *
    */
	private function ValidateConfiguration()
	{			
		$ipsversion = $this->GetIPSVersion();
		if($ipsversion == 0)
		{
			//prüfen ob Script existent
			$SkriptID = @($this->GetIDForIdent('FHEMIPSInterface'));
			if ($SkriptID === false)
			{
				$ID = $this->RegisterScript("FHEMIPSInterface", "FHEM IPS Interface", $this->CreateWebHookScript(), 5);
				IPS_SetHidden($ID, true);
				$this->RegisterHookOLD('/hook/fhem/fakeRoku', $ID);
			}
			else
			{
				//echo "Die Skript-ID lautet: ". $SkriptID;
			}
		}
		else
		{
			$SkriptID = @($this->GetIDForIdent('FHEMIPSInterface'));
			if ($SkriptID > 0)
			{
				$this->UnregisterHook("/hook/fhem/fakeRoku");
				$this->UnregisterScript("FHEMIPSInterface");
			}
			$this->RegisterHook("/hook/fhem/fakeRoku");
		}	
		
		//Skript bei Tastendruck				
		$IDKeystroke = @($this->GetIDForIdent('GetKeystrokeHarmony'));
		if ($IDKeystroke === false)
			{
				$IDKeystroke = $this->RegisterScript("GetKeystrokeHarmony", "Get Harmony Keystroke", $this->CreateKeystrokeScript(), 3);
				IPS_SetHidden($IDKeystroke, true);
				$this->SetKeystrokeEvent($IDKeystroke);
			}
		else
			{
				//echo "Die Skript-ID lautet: ". $SkriptID;
			}
		
		
		$this->SetStatus(102);	
		
	}
	
		
	public function RequestAction($Ident, $Value)
    {
        $ObjID = $this->GetIDForIdent($Ident);
		$lastkeyid = $this->GetIDForIdent("LastKeystrokeFakeRoku");
		SetValue($ObjID, $Value);
		//SetValue($lastkeyid, $keyval);
    }
	
	private function RegisterHookOLD($WebHook, $TargetID)
    {
        $ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
        if (sizeof($ids) > 0)
        {
            $hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
            $found = false;
            foreach ($hooks as $index => $hook)
            {
                if ($hook['Hook'] == $WebHook)
                {
                    if ($hook['TargetID'] == $TargetID)
                        return;
                    $hooks[$index]['TargetID'] = $TargetID;
                    $found = true;
                }
            }
            if (!$found)
            {
                $hooks[] = Array("Hook" => $WebHook, "TargetID" => $TargetID);
            }
            IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }
	
	private function RegisterHook($WebHook)
		{
  			$ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
  			if(sizeof($ids) > 0)
				{
  				$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
  				$found = false;
  				foreach($hooks as $index => $hook)
					{
					if($hook['Hook'] == $WebHook)
						{
						if($hook['TargetID'] == $this->InstanceID)
  							return;
						$hooks[$index]['TargetID'] = $this->InstanceID;
  						$found = true;
						}
					}
  				if(!$found)
					{
 					$hooks[] = Array("Hook" => $WebHook, "TargetID" => $this->InstanceID);
					}
  				IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
  				IPS_ApplyChanges($ids[0]);
				}
  		}
	
	/**
     * Löscht einen WebHook, wenn vorhanden.
     *
     * @access private
     * @param string $WebHook URI des WebHook.
     */
    protected function UnregisterHook($WebHook)
    {
        $ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
        if (sizeof($ids) > 0)
        {
            $hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
            $found = false;
            foreach ($hooks as $index => $hook)
            {
                if ($hook['Hook'] == $WebHook)
                {
                    $found = $index;
                    break;
                }
            }
            if ($found !== false)
            {
                array_splice($hooks, $index, 1);
                IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
                IPS_ApplyChanges($ids[0]);
            }
        }
    }  
	
	/**
     * Löscht eine Script, sofern vorhanden.
     *
     * @access private
     * @param int $Ident Ident der Variable.
     */
    protected function UnregisterScript($Ident)
    {
        $sid = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
        if ($sid === false)
            return;
        if (!IPS_ScriptExists($sid))
            return; //bail out
        IPS_DeleteScript($sid, true);
    } 
	
	private function CreateWebHookScript()
    {
        $Script = '<?
//Do not delete or modify.
LHFakeRoku_ProcessHookDataOLD('.$this->InstanceID.');		
?>';
        		
		return $Script;
    }
	
	public function ProcessHookDataOLD()
	{
		$lastkeyid = $this->GetIDForIdent("LastKeystrokeFakeRoku");
		$rokukeyid = $this->GetIDForIdent("KeyFakeRoku");	
		//workaround for bug
		if(!isset($_IPS))
			global $_IPS;
		if($_IPS['SENDER'] == "Execute")
			{
			echo "This script cannot be used this way.";
			return;
			}
				//Auswerten von Events von FHEM fakeRoku
		// FHEM nutzt GET
		if (isset($_GET["fhemevent"]))
			{
			$data = $_GET["fhemevent"];
			if ($data == "Up")
				{
					SetValue($rokukeyid, 0);
					SetValue($lastkeyid, "Up");
				}
			elseif ($data == "Down")
				{
					SetValue($rokukeyid, 1);
					SetValue($lastkeyid, "Down");
				}
			elseif ($data == "Left")
				{
					SetValue($rokukeyid, 2);
					SetValue($lastkeyid, "Left");
				}
			elseif ($data == "Right")
				{
					SetValue($rokukeyid, 3);
					SetValue($lastkeyid, "Right");
				}
			elseif ($data == "Select")
				{
					SetValue($rokukeyid, 4);
					SetValue($lastkeyid, "Select");
				}
			elseif ($data == "Back")
				{
					SetValue($rokukeyid, 5);
					SetValue($lastkeyid, "Back");
				}
			elseif ($data == "Play")
				{
					SetValue($rokukeyid, 6);
					SetValue($lastkeyid, "Play");
				}
			elseif ($data == "Rev")
				{
					SetValue($rokukeyid, 7);
					SetValue($lastkeyid, "Rev");
				}
			elseif ($data == "Fwd")
				{
					SetValue($rokukeyid, 8);
					SetValue($lastkeyid, "Fwd");
				}
			elseif ($data == "Search")
				{
					SetValue($rokukeyid, 9);
					SetValue($lastkeyid, "Search");
				}
			elseif ($data == "Info")
				{
					SetValue($rokukeyid, 10);
					SetValue($lastkeyid, "Info");
				}
			elseif ($data == "Home")
				{
					SetValue($rokukeyid, 11);
					SetValue($lastkeyid, "Home");
				}
			elseif ($data == "InstantReplay")
				{
					SetValue($rokukeyid, 12);
					SetValue($lastkeyid, "InstantReplay");
				}									
			}
	}
	
	/**
 	* This function will be called by the hook control. Visibility should be protected!
  	*/
		
	protected function ProcessHookData()
	{
		$lastkeyid = $this->GetIDForIdent("LastKeystrokeFakeRoku");
		$rokukeyid = $this->GetIDForIdent("KeyFakeRoku");	
		//workaround for bug
		if(!isset($_IPS))
			global $_IPS;
		if($_IPS['SENDER'] == "Execute")
			{
			echo "This script cannot be used this way.";
			return;
			}
				//Auswerten von Events von FHEM fakeRoku
		// FHEM nutzt GET
		if (isset($_GET["fhemevent"]))
			{
			$data = $_GET["fhemevent"];
			if ($data == "Up")
				{
					SetValue($rokukeyid, 0);
					SetValue($lastkeyid, "Up");
				}
			elseif ($data == "Down")
				{
					SetValue($rokukeyid, 1);
					SetValue($lastkeyid, "Down");
				}
			elseif ($data == "Left")
				{
					SetValue($rokukeyid, 2);
					SetValue($lastkeyid, "Left");
				}
			elseif ($data == "Right")
				{
					SetValue($rokukeyid, 3);
					SetValue($lastkeyid, "Right");
				}
			elseif ($data == "Select")
				{
					SetValue($rokukeyid, 4);
					SetValue($lastkeyid, "Select");
				}
			elseif ($data == "Back")
				{
					SetValue($rokukeyid, 5);
					SetValue($lastkeyid, "Back");
				}
			elseif ($data == "Play")
				{
					SetValue($rokukeyid, 6);
					SetValue($lastkeyid, "Play");
				}
			elseif ($data == "Rev")
				{
					SetValue($rokukeyid, 7);
					SetValue($lastkeyid, "Rev");
				}
			elseif ($data == "Fwd")
				{
					SetValue($rokukeyid, 8);
					SetValue($lastkeyid, "Fwd");
				}
			elseif ($data == "Search")
				{
					SetValue($rokukeyid, 9);
					SetValue($lastkeyid, "Search");
				}
			elseif ($data == "Info")
				{
					SetValue($rokukeyid, 10);
					SetValue($lastkeyid, "Info");
				}
			elseif ($data == "Home")
				{
					SetValue($rokukeyid, 11);
					SetValue($lastkeyid, "Home");
				}
			elseif ($data == "InstantReplay")
				{
					SetValue($rokukeyid, 12);
					SetValue($lastkeyid, "InstantReplay");
				}									
			}
	}
	
	private function SetKeystrokeEvent(integer $IDKeystroke)
	{
		//prüfen ob Event existent
		$ParentID = $IDKeystroke;

		$EreignisID = @($this->GetIDForIdent('EventKeystrokeFakeRoku'));
		if ($EreignisID === false)
			{
				$EreignisID = IPS_CreateEvent (0);
				IPS_SetName($EreignisID, "Keystroke FakeRoku");
				IPS_SetIdent ($EreignisID, "EventKeystrokeFakeRoku");
				IPS_SetEventTrigger($EreignisID, 0,  $this->GetIDForIdent('LastKeystrokeFakeRoku'));   //bei Variablenaktualisierung
				IPS_SetParent($EreignisID, $ParentID);
				IPS_SetEventActive($EreignisID, true);             //Ereignis aktivieren	
			}
			
		else
			{
			//echo "Die Ereignis-ID lautet: ". $EreignisID;	
			}
	}
	
	private function CreateKeystrokeScript()
	{
		$idlastkeystroke = $this->GetIDForIdent('LastKeystrokeFakeRoku');
		$Script = '<?
//modify for your needs
$lastkeystroke = GetValue('.$idlastkeystroke.');
			if ($lastkeystroke == "Up")
				{
					// Command 1
				}
			elseif ($lastkeystroke == "Down")
				{
					// Command 2
				}
			elseif ($lastkeystroke == "Left")
				{
					// Command 3
				}
			elseif ($lastkeystroke == "Right")
				{
					// Command 4
				}
			elseif ($lastkeystroke == "Select")
				{
					// Command 5
				}
			elseif ($lastkeystroke == "Back")
				{
					// Command 6
				}
			elseif ($lastkeystroke == "Play")
				{
					// Command 7
				}
			elseif ($lastkeystroke == "Rev")
				{
					// Command 8
				}
			elseif ($lastkeystroke == "Fwd")
				{
					// Command 9
				}
			elseif ($lastkeystroke == "Search")
				{
					// Command 10
				}
			elseif ($lastkeystroke == "Info")
				{
					// Command 11
				}
			elseif ($lastkeystroke == "Home")
				{
					// Command 12
				}
			elseif ($lastkeystroke == "InstantReplay")
				{
					// Command 13
				}			
?>';	
		return $Script;	
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
        
        $this->RegisterProfileIntegerHarmony($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Nachkommastellen);
        
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
		
	protected function RegisterProfileStringHarmony($Name, $Icon)
	{
        
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 3);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 3)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        //IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        //IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        
    }
	
	protected function GetIPSVersion ()
		{
			$ipsversion = IPS_GetKernelVersion ( );
			$ipsversion = explode( ".", $ipsversion);
			$ipsmajor = intval($ipsversion[0]);
			$ipsminor = intval($ipsversion[1]);
			if($ipsminor < 10) // 4.0
			{
				$ipsversion = 0;
			}
			elseif ($ipsminor >= 10 && $ipsminor < 20) // 4.1
			{
				$ipsversion = 1;
			}
			else   // 4.2
			{
				$ipsversion = 2;
			}
			return $ipsversion;
		}
}

?>
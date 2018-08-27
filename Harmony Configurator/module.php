<?
declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';



class HarmonyConfigurator extends IPSModule
{
	use BufferHelper,
		DebugHelper;

	public function Create()
	{
		//Never delete this line!
		parent::Create();

		// 1. Verfügbarer AIOSplitter wird verbunden oder neu erzeugt, wenn nicht vorhanden.
		$this->ConnectParent("{03B162DB-7A3A-41AE-A676-2444F16EBEDF}");
		$this->RegisterPropertyInteger("ImportCategoryID", 0);
		$this->RegisterPropertyBoolean("HarmonyVars", false);
		$this->RegisterPropertyBoolean("HarmonyScript", false);
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
		$MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		$HubCategoryID = $this->CreateHarmonyHubCategory();
		//Konfig prüfen
		$HarmonyConfig = IPS_GetObjectIDByIdent("HarmonyConfig", $MyParent);
		if ($HarmonyConfig == "") {
			$timestamp = time();
			$this->SendData('getConfig');
			$i = 0;
			do {
				IPS_Sleep(10);
				$updatetimestamp = IPS_GetVariable($this->GetIDForIdent("HarmonyConfig"))["VariableUpdated"];

				//echo $i."\n";
				$i++;
			} while ($updatetimestamp <= $timestamp);
		}

		//Skripte installieren
		$HarmonyScript = $this->ReadPropertyBoolean('HarmonyScript');
		if ($HarmonyScript == true) {
			$this->SendDebug("Harmony Hub Configurator", "Setup Scripts", 0);
			$this->SetHarmonyInstanceScripts($HubCategoryID);
		}

		//Harmony Aktivity Skripte setzten
		$this->SetupActivityScripts($HubCategoryID);

		//Harmony Aktivity Link setzten
		$this->CreateAktivityLink();
	}

	protected function CreateHarmonyHubCategory()
	{
		$MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		$hubip = IPS_GetProperty($MyParent, "Host");
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline.
		$hubname = GetValue(IPS_GetObjectIDByIdent("HarmonyHubName", $MyParent));
		$CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		//Prüfen ob Kategorie schon existiert
		$HubCategoryID = @IPS_GetObjectIDByIdent("CatLogitechHub_" . $hubipident, $CategoryID);
		if ($HubCategoryID === false) {
			$HubCategoryID = IPS_CreateCategory();
			IPS_SetName($HubCategoryID, "Logitech " . $hubname . " (" . $hubip . ")");
			IPS_SetIdent($HubCategoryID, "CatLogitechHub_" . $hubipident); // Ident muss eindeutig sein
			IPS_SetInfo($HubCategoryID, $hubip);
			IPS_SetParent($HubCategoryID, $CategoryID);
		}
		$this->SendDebug("Hub Skript Category", $HubCategoryID, 0);
		return $HubCategoryID;
	}

	protected function GetCurrentHarmonyDevices()
	{
		$HarmonyInstanceIDList = IPS_GetInstanceListByModuleID('{B0B4D0C2-192E-4669-A624-5D5E72DBB555}'); // Harmony Devices
		$HarmonyInstanceList = [];
		foreach($HarmonyInstanceIDList as $key => $HarmonyInstanceID)
		{
			$devicename = IPS_GetProperty($HarmonyInstanceID, "devicename");
			$deviceid = IPS_GetProperty($HarmonyInstanceID, "DeviceID");
			$HarmonyInstanceList[$deviceid] = ["objid" => $HarmonyInstanceID, "devicename" => $devicename, "deviceid" => $deviceid];
		}
		return $HarmonyInstanceList;
	}

	protected function SetHarmonyInstanceScripts($HubCategoryID)
	{
		$HarmonyInstanceList = $this->GetCurrentHarmonyDevices(); // Harmony Devices

		$config = $this->SendData('GetHarmonyConfigJSON');
		if(!empty($config)) {
			$data = json_decode($config, true);
			$activities[] = $data["activity"];
			$devices[] = $data["device"];
			foreach ($devices as $harmonydevicelist) {
				foreach ($harmonydevicelist as $harmonydevice) {
					$harmonyid = $harmonydevice["id"];
					// check if instance with $harmonyid exists
					$HarmonyInstance_Key = array_key_exists($harmonyid, $HarmonyInstanceList);
					if($HarmonyInstance_Key)
					{
						$harmony_objid = $HarmonyInstanceList[$harmonyid]["objid"];
						$controlGroups = $harmonydevice["controlGroup"];
						//Kategorien anlegen
						//Prüfen ob Kategorie schon existiert
						$MainCatID = @IPS_GetObjectIDByIdent("Logitech_Device_Cat" . $harmonydevice["id"], $HubCategoryID);
						if ($MainCatID === false) {
							$MainCatID = IPS_CreateCategory();
							IPS_SetName($MainCatID, utf8_decode($harmonydevice["label"]));
							IPS_SetInfo($MainCatID, $harmonydevice["id"]);
							IPS_SetIdent($MainCatID, "Logitech_Device_Cat" . $harmonydevice["id"]);
							IPS_SetParent($MainCatID, $HubCategoryID);
						}
						foreach ($controlGroups as $controlGroup) {
							$commands = $controlGroup["function"]; //Function Array

							//Prüfen ob Kategorie schon existiert
							$CGID = @IPS_GetObjectIDByIdent("Logitech_Device_" . $harmonydevice["id"] . "_Controllgroup_" . $controlGroup["name"], $MainCatID);
							if ($CGID === false) {
								$CGID = IPS_CreateCategory();
								IPS_SetName($CGID, $controlGroup["name"]);
								IPS_SetIdent($CGID, "Logitech_Device_" . $harmonydevice["id"] . "_Controllgroup_" . $controlGroup["name"]);
								IPS_SetParent($CGID, $MainCatID);
							}

							$assid = 0;
							foreach ($commands as $command) {
								$harmonycommand = json_decode($command["action"], true); // command, type, deviceId
								//Prüfen ob Script schon existiert
								$Scriptname = $command["label"];
								$controllgroupident = $this->CreateIdent("Logitech_Device_" . $harmonydevice["id"] . "_Command_" . $harmonycommand["command"]);
								$ScriptID = @IPS_GetObjectIDByIdent($controllgroupident, $CGID);
								if ($ScriptID === false) {
									$ScriptID = IPS_CreateScript(0);
									IPS_SetName($ScriptID, $Scriptname);
									IPS_SetParent($ScriptID, $CGID);
									IPS_SetIdent($ScriptID, $controllgroupident);
									$content = "<? LHD_Send(" . $harmony_objid . ", \"" . $harmonycommand["command"] . "\");?>";
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

	public function SetupActivityScripts($HubCategoryID)
	{
		$MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		$hubip = IPS_GetProperty($MyParent, "Host");
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline.
		$hubname = GetValue(IPS_GetObjectIDByIdent("HarmonyHubName", $MyParent));
		$activities_json = $this->SendData('GetAvailableAcitivities');
		$this->SendDebug("Harmony Hub Activities", $activities_json, 0);
		if(!empty($activities_json)) {
			$activities = json_decode($activities_json, true);
			//Prüfen ob Kategorie schon existiert
			$this->SendDebug("Top Category", $HubCategoryID, 0);
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
			$this->SendDebug("Activity Category", $MainCatID, 0);
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
		$MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
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

	//Link für Harmony Activity anlegen
	public function CreateAktivityLink()
	{
		$MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		$hubip = IPS_GetProperty($MyParent, "Host");
		$hubipident = str_replace('.', '_', $hubip); // Replaces all . with underline.
		$hubname = GetValue(IPS_GetObjectIDByIdent("HarmonyHubName", $MyParent));
		$HubCategoryID = $this->CreateHarmonyHubCategory();
		//Prüfen ob Instanz schon vorhanden
		$InstanzID = @IPS_GetObjectIDByIdent("Logitech_Harmony_Hub_Activities_" . $hubipident, $HubCategoryID);
		if ($InstanzID === false) {
			$InsID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
			IPS_SetName($InsID, $hubname); // Instanz benennen
			IPS_SetIdent($InsID, "Logitech_Harmony_Hub_Activities_" . $hubipident);
			IPS_SetParent($InsID, $HubCategoryID); // Instanz einsortieren unter dem Objekt mit der ID "$HubCategoryID"

			// Anlegen eines neuen Links für Harmony Aktivity
			$LinkID = IPS_CreateLink();             // Link anlegen
			IPS_SetName($LinkID, "Logitech Harmony Hub Activity"); // Link benennen
			IPS_SetParent($LinkID, $InsID); // Link einsortieren
			IPS_SetLinkTargetID($LinkID, IPS_GetObjectIDByIdent("HarmonyActivity", $MyParent));    // Link verknüpfen
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
		$config_list = [];
		$HarmonyInstanceIDList = IPS_GetInstanceListByModuleID('{B0B4D0C2-192E-4669-A624-5D5E72DBB555}'); // Harmony Devices
		$MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		$hostname = IPS_GetName(IPS_GetObjectIDByIdent("HarmonyHubName", $MyParent));
		$hubip = IPS_GetProperty($MyParent, "Host");
		$config = $this->SendData('GetHarmonyConfigJSON');
		$this->SendDebug('NEEO Config', $config, 0);
		if(!empty($config))
		{
			$data = json_decode($config);
			$devices = $data->device;
			foreach ($devices as $harmonydevice) {
				$instanceID = 0;
				$harmony_device_name = $harmonydevice->label; //Bezeichnung Harmony Device
				$this->SendDebug('Harmony Config', 'device name: '.$harmony_device_name, 0);
				$manufacturer = $harmonydevice->manufacturer; // manufacturer
				$this->SendDebug('Harmony Config', 'manufacturer: '.$manufacturer, 0);
				$commandset = $harmonydevice->controlGroup;
				$commandset_json = json_encode($commandset);
				$IsKeyboardAssociated = $harmonydevice->IsKeyboardAssociated;
				$model = $harmonydevice->model;
				$device_id = intval($harmonydevice->id); //DeviceID des Geräts
				$deviceTypeDisplayName = $harmonydevice->deviceTypeDisplayName;
				foreach ($HarmonyInstanceIDList as $HarmonyInstanceID) {
					if (IPS_GetInstance($HarmonyInstanceID)['ConnectionID'] == $MyParent && $device_id == IPS_GetProperty($HarmonyInstanceID, 'DeviceID')) {
						$instanceID = $HarmonyInstanceID;
					}
				}
				if(property_exists($harmonydevice, 'BTAddress'))
				{
					$BluetoothDevice = true;
					// $blutooth_address = $harmonydevice->BTAddress;
					$this->SendDebug('Harmony Config', 'device name: '.$harmony_device_name.' use bluetooth' , 0);
				}else {
					$BluetoothDevice = false;
					$this->SendDebug('Harmony Config', 'device name: '.$harmony_device_name.' does not use bluethooth' , 0);
				}
				$config_list[] = ["instanceID" => $instanceID,
					"id" => $device_id,
					"name" => $harmony_device_name,
					"manufacturer" => $manufacturer,
					"deviceTypeDisplayName" => $deviceTypeDisplayName,
					"deviceid" => $device_id,
					"location" => [
						$this->Translate('devices'), $this->Translate('harmony devices'), $hostname . " (" . $hubip . ")"
					],
					"create" => [
						[
							"moduleID" => "{B0B4D0C2-192E-4669-A624-5D5E72DBB555}",
							"configuration" =>  [
								"devicename" => $harmony_device_name,
								"DeviceID" => $device_id,
								"BluetoothDevice" => $BluetoothDevice,
								"VolumeControl" => false,
								"MaxStepVolume" => 0,
								"Manufacturer" => $manufacturer,
								"IsKeyboardAssociated" => $IsKeyboardAssociated,
								"model" => $model,
								"commandset" => $commandset_json,
								"deviceTypeDisplayName" => $deviceTypeDisplayName,
								"HarmonyVars" => $this->ReadPropertyBoolean("HarmonyVars"),
								"HarmonyScript" => $this->ReadPropertyBoolean("HarmonyScript"),
							]
						]
					]
				];
			}
		}
		return $config_list;
	}

	/***********************************************************
	 * Configuration Form
	 ***********************************************************/

	/**
	 * build configuration form
	 * @return string
	 */
	public function GetConfigurationForm()
	{
		// return current form
		$Form =  json_encode([
			'elements' => $this->FormHead(),
			'actions' => $this->FormActions(),
			'status' => $this->FormStatus()
		]);
		$this->SendDebug('FORM', $Form, 0);
		$this->SendDebug('FORM', json_last_error_msg(), 0);
		return $Form;
	}

	/**
	 * return form configurations on configuration step
	 * @return array
	 */
	protected function FormHead()
	{
		$category = false;

		$form = [
			[
				'type' => 'Label',
				'label' => 'category for Logitech Harmony Hub devices'
			],
			[
				'name' => 'ImportCategoryID',
				'type' => 'SelectCategory',
				'caption' => 'category harmony scripts'
			],
			[
				'type' => 'Label',
				'label' => 'Create Harmony devices for remote control:'
			],
			[
				'type' => 'Label',
				'label' => 'Create variables for webfront (Please note: High numbers of variables)'
			],
			[
				'name' => 'HarmonyVars',
				'type' => 'CheckBox',
				'caption' => 'Harmony variables'
			],
			[
				'type' => 'Label',
				'label' => 'create scripts for remote control (alternative or addition for remote control via webfront):'
			],
			[
				'name' => 'HarmonyScript',
				'type' => 'CheckBox',
				'caption' => 'Harmony script'
			],
			[
				'name' => 'HarmonyConfiguration',
				'type' => 'Configurator',
				'rowCount' => 20,
				'add' => false,
				'delete' => true,
				'sort' => [
					'column' => 'name',
					'direction' => 'ascending'
				],
				'columns' => [
					[
						'label' => 'ID',
						'name' => 'id',
						'width' => '200px',
						'visible' => false
					],
					[
						'label' => 'device name',
						'name' => 'name',
						'width' => 'auto'
					],
					[
						'label' => 'manufacturer',
						'name' => 'manufacturer',
						'width' => '250px'
					],
					[
						'label' => 'type',
						'name' => 'deviceTypeDisplayName',
						'width' => '250px'
					],
					[
						'label' => 'device id',
						'name' => 'deviceid',
						'width' => '200px'
					]
				],
				'values' => $this->Get_ListConfiguration()
			]
		];

		if ($category) {
			$form = array_merge_recursive(
				$form,
				[
					[
						'name' => 'script_category',
						'type' => 'SelectCategory',
						'caption' => 'Script category'
					]
				]
			);
		}


		return $form;
	}

	/**
	 * return form actions by token
	 * @return array
	 */
	protected function FormActions()
	{
		$MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		$form = [
			[
				'type' => 'Label',
				'label' => '1. Read Logitech Harmony Hub configuration:'
			],
			[
				'type' => 'Button',
				'label' => 'Read configuration',
				'onClick' => 'HarmonyHub_getConfig('.$MyParent.');'
			],
			[
				'type' => 'Label',
				'label' => '2. Get device list:'
			],
			[
				'type' => 'Button',
				'label' => 'Refresh list',
				'onClick' => 'HarmonyConfig_RefreshListConfiguration($id);'
			],
			[
				'type' => 'Label',
				'label' => '3. Setup Harmony activity scripts:'
			],
			[
				'type' => 'Button',
				'label' => 'Setup Harmony',
				'onClick' => 'HarmonyConfig_SetupHarmony($id);'
			]
		];
		return $form;
	}

	/**
	 * return from status
	 * @return array
	 */
	protected function FormStatus()
	{
		$form = [
			[
				'code' => 101,
				'icon' => 'inactive',
				'caption' => 'Creating instance.'
			],
			[
				'code' => 102,
				'icon' => 'active',
				'caption' => 'Harmony configurator created.'
			],
			[
				'code' => 104,
				'icon' => 'inactive',
				'caption' => 'interface closed.'
			],
			[
				'code' => 201,
				'icon' => 'inactive',
				'caption' => 'Please follow the instructions.'
			],
			[
				'code' => 202,
				'icon' => 'error',
				'caption' => 'no category selected.'
			]
		];

		return $form;
	}


	/** Eine Anfrage an den IO und liefert die Antwort.
	 * @param string $Method
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

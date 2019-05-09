<?
include_once(__DIR__ . "/../libs/SSDPTraits.php");

class HarmonyRokuEmulator extends IPSModule
{
	// helper properties
	private $position = 0;
	private $MySerial = "";

	public function Create()
	{
		//Never delete this line!
		parent::Create();
		$this->RequireParent("{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}"); // Server Socket
		$this->RegisterPropertyInteger('ServerSocketPort', 42450);
		$this->RegisterPropertyInteger('HarmonyHubObjID', 0);
		$this->RegisterPropertyInteger('HarmonyHubActivity', 0);
		$this->CreateActivityProperties();
		$this->MySerial = md5(openssl_random_pseudo_bytes(10));
	}


	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		//  register profiles
		$this->RegisterProfileAssociation(
			'LogitechHarmony.FakeRokuIPS',
			'Keyboard',
			'',
			'',
			0,
			1,
			0,
			0,
			1,
			[
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
				[12, $this->Translate('Instant Replay'), '', -1]
			]
		);

		$this->RegisterVariableInteger("KeyFakeRoku", "Roku Emulator", "LogitechHarmony.FakeRokuIPS", $this->_getPosition());
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
		$this->SetStatus(102);
	}

	/**
	 * checks, if configuration is complete
	 * @return bool
	 */
	private function CheckConfiguration()
	{
		$ServerSocketPort = $this->ReadPropertyInteger('ServerSocketPort');
		if ($ServerSocketPort > 0) {
			return true;
		}

		if ($ServerSocketPort == 0) {
			$this->_debug('Roku Emulator', 'Please select a port');
			$this->SetStatus(202);
			return false;
		}
		return true;
	}

	public function GetConfigurationForParent()
	{
		$Config['Port'] = $this->ReadPropertyInteger("ServerSocketPort"); // Server Socket Port
		return json_encode($Config);
	}

	public function ReceiveData($JSONString)
	{
		// Empfangene Daten vom I/O
		$data = json_decode($JSONString);
		//$dataio = json_encode($data->Buffer);
		$dataio = $data->Buffer;
		// $this->SendDebug("ReceiveData:", json_encode($dataio), 0);
		// "GET \/ HTTP\/1.1\r\nHost: 192.168.55.10:42450\r\nConnection: close\r\n\r\n"
		$Host = $data->ClientIP;
		// $this->SendDebug("ReceiveData:", "IP: " . $Host, 0);
		$Port = $data->ClientPort;
		// $this->SendDebug("ReceiveData:", "Port: " . $Port, 0);
		$pos = strpos($dataio, "GET");
		if ($pos == 0) {
			$this->RokuResponse($Host, $Port);
		}
		$pos = strpos($dataio, "POST");
		if ($pos == 0) {
			// cut off data
			$keypress_pos = strpos($dataio, "keypress");
			$http_pos = strpos($dataio, "HTTP");
			$data = substr($dataio, $keypress_pos+9, ($http_pos - ($keypress_pos+10)));
			$this->WriteValues($data);
		}
	}

	protected function WriteValues($data)
	{
		$this->SendDebug("Logitech Harmony Hub", "Roku Command: " . $data, 0);
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

	protected function StartRokuKeyscript($command)
	{
		$activity = $this->GetCurrentActivity();
		$activityname = $activity["activityname"];
		$activityid = $activity["activityid"];
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
					$this->SendDebug("Logitech Roku", "Roku starts script: " . utf8_decode(IPS_GetName($rokucommand["rokuscript"])) . " (" . $rokucommand["rokuscript"] . ")", 0);
					$this->SendDebug("Logitech Roku", "Command " . $command . " for activity " . $activityname, 0);
					IPS_RunScriptEx($rokucommand["rokuscript"], Array("Command" => $command, "Activity" => $activityname));
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
		$activityname = GetValueFormatted(IPS_GetObjectIDByIdent("HarmonyActivity", $HarmonyHubObjID));
		$activityid = GetValue(IPS_GetObjectIDByIdent("HarmonyActivity", $HarmonyHubObjID));
		$activity = array("activityname" => $activityname, "activityid" => $activityid);
		return $activity;
	}

	protected function RokuResponse($Host, $Port)
	{
		$rokuresponse = '<root xmlns="urn:schemas-upnp-org:device-1-0">
<specVersion>
<major>1</major>
<minor>0</minor>
</specVersion>
<device>
<deviceType>urn:roku-com:device:player:1-0</deviceType>
<friendlyName>IP-Symcon (Roku Device)</friendlyName>
<manufacturer>IPSymconHarmony</manufacturer>
<manufacturerURL>https://github.com/Wolbolar/IPSymconHarmony</manufacturerURL>
<modelDescription>Roku Emulator IP-Symcon</modelDescription>
<modelName>IPS5</modelName>
<modelNumber>4200X</modelNumber>
<modelURL>https://github.com/Wolbolar/IPSymconHarmony</modelURL>
<serialNumber>' . $this->MySerial . '</serialNumber>
<UDN>uuid:roku:ecp:' . $this->MySerial . '</UDN>
<serviceList>
<service>
<serviceType>urn:roku-com:service:ecp:1</serviceType>
<serviceId>urn:roku-com:serviceId:ecp1-0</serviceId>
<controlURL/>
<eventSubURL/>
<SCPDURL>ecp_SCPD.xml</SCPDURL>
</service>
</serviceList>
</device>
</root>
';
		$Header[] = "HTTP/1.1 200 OK";
		// $Header[] = "LOCATION: http://" . $this->GetIP() . ":".$this->ReadPropertyInteger("ServerSocketPort");
		// $Header[] = "Content-Type: application/xml; charset=utf-8";
		// $Header[] = "ST: roku:ecp";
		// $Header[] = "USN: uuid:roku:ecp:" . $this->MySerial;
		// $Header[] = "SERVER: Roku/1.0 UPnP/1.1";
		$Header[] = "Content-Type: text/xml; charset=utf-8";
		$Header[] = "Content-Length: " . strlen($rokuresponse);
		$Header[] = "Connection: Close";
		$Header[] = "\r\n";
		$Payload = implode("\r\n", $Header);
		$Payload .= '<?xml version="1.0" encoding="utf-8" ?>' . $rokuresponse;

		$result = $this->SendToSocket($Host, $Port, $Payload);
		return $result;
	}

	protected function SendToSocket($Host, $Port, $payload)
	{
		$SendData = Array("DataID" => "{C8792760-65CF-4C53-B5C7-A30FCC84FEFE}", "Buffer" => utf8_encode($payload), "ClientIP" => $Host, "ClientPort" => $Port); // Server Socket
		$this->SendDataToParent(json_encode($SendData));
		$this->SendDebug("SendData:", $payload, 0);
	}

	protected function GetIP()
	{
		$ssdpid = IPS_GetInstanceListByModuleID("{058CE601-4353-F473-EA14-A2B7B94628A0}")[0]; // SSDP;
		$instance = IPS_GetInstance($ssdpid);
		$parentssdp = $instance['ConnectionID'];
		$myIP = IPS_GetProperty($parentssdp, 'BindIP');
		return $myIP;
	}

	protected function GetHarmonyHubs()
	{
		$harmonyhubs = IPS_GetInstanceListByModuleID("{03B162DB-7A3A-41AE-A676-2444F16EBEDF}"); // Harmony Hub;
		return $harmonyhubs;
	}

	protected function GetHarmonyHubList()
	{
		$harmonyhubs = $this->GetHarmonyHubs();
		$options = [
			[
				'caption' => 'Please choose',
				'value' => 0
			]
		];
		foreach ($harmonyhubs as $harmonyhub) {
			$options[] = [
				'caption' => IPS_GetName($harmonyhub),
				'value' => $harmonyhub
			];
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
			$activities = $this->GetHubActivities($HubID);
			$number_activities = count($activities);
			if($number_activities > 0)
			{
				foreach ($activities as $key => $activity) {
					$form = array_merge_recursive(
						$form,
						[
							[
								'type' => 'ExpansionPanel',
								'caption' => $key,
								'items' => [
									[
										'type' => 'List',
										'name' => $this->GetListName($HubID, $activity),
										'caption' => 'Roku Emulator Keys',
										'rowCount' => 13,
										'add' => false,
										'delete' => false,
										'sort' => [
											'column' => 'command',
											'direction' => 'ascending'
										],
										'columns' => [
											[
												'name' => 'command',
												'label' => 'command',
												'width' => '200px',
												'save' => true,
												'visible' => true
											],
											[
												'name' => 'rokuscript',
												'label' => 'script',
												'width' => 'auto',
												'save' => true,
												'edit' => [
													'type' => 'SelectScript'
												]
											],
											[
												'name' => 'key_id',
												'label' => 'Key ID',
												'width' => 'auto',
												'save' => true,
												'visible' => false
											]
										],
										'values' => [
											[
												'command' => "Up",
												'key_id' => 0
											],
											[
												'command' => "Down",
												'key_id' => 1
											],
											[
												'command' => "Left",
												'key_id' => 2
											],
											[
												'command' => "Right",
												'key_id' => 3
											],
											[
												'command' => "Select",
												'key_id' => 4
											],
											[
												'command' => "Back",
												'key_id' => 5
											],
											[
												'command' => "Play",
												'key_id' => 6
											],
											[
												'command' => "Reverse",
												'key_id' => 7
											],
											[
												'command' => "Forward",
												'key_id' => 8
											],
											[
												'command' => "Search",
												'key_id' => 9
											],
											[
												'command' => "Info",
												'key_id' => 10
											],
											[
												'command' => "Home",
												'key_id' => 11
											],
											[
												'command' => "Instant Replay",
												'key_id' => 12
											]]
									]
								]
							]
						]
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

	public function RequestAction($Ident, $Value)
	{
		$ObjID = $this->GetIDForIdent($Ident);
		$lastkeyid = $this->GetIDForIdent("LastKeystrokeFakeRoku");
		SetValue($ObjID, $Value);
		//SetValue($lastkeyid, $keyval);
	}

	/**
	 * register profiles
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
		IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
	}

	/**
	 * register profile association
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


	/***********************************************************
	 * Configuration Form
	 ***********************************************************/

	/**
	 * build configuration form
	 * @return string
	 */
	public function GetConfigurationForm()
	{
		// update status, when configuration is not complete
		if (!$this->CheckConfiguration()) {
			$this->SetStatus(201);
		}

		// return current form
		return json_encode([
			'elements' => $this->FormHead(),
			'actions' => $this->FormActions(),
			'status' => $this->FormStatus()
		]);
	}

	/**
	 * return form configurations on configuration step
	 * @return array
	 */
	protected function FormHead()
	{
		$form = [
			[
				'type' => 'Label',
				'label' => 'Roku Emulator IP-Symcon'
			],
			[
				'type' => 'Label',
				'label' => 'Please select port to use for the Roku emulator:'
			],
			[
				'name' => 'ServerSocketPort',
				'type' => 'NumberSpinner',
				'caption' => 'Port'
			]
		];
		$harmonyhubs = $this->GetHarmonyHubs();
		$number_hubs = count($harmonyhubs);
		if($number_hubs == 0)
		{
			$form = array_merge_recursive(
				$form,
				[
					[
						'type' => 'Label',
						'label' => 'No hub found, please configure harmony hub first'
					]
				]
			);
		}
		else
		{
			$form = array_merge_recursive(
				$form,
				[
					[
						'type' => 'Label',
						'label' => 'Please select the Harmony Hub for configuration:'
					],
					[
						'name' => 'HarmonyHubObjID',
						'type' => 'Select',
						'caption' => 'Harmony Hub',
						'options' => $this->GetHarmonyHubList()
					]
				]
			);
		}

		$HarmonyHubObjID = $this->ReadPropertyInteger("HarmonyHubObjID");
		if ($HarmonyHubObjID > 0) {
			$form = array_merge_recursive(
				$form,
				[
					[
						'type' => 'Label',
						'label' => 'configure activities'
					]
				]
			);
			$form = $this->GetHubActivitiesExpansionPanels($HarmonyHubObjID, $form);
		}
		return $form;
	}


	/**
	 * return form actions by token
	 * @return array
	 */
	protected function FormActions()
	{
		$form = [];

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
				'caption' => 'Roku emulator device created.'
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
				'caption' => 'Device code must not be empty.'
			],
			[
				'code' => 203,
				'icon' => 'error',
				'caption' => 'Device code has not the correct lenght.'
			],
			[
				'code' => 204,
				'icon' => 'error',
				'caption' => 'no Harmony Hub selected.'
			]
		];

		return $form;
	}

	/***********************************************************
	 * Helper methods
	 ***********************************************************/

	/**
	 * send debug log
	 * @param string $notification
	 * @param string $message
	 * @param int $format 0 = Text, 1 = Hex
	 */
	private function _debug(string $notification = NULL, string $message = NULL, $format = 0)
	{
		$this->SendDebug($notification, $message, $format);
	}

	/**
	 * return incremented position
	 * @return int
	 */
	private function _getPosition()
	{
		$this->position++;
		return $this->position;
	}

	/***********************************************************
	 * Migrations
	 ***********************************************************/

	/**
	 * Polyfill for IP-Symcon 4.4 and older
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

<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
require_once __DIR__ . '/../libs/HarmonyBufferHelper.php';
require_once __DIR__ . '/../libs/HarmonyDebugHelper.php';

class HarmonyDiscovery extends IPSModule
{
    use HarmonyBufferHelper, HarmonyDebugHelper;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterAttributeString('devices', '[]');
        $this->RegisterPropertyString('Email', '');
        $this->RegisterPropertyString('Password', '');

        //we will wait until the kernel is ready
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterTimer('Discovery', 0, 'HarmonyDiscovery_Discover($_IPS[\'TARGET\']);');
    }

    /**
     * Interne Funktion des SDK.
     */
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }

        $this->WriteAttributeString('devices', json_encode($this->DiscoverDevices()));
        $this->SetTimerInterval('Discovery', 300000);

        // Status Error Kategorie zum Import auswählen
        $this->SetStatus(102);
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
            case IPS_KERNELSTARTED:
                $this->WriteAttributeString('devices', json_encode($this->DiscoverDevices()));
                break;

            default:
                break;
        }
    }

    /**
     * Liefert alle Geräte.
     *
     * @return array configlist all devices
     */
    private function Get_ListConfiguration()
    {
        $config_list        = [];
        $ConfiguratorIDList = IPS_GetInstanceListByModuleID('{E1FB3491-F78D-457A-89EC-18C832F4E6D9}'); // Harmony  Configurator
        $devices            = $this->DiscoverDevices();
        $this->SendDebug('Discovered Logitech Harmony Hubs', json_encode($devices), 0);
        $email    = $this->ReadPropertyString('Email');
        $password = $this->ReadPropertyString('Password');
        if (!empty($devices)) {
            foreach ($devices as $device) {
                $instanceID = 0;
                $name       = $device['name'];
                $uuid       = $device['uuid'];
                $host       = $device['host'];
                $device_id  = 0;
                foreach ($ConfiguratorIDList as $ConfiguratorID) {
                    if ($uuid == IPS_GetProperty($ConfiguratorID, 'uuid')) {
                        $configurator_name = IPS_GetName($ConfiguratorID);
                        $this->SendDebug(
                            'Harmony Discovery', 'configurator found: ' . utf8_decode($configurator_name) . ' (' . $ConfiguratorID . ')', 0
                        );
                        $instanceID = $ConfiguratorID;
                    }
                }

                $config_list[] = [
                    'instanceID' => $instanceID,
                    'id'         => $device_id,
                    'name'       => $name,
                    'uuid'       => $uuid,
                    'host'       => $host,
                    'create'     => [
                        [
                            'moduleID'      => '{E1FB3491-F78D-457A-89EC-18C832F4E6D9}',
                            'configuration' => [
                                'name' => $name,
                                'uuid' => $uuid,
                                'host' => $host, ], ],
                        [
                            'moduleID'      => '{03B162DB-7A3A-41AE-A676-2444F16EBEDF}',
                            'configuration' => [
                                'Email'    => $email,
                                'Password' => $password, ], ],
                        [
                            'moduleID'      => '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}',
                            'configuration' => [
                                'Host' => $host,
                                'Port' => 5222,
                                'Open' => true, ], ], ], ];
            }
        }

        return $config_list;
    }

    private function DiscoverDevices(): array
    {
        $devices = $this->mSearch('urn:myharmony-com:device:harmony:1');
        $this->SendDebug('Discover Response:', json_encode($devices), 0);
        $harmony_info = $this->GetHarmonyInfo($devices);
        foreach ($harmony_info as $device) {
            $this->SendDebug('name:', $device['name'], 0);
            $this->SendDebug('uuid:', $device['uuid'], 0);
            $this->SendDebug('host:', $device['host'], 0);
            $this->SendDebug('port:', $device['port'], 0);
        }

        return $harmony_info;
    }

    protected function mSearch($st = 'upnp:rootdevice')
    {
        $ssdp_ids = IPS_GetInstanceListByModuleID('{FFFFA648-B296-E785-96ED-065F7CEE6F29}');
        $ssdp_id = $ssdp_ids[0];
        $devices = YC_SearchDevices($ssdp_id, $st);
        $harmony_response = [];
        $i = 0;
        foreach($devices as $device)
        {
            if(isset($device['ST']))
            {
                if($device['ST'] == 'urn:myharmony-com:device:harmony:1')
                {
                    $uuid               = str_ireplace('uuid:', '', $device['USN']);
                    $cutoff             = strpos($uuid, '::');
                    $uuid               = substr($uuid, 0, $cutoff);
                    $harmony_response[$i]['uuid'] = $uuid;
                    $harmony_response[$i]['location'] = $device['Location'];
                    $i++;
                }
            }
        }
        return $harmony_response;
    }

    protected function GetHarmonyInfo($result)
    {
        $harmony_info = [];
        foreach ($result as $device) {
            $uuid           = $device['uuid'];
            $location       = $device['location'];
            $description    = $this->GetXML($location);
            $xml            = simplexml_load_string($description);
            $name           = strval($xml->device->friendlyName);
            $location       = str_ireplace('http://', '', $location);
            $location       = explode(':', $location);
            $ip             = $location[0];
            $cutoff         = strpos($location[1], '/');
            $port           = substr($location[1], 0, $cutoff);
            $harmony_info[] = ['name' => $name, 'uuid' => $uuid, 'host' => $ip, 'port' => $port];
        }

        return $harmony_info;
    }

    private function GetXML($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function GetDevices()
    {
        $devices = $this->ReadPropertyString('devices');

        return $devices;
    }

    public function Discover()
    {
        $this->LogMessage($this->Translate('Background Discovery of Logitech Harmony Hubs'), KL_NOTIFY);
        $this->WriteAttributeString('devices', json_encode($this->DiscoverDevices()));

        return json_encode($this->DiscoverDevices());
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
        $Form = json_encode(
            [
                'elements' => $this->FormElements(),
                'actions'  => $this->FormActions(),
                'status'   => $this->FormStatus(), ]
        );
        $this->SendDebug('FORM', $Form, 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);

        return $Form;
    }

    /**
     * return form configurations on configuration step.
     *
     * @return array
     */
    protected function FormElements()
    {
        $form = [
            [
                'type'    => 'Label',
                'caption' => 'MyHarmony access data (email / password)', ],
            [
                'name'    => 'Email',
                'type'    => 'ValidationTextBox',
                'caption' => 'Email', ],
            [
                'name'    => 'Password',
                'type'    => 'PasswordTextBox',
                'caption' => 'Password', ], ];

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
                'name'     => 'HarmonyDiscovery',
                'type'     => 'Configurator',
                'rowCount' => 20,
                'add'      => false,
                'delete'   => true,
                'sort'     => [
                    'column'    => 'name',
                    'direction' => 'ascending', ],
                'columns'  => [
                    [
                        'label'   => 'ID',
                        'name'    => 'id',
                        'width'   => '200px',
                        'visible' => false, ],
                    [
                        'label' => 'name',
                        'name'  => 'name',
                        'width' => 'auto', ],
                    [
                        'label' => 'UUID',
                        'name'  => 'uuid',
                        'width' => '400px', ],
                    [
                        'label' => 'host',
                        'name'  => 'host',
                        'width' => '250px', ], ],
                'values'   => $this->Get_ListConfiguration(), ], ];

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
                'caption' => 'Creating instance.', ],
            [
                'code'    => 102,
                'icon'    => 'active',
                'caption' => 'Harmony Hub Discovery created.', ],
            [
                'code'    => 104,
                'icon'    => 'inactive',
                'caption' => 'interface closed.', ],
            [
                'code'    => 201,
                'icon'    => 'inactive',
                'caption' => 'Please follow the instructions.', ], ];

        return $form;
    }
}

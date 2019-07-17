<?
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
        $this->RegisterAttributeString("devices", "[]");
        $this->RegisterPropertyString("Email", "");
        $this->RegisterPropertyString("Password", "");

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

        $this->WriteAttributeString("devices", json_encode($this->DiscoverDevices()));
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
                $this->WriteAttributeString("devices", json_encode($this->DiscoverDevices()));
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
        $email    = $this->ReadPropertyString("Email");
        $password = $this->ReadPropertyString("Password");
        if (!empty($devices)) {
            foreach ($devices as $device) {
                $instanceID = 0;
                $name       = $device["name"];
                $uuid       = $device["uuid"];
                $host       = $device["host"];
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
                    "instanceID" => $instanceID,
                    "id"         => $device_id,
                    "name"       => $name,
                    "uuid"       => $uuid,
                    "host"       => $host,
                    "create"     => [
                        [
                            'moduleID'      => '{E1FB3491-F78D-457A-89EC-18C832F4E6D9}',
                            'configuration' => [
                                'name' => $name,
                                'uuid' => $uuid,
                                'host' => $host]],
                        [
                            'moduleID'      => '{03B162DB-7A3A-41AE-A676-2444F16EBEDF}',
                            'configuration' => [
                                'Email'    => $email,
                                'Password' => $password]],
                        [
                            'moduleID'      => '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}',
                            'configuration' => [
                                'Host' => $host,
                                'Port' => 5222,
                                'Open' => true]]]];

            }
        }
        return $config_list;
    }

    private function DiscoverDevices(): array
    {
        $devices = $this->mSearch();
        $this->SendDebug("Discover Response:", json_encode($devices), 0);
        $harmony_info = $this->GetHarmonyInfo($devices);
        foreach ($harmony_info as $device) {
            $this->SendDebug("name:", $device["name"], 0);
            $this->SendDebug("uuid:", $device["uuid"], 0);
            $this->SendDebug("host:", $device["host"], 0);
            $this->SendDebug("port:", $device["port"], 0);
        }
        return $harmony_info;
    }

    protected function mSearch($st = 'upnp:rootdevice', $mx = 2, $man = 'ssdp:discover', $from = null, $port = null, $sockTimout = 3)
    {
        $user_agent = "MacOSX/10.8.2 UPnP/1.1 PHP-UPnP/0.0.1a";
        // BUILD MESSAGE
        $msg = 'M-SEARCH * HTTP/1.1' . "\r\n";
        $msg .= 'HOST: 239.255.255.250:1900' . "\r\n";
        $msg .= 'MAN: "' . $man . '"' . "\r\n";
        $msg .= 'MX: ' . $mx . "\r\n";
        $msg .= 'ST:' . $st . "\r\n";
        $msg .= 'USER-AGENT: ' . $user_agent . "\r\n";
        $msg .= '' . "\r\n";
        // MULTICAST MESSAGE
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return [];
        }
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, true);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, true);
        // SET TIMEOUT FOR RECIEVE
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ["sec" => $sockTimout, "usec" => 100000]);
        //socket_bind($socket, '0.0.0.0', 0);
        if (@socket_sendto($socket, $msg, strlen($msg), 0, '239.255.255.250', 1900) === false) {
            return [];
        }
        // RECIEVE RESPONSE
        $response = [];
        do {
            $buf   = null;
            $bytes = @socket_recvfrom($socket, $buf, 2048, 0, $from, $port);
            if ($bytes === false) {
                break;
            }
            if (!is_null($buf)) {
                $response[] = $this->parseMSearchResponse($buf);
            }
        } while (!is_null($buf));
        // CLOSE SOCKET
        socket_close($socket);
        $harmony_response = [];
        foreach ($response as $device) {
            if (isset($device["st"])) {
                if ($device["st"] == "urn:myharmony-com:device:harmony:1") {
                    $uuid               = str_ireplace('uuid:', '', $device["usn"]);
                    $cutoff             = strpos($uuid, "::");
                    $uuid               = substr($uuid, 0, $cutoff);
                    $harmony_response[] = ["uuid" => $uuid, "location" => $device["location"]];
                }
            }
        }
        return $harmony_response;
    }

    protected function parseMSearchResponse($response)
    {
        $responseArr    = explode("\r\n", $response);
        $parsedResponse = [];
        foreach ($responseArr as $key => $row) {
            if (stripos($row, 'http') === 0) {
                $parsedResponse['http'] = $row;
                $this->SendDebug("Discovered Device http:", json_encode($parsedResponse['http']), 0);
            }
            if (stripos($row, 'cach') === 0) {
                $parsedResponse['cache-control'] = str_ireplace('cache-control: ', '', $row);
                $this->SendDebug("Discovered Device cache-control:", json_encode($parsedResponse['cache-control']), 0);
            }
            if (stripos($row, 'date') === 0) {
                $parsedResponse['date'] = str_ireplace('date: ', '', $row);
                $this->SendDebug("Discovered Device date:", json_encode($parsedResponse['date']), 0);
            }
            if (stripos($row, 'ext') === 0) {
                $parsedResponse['ext'] = str_ireplace('ext: ', '', $row);
                $this->SendDebug("Discovered Device ext:", json_encode($parsedResponse['ext']), 0);
            }
            if (stripos($row, 'loca') === 0) {
                $parsedResponse['location'] = str_ireplace('location: ', '', $row);
                $this->SendDebug("Discovered Device location:", json_encode($parsedResponse['location']), 0);
            }
            if (stripos($row, 'serv') === 0) {
                $parsedResponse['server'] = str_ireplace('server: ', '', $row);
                $this->SendDebug("Discovered Device server:", json_encode($parsedResponse['server']), 0);
            }
            if (stripos($row, 'st:') === 0) {
                $parsedResponse['st'] = str_ireplace('st: ', '', $row);
                $this->SendDebug("Discovered Device st:", json_encode($parsedResponse['st']), 0);
            }
            if (stripos($row, 'usn:') === 0) {
                $parsedResponse['usn'] = str_ireplace('usn: ', '', $row);
                $this->SendDebug("Discovered Device usn:", json_encode($parsedResponse['usn']), 0);
            }
            if (stripos($row, 'cont') === 0) {
                $parsedResponse['content-length'] = str_ireplace('content-length: ', '', $row);
                $this->SendDebug("Discovered Device content-length:", json_encode($parsedResponse['content-length']), 0);
            }
            if (stripos($row, 'nt:') === 0) {
                $parsedResponse['nt'] = str_ireplace('nt: ', '', $row);
                $this->SendDebug("Discovered Device nt:", json_encode($parsedResponse['nt']), 0);
            }
            if (stripos($row, 'nl-deviceid') === 0) {
                $parsedResponse['nl-deviceid'] = str_ireplace('nl-deviceid: ', '', $row);
                $this->SendDebug("Discovered Device nl-deviceid:", json_encode($parsedResponse['nl-deviceid']), 0);
            }
            if (stripos($row, 'nl-devicename:') === 0) {
                $parsedResponse['nl-devicename'] = str_ireplace('nl-devicename: ', '', $row);
                $this->SendDebug("Discovered Device nl-devicename:", json_encode($parsedResponse['nl-devicename']), 0);
            }
        }
        return $parsedResponse;
    }

    protected function GetHarmonyInfo($result)
    {
        $harmony_info = [];
        foreach ($result as $device) {
            $uuid           = $device["uuid"];
            $location       = $device["location"];
            $description    = $this->GetXML($location);
            $xml            = simplexml_load_string($description);
            $name           = strval($xml->device->friendlyName);
            $location       = str_ireplace('http://', '', $location);
            $location       = explode(":", $location);
            $ip             = $location[0];
            $cutoff         = strpos($location[1], "/");
            $port           = substr($location[1], 0, $cutoff);
            $harmony_info[] = ["name" => $name, "uuid" => $uuid, "host" => $ip, "port" => $port];
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
        $devices = $this->ReadPropertyString("devices");
        return $devices;
    }

    public function Discover()
    {
        $this->LogMessage($this->Translate('Background Discovery of Logitech Harmony Hubs'), KL_NOTIFY);
        $this->WriteAttributeString("devices", json_encode($this->DiscoverDevices()));
        return json_encode($this->DiscoverDevices());
    }

    /***********************************************************
     * Configuration Form
     ***********************************************************/

    /**
     * build configuration form
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
                'status'   => $this->FormStatus()]
        );
        $this->SendDebug('FORM', $Form, 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);
        return $Form;
    }

    /**
     * return form configurations on configuration step
     *
     * @return array
     */
    protected function FormElements()
    {
        $form = [
            [
                'type'    => 'Label',
                'caption' => 'MyHarmony access data (email / password)'],
            [
                'name'    => 'Email',
                'type'    => 'ValidationTextBox',
                'caption' => 'Email'],
            [
                'name'    => 'Password',
                'type'    => 'PasswordTextBox',
                'caption' => 'Password']];
        return $form;
    }

    /**
     * return form actions by token
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
                    'direction' => 'ascending'],
                'columns'  => [
                    [
                        'label'   => 'ID',
                        'name'    => 'id',
                        'width'   => '200px',
                        'visible' => false],
                    [
                        'label' => 'name',
                        'name'  => 'name',
                        'width' => 'auto'],
                    [
                        'label' => 'UUID',
                        'name'  => 'uuid',
                        'width' => '400px'],
                    [
                        'label' => 'host',
                        'name'  => 'host',
                        'width' => '250px']],
                'values'   => $this->Get_ListConfiguration()]];
        return $form;
    }

    /**
     * return from status
     *
     * @return array
     */
    protected function FormStatus()
    {
        $form = [
            [
                'code'    => 101,
                'icon'    => 'inactive',
                'caption' => 'Creating instance.'],
            [
                'code'    => 102,
                'icon'    => 'active',
                'caption' => 'Harmony Hub Discovery created.'],
            [
                'code'    => 104,
                'icon'    => 'inactive',
                'caption' => 'interface closed.'],
            [
                'code'    => 201,
                'icon'    => 'inactive',
                'caption' => 'Please follow the instructions.']];

        return $form;
    }
}

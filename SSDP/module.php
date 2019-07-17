<?php

include_once __DIR__ . '/../libs/SSDPTraits.php';
require_once __DIR__ . '/../libs/HarmonyDebugHelper.php';
/**
 * @property int    $ParentID
 * @property string $MySerial
 * @property string $myIP IP-Adresse von IPS
 * @property string $Buffer
 */
class SSDPRoku extends IPSModule
{
    // use fügt bestimmte Traits dieser Klasse hinzu.
    use HarmonyDebugHelper, // Erweitert die SendDebug Methode von IPS um Arrays und Objekte.
        InstanceStatus /* Diverse Methoden für die Verwendung im Splitter */ {
        InstanceStatus::MessageSink as IOMessageSink; // MessageSink gibt es sowohl hier in der Klasse, als auch im Trait InstanceStatus. Hier wird für die Methode im Trait ein Alias benannt.
        InstanceStatus::RegisterParent as IORegisterParent; // MessageSink gibt es sowohl hier in der Klasse, als auch im Trait InstanceStatus. Hier wird für die Methode im Trait ein Alias benannt.
    }

    /**
     * Interne Funktion des SDK.
     * Wird immer ausgeführt wenn IPS startet und wenn eine Instanz neu erstellt wird.
     */
    public function Create()
    {
        // Diese Zeile nicht löschen.
        parent::Create();
        //Always create our own MultiCast I/O, when no parent is already available
        $this->RequireParent('{BAB408E0-0A0F-48C3-B14E-9FB2FA81F66A}');

        // $this->RegisterPropertyInteger('HarmonyHubRokuEmulatorID', 0);
        $this->RegisterPropertyBoolean('ExtendedDebug', true);
        $this->RegisterTimer('SendNotify', 0, 'SSDPRoku_TimerNotify($_IPS[\'TARGET\']);');
        // Alle Instanz-Buffer initialisieren
        $this->MySerial  = md5(openssl_random_pseudo_bytes(10));
        $this->SendQueue = [];
        $this->SetMultiBuffer('SSDPData', '');
    }

    // Überschreibt die intere IPS_ApplyChanges($id) Funktion
    public function ApplyChanges()
    {
        // Wir wollen wissen wann IPS fertig ist mit dem starten, weil vorher funktioniert der Datenaustausch nicht.
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        // Wenn sich unserer IO ändert, wollen wir das auch wissen.
        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);

        parent::ApplyChanges();

        // Wenn Kernel nicht bereit, dann warten... IPS_KERNELSTARTED/KR_READY kommt ja gleich
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        $this->RegisterHook('/hook/roku' . $this->InstanceID);
        // Unseren Parent merken und auf dessen Statusänderungen registrieren.
        $this->RegisterParent();
        if ($this->HasActiveParent()) {
            $this->IOChangeState(IS_ACTIVE);
        }
    }

    /**
     * Interne Funktion des SDK.
     * Verarbeitet alle Nachrichten auf die wir uns registriert haben.
     */
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        // Zuerst mal den Trait InstanceStatus die Nachtichten verarbeiten lassen:
        $this->IOMessageSink($TimeStamp, $SenderID, $Message, $Data);
        switch ($Message) {
            case IPS_KERNELSTARTED: // only after IP-Symcon started
                $this->KernelReady(); // if IP-Symcon is ready
                break;
        }
    }

    /**
     * Wird ausgeführt wenn der Kernel hochgefahren wurde.
     */
    protected function KernelReady()
    {
        $this->ApplyChanges();
    }

    /**
     * Überschreibt RegisterParent aus dem Trait InstanceStatus.
     */
    protected function RegisterParent()
    {
        $this->IORegisterParent();
        if ($this->ParentID > 0) {
            $this->myIP = IPS_GetProperty($this->ParentID, 'BindIP');
            $this->SendDebug('My IP is', $this->myIP, 0);
        } else {
            $this->myIP = '';
            $this->SendDebug('My IP is', 'EMPTY', 0);
        }
    }

    /**
     * Wird über den Trait InstanceStatus ausgeführt wenn sich der Status des Parent ändert.
     * Oder wenn sich die Zuordnung zum Parent ändert.
     *
     *
     * @param int $State Der neue Status des Parent.
     */
    protected function IOChangeState($State)
    {
        if ($State == IS_ACTIVE) { // Parent ist Aktiv geworden
            $this->SetTimerInterval('SendNotify', 60000); // Notify alle 60 senkunden
            $this->TimerNotify();
            $this->SetStatus(IS_ACTIVE); // Active neu setzen, für alle Childs welche darauf reagieren.
        } else { // Oh, Parent ist nicht aktiv geworden
            $this->SetTimerInterval('SendNotify', 0); // Und kein Notify mehr.
            $this->SetStatus(IS_INACTIVE);
        }
    }

    /**
     * Interne Funktion des SDK.
     * Wird von der Console aufgerufen, wenn 'unser' IO-Parent geöffnet wird.
     * Außerdem nutzen wir sie in Applychanges, da wir dort die Daten zum konfigurieren nutzen.
     */
    public function GetConfigurationForParent()
    {
        $Config['Port']        = 1900; //SSDP Multicast Sende-Port
        $Config['Host']        = '239.255.255.250'; //SSDP Multicast-IP
        $Config['MulticastIP'] = '239.255.255.250'; //SSDP Multicast-IP
        $Config['BindPort']    = 1900; //SSDP Multicast Empfangs-Port
        //$Config['BindIP'] muss der User auswählen und setzen wenn mehrere Netzwerkadressen auf dem IP-System vorhanden sind.
        $Config['EnableBroadcast']    = true;
        $Config['EnableReuseAddress'] = true;
        $Config['EnableLoopback']     = true;

        return json_encode($Config);
    }

    protected function GetRokuEmulatorPort()
    {
        $rokuemulators = IPS_GetInstanceListByModuleID('{8C1A1681-9CAD-A828-70B2-38DD6BD78FD0}'); // Roku Emulators
        $serverports   = [];
        foreach ($rokuemulators as $rokuemulator) {
            $ServerSocketPort = IPS_GetProperty($rokuemulator, 'ServerSocketPort');
            $serverports[]    = $ServerSocketPort;
        }

        return $serverports;
    }

    /**
     * Wird vom Timer aufgerufen
     * Versendet ein NOTIFY für jedes Gerät.
     */
    public function TimerNotify()
    {
        $serverports = $this->GetRokuEmulatorPort();
        foreach ($serverports as $serverport) {
            $this->SendNotify($serverport); // und einmal jetzt
        }
    }

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID); //array
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false; //ConnectionID
    }

    /**
     * Versendet ein NOTIFY.
     */
    protected function SendNotify(int $serverport)
    {
        $parent_form = IPS_GetConfiguration($this->GetParent());
        $bind_ip     = json_decode($parent_form, true)['BindIP'];

        $Header[] = 'NOTIFY * HTTP/1.1';
        $Header[] = 'HOST: 239.255.255.250:1900';
        $Header[] = 'CACHE-CONTROL: max-age=300';
        $Header[] = 'LOCATION: http://' . $bind_ip . ':' . $serverport . '/';
        // $Header[] = "LOCATION: http://" . $bind_ip . ":3777/hook/roku" . $this->InstanceID;
        // $Header[] = "LOCATION: http://192.168.55.10:3777/hook/roku10052"; // second IPS
        //$Header[] = "NT: roku:ecp";
        //$Header[] = "USN: uuid:roku:ecp:" . $this->MySerial;
        //$Header[] = "USN: uuid:" . $this->MySerial.'::roku:ecp:';
        $Header[] = 'NT: upnp:rootdevice';
        $Header[] = 'USN: uuid:' . $this->MySerial . '::upnp:rootdevice';
        $Header[] = 'NTS: ssdp:alive';
        $Header[] = 'SERVER: Roku/1.0 UPnP/1.1';
        $Header[] = 'Content_Length: 0';
        $Header[] = '';
        $Header[] = '';
        $Payload  = implode("\r\n", $Header);
        if ($this->ReadPropertyBoolean('ExtendedDebug')) {
            $this->SendDebug('SendNotify', $Payload, 0);
        }
        $SendData = [
            'DataID'     => '{C8792760-65CF-4C53-B5C7-A30FCC84FEFE}',
            'Buffer'     => utf8_encode($Payload),
            'ClientIP'   => '239.255.255.250',
            'ClientPort' => 1900,];
        //        $this->SendDebug("SendToParent", $SendData, 0);
        $this->SendDataToParent(json_encode($SendData));
    }

    /**
     * Antwort auf M-SEARCH.
     */
    public function SendSearchResponse(string $Host, int $Port, int $serverport)
    {
        $parent_form = IPS_GetConfiguration($this->GetParent());
        $bind_ip     = json_decode($parent_form, true)['BindIP'];

        $Header[] = 'HTTP/1.1 200 OK';
        $Header[] = 'CACHE-CONTROL: max-age=300';
        $Header[] = 'ST: roku:ecp';
        $Header[] = 'LOCATION: http://' . $bind_ip . ':' . $serverport . '/';
        // $Header[] = "LOCATION: http://" . $bind_ip . ":3777/hook/roku" . $this->InstanceID;
        //$Header[] = "LOCATION: http://192.168.55.10:3777/hook/roku10052"; // second IPS
        $Header[] = 'USN: uuid:roku:ecp:' . $this->MySerial;
        $Header[] = '';
        $Header[] = '';
        $Payload  = implode("\r\n", $Header);
        if ($this->ReadPropertyBoolean('ExtendedDebug')) {
            $this->SendDebug('SendSearchResponse', $Payload, 0);
        }
        $SendData =
            ['DataID' => '{C8792760-65CF-4C53-B5C7-A30FCC84FEFE}', 'Buffer' => utf8_encode($Payload), 'ClientIP' => $Host, 'ClientPort' => $Port];
        // $this->SendDebug("SendToParent", $SendData, 0);
        $this->SendDataToParent(json_encode($SendData));
    }

    private function ParseHeader($Lines)
    {
        $Header = [];
        foreach ($Lines as $Line) {
            $pair                     = explode(':', $Line);
            $Key                      = array_shift($pair);
            $Header[strtoupper($Key)] = trim(implode(':', $pair));
        }

        return $Header;
    }

    private function WriteBuffer($databuffer)
    {
        // Inhalt von $databuffer im Puffer speichern
        $this->SetMultiBuffer('SSDPData', $databuffer);
    }

    private function GetBufferIN()
    {
        // bereits im Puffer der Instanz vorhandene Daten in $databuffer kopieren
        $databuffer = $this->GetMultiBuffer('SSDPData');

        return $databuffer;
    }

    public function ReceiveData($JSONString)
    {
        $ReceiveData = json_decode($JSONString);
        //$this->SendDebug("Receive", $ReceiveData, 0);

        $databuffer = $this->GetBufferIN();
        $data       = $databuffer . utf8_decode($ReceiveData->Buffer);
        if (substr($data, -4) != "\r\n\r\n") { // HEADER nicht komplett ?
            $this->WriteBuffer($data);

            return;
        }
        //Okay Header komplett. Zerlegen:
        $Lines = explode("\r\n", $data);
        $this->WriteBuffer('');
        // die letzten zwei wech.
        array_pop($Lines);
        array_pop($Lines);

        //        $this->SendDebug("Receive", $Lines, 0);
        $Request = array_shift($Lines);
        $Header  = $this->ParseHeader($Lines);
        // Auf verschiedene Requests prüfen.
        switch ($Request) { // REQUEST
            case 'M-SEARCH * HTTP/1.1':
                // hier Sucht ein Gerät.
                // Sucht es nach uns ?
                if ($this->ReadPropertyBoolean('ExtendedDebug')) {
                    $this->SendDebug('Receive REQUEST', $Request, 0);
                    $this->SendDebug('Receive HEADER', $Header, 0);
                }
                if (isset($Header['MAN']) and (strtolower($Header['MAN']) == '"ssdp:discover"')) {
                    //   Antworten an diesen HOST und PORT.
                    $serverports = $this->GetRokuEmulatorPort();
                    foreach ($serverports as $serverport) {
                        $this->SendSearchResponse($ReceiveData->ClientIP, $ReceiveData->ClientPort, $serverport);
                    }

                    return;
                }
                break;
            case 'NOTIFY * HTTP/1.1':
                if ($this->ReadPropertyBoolean('ExtendedDebug')) {
                    $this->SendDebug('Receive REQUEST', $Request, 0);
                    $this->SendDebug('Receive HEADER', $Header, 0);
                }

                // hier meldet sich ein Gerät.
                // Wir könnten jetzt auch selbst hier mal die Location z.B. mit einen PHP-Socket oder Sys_GetURLContent() auslesen und so auch Geräte im Netz ansprechen welche sich mit Notify melden :)
                $Data = @Sys_GetURLContent($Header['LOCATION']); // HTTP-TCP Verbindung, mal schauen was das Gerät hinter der LOCATION verbirgt.
                if ($this->ReadPropertyBoolean('ExtendedDebug')) {
                    $this->SendDebug('Load LOCATION', $Data, 0);
                }

                // Hier können wir auch unsere Daten empfangen, welche wir in LOCATION selber per Webhook verlinkt haben !
                break;
            default:
                // Alles andere wollen wir nicht
                return;
        }
    }

    protected function RegisterHook($WebHook)
    {
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found                     = true;
                }
            }
            if (!$found) {
                $hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
            }
            $this->SendDebug('hook', $hooks, 0);
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

    protected function ProcessHookdata()
    {
        $this->SendDebug('!!!!!!!!!!!!!!!!!!!!!!!!!', 'DEVICE READ LOCATION', 0);
        $this->SendDebug('GET', $_GET, 0);
        $this->SendDebug('POST', $_POST, 0);
        $this->SendDebug('REQUEST', $_REQUEST, 0);
        $this->SendDebug('RAW', file_get_contents('php://input'), 0);
        echo '<root xmlns="urn:schemas-upnp-org:device-1-0">
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
<modelName>Roku</modelName>
<modelURL>https://github.com/Wolbolar/IPSymconHarmony</modelURL>
<modelNumber>4200X</modelNumber>
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
        $apsxml = `<apps>
  <app id="11">Roku Channel Store</app>
  <app id="12">Netflix</app>
  <app id="13">Amazon Video on Demand</app>
  <app id="14">MLB.TV¨</app>
  <app id="26">Free FrameChannel Service</app>
  <app id="27">Mediafly</app>
  <app id="28">Pandora</app>
  </apps>`;

        $mapping = [
            'Rev'           => 1,
            'Fwd'           => 2,
            'Play'          => 3,
            'Back'          => 4,
            'Home'          => 5,
            'Info'          => 6,
            'Up'            => 7,
            'Down'          => 8,
            'Right'         => 9,
            'Left'          => 10,
            'Select'        => 11,
            'InstantReplay' => 12,
            'Search'        => 13,];
    }

    // <deviceType>urn:roku-com:device:player:1-0</deviceType>
    // <deviceType>urn:schemas-upnp-org:device:Basic:1</deviceType>
    // <modelNumber>4200X</modelNumber>
    // <modelNumber>1.0</modelNumber>
    /*
     * <UDN>uuid:roku:ecp:' . $this->MySerial . '</UDN>
    <software-version>7.5.0</software-version>
    <software-build>09021</software-build>
    <power-mode>PowerOn</power-mode>
     */

    public function __get($name)
    {
        if (strpos($name, 'Multi_') === 0) {
            $curCount = $this->GetBuffer('BufferCount_' . $name);
            if ($curCount == false) {
                $curCount = 0;
            }
            $data = '';
            for ($i = 0; $i < $curCount; $i++) {
                $data .= $this->GetBuffer('BufferPart' . $i . '_' . $name);
            }
        } else {
            $data = $this->GetBuffer($name);
        }

        return unserialize($data);
    }

    public function __set($name, $value)
    {
        $data = serialize($value);
        if (strpos($name, 'Multi_') === 0) {
            $oldCount = $this->GetBuffer('BufferCount_' . $name);
            if ($oldCount == false) {
                $oldCount = 0;
            }
            $parts    = str_split($data, 8000);
            $newCount = strval(count($parts));
            $this->SetBuffer('BufferCount_' . $name, $newCount);
            for ($i = 0; $i < $newCount; $i++) {
                $this->SetBuffer('BufferPart' . $i . '_' . $name, $parts[$i]);
            }
            for ($i = $newCount; $i < $oldCount; $i++) {
                $this->SetBuffer('BufferPart' . $i . '_' . $name, '');
            }
        } else {
            $this->SetBuffer($name, $data);
        }
    }

    private function SetMultiBuffer($name, $value)
    {
        if (IPS_GetKernelVersion() >= 5) {
            $this->{'Multi_' . $name} = $value;
        } else {
            $this->SetBuffer($name, $value);
        }
    }

    private function GetMultiBuffer($name)
    {
        if (IPS_GetKernelVersion() >= 5) {
            $value = $this->{'Multi_' . $name};
        } else {
            $value = $this->GetBuffer($name);
        }

        return $value;
    }
}

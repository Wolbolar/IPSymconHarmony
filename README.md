# IPSymconHarmony

Modul für IP-Symcon ab Version 4. Ermöglicht die Kommunikation mit einem Logitech Harmony Hub und das Senden von Befehlen über den Logitech Harmony Hub.

Das Modul ist eine Erweiterung und Anpassung der Skripte von Zapp und der Diskussionen aus
https://www.symcon.de/forum/threads/22682-Logitech-Harmony-Ultimate-Smart-Control-Hub-library
als PHP-Modul für IP-Symcon ab Version 4.x.
Beta Test

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Anhang](#5-anhang)  

## 1. Funktionsumfang

Mit Hilfe des Logitech Harmony Hub sind Geräte bedienbar, die sonst über IR-Fernbedienungen steuerbar sind oder auch neuere Geräte wie FireTV und AppleTV.
Nähere Informationen zu ansteuerbaren Geräten über den Logitech Harmony Hub unter
http://www.logitech.com/de-de/product/harmony-ultimate-hub
Mit Hilfe des Moduls können die Geräte die im Logitech Harmony Hub hinterlegt sind in IP-Symcon importiert werden und dann von IP-Symcon über den Logitech Harmony Hub geschaltet werden.
Harmony Aktivitäten können von IP-Symcon aus gestartet werden. Wenn der Harmony Hub eine Aktivität ausführt wird die aktuelle laufende Aktivität an IP-Symcon übermittelt.

### IR Code Senden:  

 - Senden eines IR Signals über den Logitech Harmony Hub an dem Hub bekannte Geräte  

### FireTV:  (Testmodus)

 - Senden von Befehlen an einen FireTV    

### Aktivität anzeigen:  

 - Anzeige der momentanen aktiven Harmony Activity des Harmony Hub  
   

## 2. Voraussetzungen

 - IPS 4.x
 - Logitech Harmony Hub im gleichen Netzwerk wie IP-Symcon

## 3. Installation

### a. Laden des Moduls

   Über das 'Modul Control' in IP-Symcon (Ver. 4.x) folgende URL hinzufügen:
	
    `git://github.com/Wolbolar/IPSymconHarmony.git`  

### b. Einrichtung in IPS

	In IP-Symcon zunächst über Objekt hinzufügen -> Instanz hinzufügen eine neue Instanz anlegen. Als Hersteller Logitech eintragen und als Instanz Logitech Harmony Hub auswählen.
	Das Konfigurationsformular ausfüllen:
	Öffnen: Haken setzt damit die I/O Instanz aktiviert wird
	IP Adresse: IP Adresse des Logitech Harmony Hub eintragen.
	Port: Standard Port zur Kommunikation 5222 (nicht umstellen)
	Zugangsdaten
	Email: Emailadresse, die als Anmeldename für MyHarmony benutzt wird
	Passwort: Passwort das zum Anmelden an MyHarmony benutzt wird
	
	Kategorie zum Anlegen der Logitech Harmony Hub Geräte
	Es ist eine Kategorie in IP-Symcon anzulegen und hier im Feld Harmony Hub Geräte auszuwählen unter der dann die Geräte automatisch angelegt werden
	
	Debug blendet die Variablen IO und CommandOut ein, nur für Debug. Hier muss kein Haken gesetzt werden.
	
	
	Standardmäßig wird mit dem Druck auf Übernehmen nur die Instanz der Harmony Hub Geräte und das Schalten der Harmony Hub Aktivitäten im Webfront angelegt.
	Sollte es gewünscht sein auch einzelne Befehle an die an dem Logitech Harmony Hub angelernten Geräte zu schicken, kann bei Harmony Variablen und /oder Harmony Skript ein Haken gesetzt werden.
	
	Harmony Variablen
	Es werden bei jedem Harmony Hub Gerät für jede im Harmony Hub hinterlegte Controllgroup eine Variable zum Schalten im Webfront angelegt. Hier können je nach Anzahl der im Harmony Hub konfigurierten Geräte eine hohe Anzahl an Variablen anfallen.
	Die Option ist für IP-Symcon Nutzer gedacht die noch genügend Variablen zur Verfügung haben und Befehle aus dem Webfront absetzten wollen.	
	
	Harmony Skript
	Diese Option kann als Alternative oder als Ergänzung zu Harmony Variablen genutzt werden. Es werden für jede im Harmony Hub hinterlegte Controllgroup eine Subkategorie mit Scripten angelegt.
	Das einzelne Script sendet dann denn jeweiligen Befehl (Skriptname) an den Logitech Harmony Hub.
	
	Nachdem beim ersten Anlegen des Moduls auf Übernehmen gedrückt wurde wird zunächst mal der User Auth Token vom Modul abgefragt und in IP-Symcon abgelegt.
	
	Nachdem der User Auth Token in IP-Symcon abgelegt wurde kann nun in der Testumgebeung auf
	Konfiguration auslesen
	gedrückt werden.
	Die Konfiguration des Logitech Harmony Hub wird ausgelesen und in der Variable Harmony Config abgelegt.
	
	Nachdem die Variable Harmony Config in IP-Symcon beschrieben wurde kann auf
	Setup Harmony 
	gedrückt werden.
	Nun wird abhängig von der Auswahl unter Harmony Variablen und Harmony Skript die Variablen oder Skripte für die im Logitech Harmony Hub hinterlegten Geräte angelegt.


## 4. Funktionsreferenz

### Logitech Harmony Hub:

### Harmony Devices
 Die Harmony Devices sind über den Splitter und Setup Harmony anzulegen
 An jedes Device kann ein Befehl geschickt werden
 
 Liest die verfügbaren Funktionen des Geräts aus und gibt diese als Array aus
  `LHD_GetCommands(integer $InstanceID)` 
  Parameter $InstanceID ObjektID des Harmony Hub Geräts
  
 Sendet einen Befehl an den Logitech Harmony Hub
 `LHD_Send(integer $InstanceID, string $Command)` 
 Parameter $InstanceID ObjektID des Harmony Hub Geräts
 Parameter $Command Befehl der gesendet werden soll, verfügbare Befehle werden über LHD_GetCommands ausgelesen.
 
### Harmony Hub
 Es können Aktivitäten des Logitech Harmony Hub ausgeführt werden.
 Die aktuelle Akivität des Logitech Harmony Hub wird in der Variable Harmony Activity angezeigt und kann im Webfront geschaltet werden.
 
 Wenn die Aktivität über Funktionen aktualisiert werden soll oder über ein Skript geschaltet sind die folgenden Funktionen zu benutzten:
 Fordert die aktuelle Aktivität des Logitech Harmony Hub an. Der Wert wird in die Variable Harmony Activity gesetzt.
   `HarmonyHub_getCurrentActivity(integer $InstanceID)` 
  Parameter $InstanceID ObjektID des Harmony Hub Splitters

 
 Liest alle verfügbaren Aktivitäten des Logitech Harmony Hub aus und gibt einen Array zurück.
   `HarmonyHub_GetAvailableAcitivities(integer $InstanceID)` 
  Parameter $InstanceID ObjektID des Harmony Hub Splitters
  
  Liest alle verfügbaren Device IDs des Logitech Harmony Hub aus und gibt einen Array zurück.
   `HarmonyHub_GetHarmonyDeviceIDs(integer $InstanceID)` 
  Parameter $InstanceID ObjektID des Harmony Hub Splitters 
 
 Schaltet auf die gewünschte Logitech Harmony Hub Aktivität
 `HarmonyHub_startActivity(integer $InstanceID, integer $activityID)` 
  Parameter $InstanceID ObjektID des Harmony Hub Splitters
  Parameter $activityID ID der Harmony Aktivität, verfügbare IDs können über HarmonyHub_GetAvailableAcitivities ausgelesen werden


## 5. Konfiguration:

### Logitech Harmony Hub:

| Eigenschaft      | Typ     | Standardwert | Funktion                                            |
| :--------------: | :-----: | :----------: | :-------------------------------------------------: |
| Open             | boolean | true         | Verbindung zum Logitech Harmony Hub aktiv / deaktiv |
| Host             | string  |              | IP Adresse des Logitech Harmony Hub                 |
| Port             | integer | 5222         | Port zur Kommunikation mit dem Logitech Harmony Hub |
| Email            | string  |              | Email Adresse zur Anmeldung MyHarmony               |
| Passwort         | string  |              | Passwort zur Anmeldung MyHarmony                    |
| ImportCategoryID | integer |              | ObjektID der Import Kategorie                       |
| HarmonyVars      | boolean |              | Aktiv legt Variablen pro Controlgroup an            |
| HarmonyScript    | boolean |              | Aktiv legt für jeden Befehl ein Skript an           |


### Logitech Harmony Device:  

| Eigenschaft     | Typ     | Standardwert | Funktion                                                              |
| :-------------: | :-----: | :----------: | :-------------------------------------------------------------------: |
| Name            | string  |              | Name des Geräts                                                       |
| DeviceID        | integer |              | DeviceID des Geräts                                                   |
| BluetoothDevice | boolean |     false    | Bluetooth Gerät                                                       |


## 6. Anhang

###  b. GUIDs und Datenaustausch:

#### Logitech Harmony Hub Splitter:

GUID: `{7E03C651-E5BF-4EC6-B1E8-397234992DB4}` 


#### Logitech Harmony Device:

GUID: `{C45FF6B3-92E9-4930-B722-0A6193C7FFB5}` 





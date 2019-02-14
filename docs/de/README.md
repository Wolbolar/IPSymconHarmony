# IPSymconHarmony
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)

Modul für IP-Symcon ab Version 5. Ermöglicht die Kommunikation mit einem Logitech Harmony Hub und das Senden von Befehlen über den Logitech Harmony Hub.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Anhang](#5-anhang)  

## 1. Funktionsumfang

Mit Hilfe des Logitech Harmony Hub sind Geräte bedienbar, die sonst über IR-Fernbedienungen steuerbar sind oder auch neuere Geräte wie FireTV und AppleTV die über Bluetooth angesteuert werden.
Nähere Informationen zu ansteuerbaren Geräten über den Logitech Harmony Hub unter [Logitech Harmony Elite](https://www.logitech.com/de-de/product/harmony-elite "Logitech Harmony Elite")

Mit Hilfe des Moduls können die Geräte die im Logitech Harmony Hub hinterlegt sind in IP-Symcon importiert werden und dann von IP-Symcon über den Logitech Harmony Hub geschaltet werden.
Harmony Aktivitäten können von IP-Symcon aus gestartet werden. Wenn der Harmony Hub eine Aktivität ausführt wird die aktuelle laufende Aktivität an IP-Symcon übermittelt.

### IR Code Senden:  

 - Senden eines IR Signals über den Logitech Harmony Hub an dem Hub bekannte Geräte  

### FireTV:  (Bluetooth)

 - Senden von Befehlen an einen FireTV    

### Aktivität anzeigen:  

 - Anzeige der momentanen aktiven Harmony Activity des Harmony Hub 
 
### Aktivität starten 
   
- Starten einen Harmony hub Aktivität

## 2. Voraussetzungen

 - IPS 5.x
 - Logitech Harmony Hub im gleichen Netzwerk wie IP-Symcon

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://<IP-Symcon IP>:3777/console/_ öffnen. 


Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

![Store](img/store_icon.png?raw=true "open store")

Im Suchfeld nun

```
Logitech Harmony
```  

eingeben

![Store](img/module_store_search.png?raw=true "module search")

und schließend das Modul auswählen und auf _Installieren_

![Store](img/install.png?raw=true "install")

drücken.


#### Alternatives Installieren über Modules Instanz

Den Objektbaum _Öffnen_.

![Objektbaum](img/objektbaum.png?raw=true "Objektbaum")	

Die Instanz _'Modules'_ unterhalb von Kerninstanzen im Objektbaum von IP-Symcon (>=Ver. 5.x) mit einem Doppelklick öffnen und das  _Plus_ Zeichen drücken.

![Modules](img/Modules.png?raw=true "Modules")	

![Plus](img/plus.png?raw=true "Plus")	

![ModulURL](img/add_module.png?raw=true "Add Module")
 
Im Feld die folgende URL eintragen und mit _OK_ bestätigen:

```
https://github.com/Wolbolar/IPSymconHarmony 
```  
	
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_    

Es wird im Standard der Zweig (Branch) _master_ geladen, dieser enthält aktuelle Änderungen und Anpassungen.
Nur der Zweig _master_ wird aktuell gehalten.

![Master](img/master.png?raw=true "master") 

Sollte eine ältere Version von IP-Symcon die kleiner ist als Version 5.1 (min 4.3) eingesetzt werden, ist auf das Zahnrad rechts in der Liste zu klicken.
Es öffnet sich ein weiteres Fenster,

![SelectBranch](img/select_branch.png?raw=true "select branch") 

hier kann man auf einen anderen Zweig wechseln, für ältere Versionen kleiner als 5.1 (min 4.3) ist hier
_Old-Version_ auszuwählen. 

### b. Einrichtung in IPS

Wenn Skripte angelegt werden sollen werden diese unterhalb einer Kategorie abgelegt. Zunächst legen wir eine Kategorie im Objektbaum _Rechte Maustaste -> Objekt hinzufügen -> Kategorie_ an, dieser geben wir einen beliebigen Namen wie z.B. _**Harmony Geräte**_.
Unter dieser Kategorie werden später alle Skripte für Geräte des Logitech Harmony Hub angelegt werden.

Danach in IP-Symcon 5.x unter Splitter eine Instanz hinzufügen.

![Add_Splitter](img/add_splitter.png?raw=true "Add Splitter")

Hier nun als Hersteller _Logitech_ eingeben und _Logitech Harmony Hub_ auswählen.

![Add Logitech Hub](img/add_splitter_1.png?raw=true "Add Logitech Hub")


Im dem sich öffnenden Fenster zunächst bei Erstinstallation folgende Dinge auswählen:

![Logitech Hub 1](img/logitech_hub_1.png?raw=true "Logitech Hub 1")


**1.** Slider auf Aktiv setzten. Dies ist notwenig damit die I/O Instanz später aktiv ist.

**2.** IP Adresse des Logitech Harmony Hub eintragen

**3.** Email Adresse (entspricht Anmeldename) für MyHarmony

**5.** Passwort für MyHarmony

**6.** Anschließend _ÄNDERUNGEN ÜBERNEHMEN_ drücken.

![Accept Changes](img/Accept_Changes.png?raw=true "Accept Changes")

**7.** _Konfiguration auslesen_ drücken und ein paar Sekunden abwarten.

![Logitech Hub 2](img/logitech_hub_2.png?raw=true "Logitech Hub 2")

**8.** Nachdem die Variable _Harmony Config_ aktualisiert wurde auf _Setup Harmony_ drücken, danach kann die Instanz geschlossen werden.

Anschließend wird ein Konfigurator anlegt.

![Konfigurator 1](img/konfigurator_1.png?raw=true "Konfigurator 1")

![Konfigurator 2](img/konfigurator_2.png?raw=true "Konfigurator 2")

Im Konfigurator steht jetzt folgendes zur Auswahl:

![Konfigurator 3](img/konfigurator_3.png?raw=true "Konfigurator 3")


- _Kategorie Harmony Skripte_ ist die Kategorie unter der Skripte angelegt werden
- _Harmony Variablen_ wenn nicht aktiv wird nur die Instanz angelegt, es können dann dennoch Skripte genutzt werden. Aktivieren um über Variablen im Webfront schalten zu können. _Achtung! Bei vielen Geräten können viele Variablen verbraucht werden_.
_Optional_ Wenn diese Option gewählt wird werden für jede Befehlsgruppe eines Logitech Harmony Geräts eine Variable zum Schalten aus dem Webfront angelegt. **VORSICHT:** Diese Option sollte **nur gewählt werden wenn noch ausreichend Variablen in IP-Symcon verfügbar sind** oder die Variablenanzahl unbegrenzt ist, da eine hohe Anzahl an Variablen je nach angelernten Geräten im Harmony Hub verbraucht werden kann. Es werden bei jedem Harmony Hub Gerät für jede im Harmony Hub hinterlegte Controllgroup eine Variable zum Schalten im Webfront angelegt. Hier können je nach Anzahl der im Harmony Hub konfigurierten Geräte eine hohe Anzahl an Variablen anfallen.
Die Option ist für IP-Symcon Nutzer gedacht die noch genügend Variablen zur Verfügung haben und Befehle aus dem Webfront absetzten wollen.	
- _Harmony Skript_ wenn dies aktiviert wird, wird für jede bereits angelegte Instanz mit _Setup Harmony_ die zugehörigen Skripte angelegt.
_Optional_ Dies Option kann gewählt werden als Alternative oder Ergänzung zu Variablen. Es werden für jede im Harmony Hub hinterlegte Controllgroup eine Subkategorie mit Scripten angelegt.
Das einzelne Script sendet dann denn jeweiligen Befehl (Skriptname) an den Logitech Harmony Hub.

Sollte etwas an der Konfiguration im Harmony Hub verändert worden sein ist _Konfiguration auslesen_ zu drücken und nach einer Pause dann _Liste aktualisieren_.
Im Konfigurator können nun einzelne Geräte ausgewählt werden und mit _Erstellen_ wird das Gerät in IP-Symcon angelegt.

![Konfigurator 4](img/konfigurator_4.png?raw=true "Konfigurator 4")

Mit _Setup Harmony_ wird für jedes in IP-Symcon angelegte Gerät abhänig von den Einstellungen (s.o.) Skripte angelegt, des Weiteren werden Skripte für die Aktivitäten erzeugt.


Im Webfront von IP-Symcon sieht das z.B. dann so aus:
![Webfront](img/Webfront.png?raw=true "Webfront")

Es lassen sich über das Webfront oder die Skripte dann Befehle absetzten. Die Aktivität wird im Webfront angezeigt.
Sobald ein Gerät oder Harmony Fernbedienung eine Harmony Aktivität auslöst wird diese auch in IP-Symcon aktualisiert.
Die aktuelle Aktivität wird in der Variable Harmony Activity, diese liegt unter dem Logitech Harmony Splitter, angezeigt. Es wird automatisch ein Link unter der oben gewählten Kategorie zu dieser Variable angelegt.

Die Variablennamen und die Bezeichnung der Befehle werden so angelegt wie diese von der Bezeichnung im Harmony Hub hinterlegt sind. Für jede angelegte Variable wird auch das Beschreibungsfeld genutzt, hier steht der eigentliche Befehl drinnen der an den Harmony Hub gesendet wird. Daher darf das Beschreibungsfeld der Variable nicht geändert werden. Die Bezeichnung der Variable sowie die Befehlsnamen die im Variablenprofile der Variable hinterlegt sind können individuell vom Nutzer angepasst werden. Dabei darf nur nicht die Reihenfolge im Variablenprofil verändert werden.

## 4. Funktionsreferenz

### Logitech Harmony Hub:

### Harmony Devices
 Die Harmony Devices sind über den Konfigurator anzulegen
 An jedes Device kann ein Befehl geschickt werden
 
 Liest die verfügbaren Funktionen des Geräts aus und gibt diese als Array aus
```php 
LHD_GetCommands(integer $InstanceID) 
```  
Parameter _$InstanceID_ ObjektID des Harmony Hub Geräts
  
 Sendet einen Befehl an den Logitech Harmony Hub 
```php
LHD_Send(integer $InstanceID, string $Command)
``` 
 Parameter _$InstanceID_ ObjektID des Harmony Hub Geräts
 Parameter _$Command_ Befehl der gesendet werden soll, verfügbare Befehle werden über LHD_GetCommands ausgelesen.
 
### Harmony Hub
 Es können Aktivitäten des Logitech Harmony Hub ausgeführt werden.
 Die aktuelle Akivität des Logitech Harmony Hub wird in der Variable Harmony Activity angezeigt und kann im Webfront geschaltet werden.
 
 Wenn die Aktivität über Funktionen aktualisiert werden soll oder über ein Skript geschaltet sind die folgenden Funktionen zu benutzten:
 Fordert die aktuelle Aktivität des Logitech Harmony Hub an. Der Wert wird in die Variable Harmony Activity gesetzt.
```php
HarmonyHub_getCurrentActivity(integer $InstanceID) 
```   
  Parameter _$InstanceID_ ObjektID des Harmony Hub Splitters

 
 Liest alle verfügbaren Aktivitäten des Logitech Harmony Hub aus und gibt einen Array zurück.
```php
HarmonyHub_GetAvailableAcitivities(integer $InstanceID) 
```   
  Parameter _$InstanceID_ ObjektID des Harmony Hub Splitters
  
  Liest alle verfügbaren Device IDs des Logitech Harmony Hub aus und gibt einen Array zurück.
```php
HarmonyHub_GetHarmonyDeviceIDs(integer $InstanceID) 
```   
   
  Parameter _$InstanceID_ ObjektID des Harmony Hub Splitters 
 
 Schaltet auf die gewünschte Logitech Harmony Hub Aktivität
```php
HarmonyHub_startActivity(integer $InstanceID, integer $activityID)
``` 
  Parameter _$InstanceID_ ObjektID des Harmony Hub Splitters
  Parameter _$activityID_ ID der Harmony Aktivität, verfügbare IDs können über HarmonyHub_GetAvailableAcitivities ausgelesen werden


## 5. Konfiguration:

### Logitech Harmony Hub:

| Eigenschaft      | Typ     | Standardwert | Funktion                                            |
| :--------------: | :-----: | :----------: | :-------------------------------------------------: |
| Open             | boolean | true         | Verbindung zum Logitech Harmony Hub aktiv / deaktiv |
| Host             | string  |              | IP Adresse des Logitech Harmony Hub                 |
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

Credits:
[Logitech Harmony Ultimate Smart Control Hub Library](https://www.symcon.de/forum/threads/22682-Logitech-Harmony-Ultimate-Smart-Control-Hub-library "Logitech-Harmony-Ultimate-Smart-Control-Hub-library") _Zapp_ 



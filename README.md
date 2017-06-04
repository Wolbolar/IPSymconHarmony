# IPSymconHarmony

Modul für IP-Symcon ab Version 4. Ermöglicht die Kommunikation mit einem Logitech Harmony Hub und das Senden von Befehlen über den Logitech Harmony Hub.

Das Modul ist eine Erweiterung und Anpassung der Skripte von Zapp und der Diskussionen aus
https://www.symcon.de/forum/threads/22682-Logitech-Harmony-Ultimate-Smart-Control-Hub-library
als PHP-Modul für IP-Symcon ab Version 4.x.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Anhang](#5-anhang)  

## 1. Funktionsumfang

Mit Hilfe des Logitech Harmony Hub sind Geräte bedienbar, die sonst über IR-Fernbedienungen steuerbar sind oder auch neuere Geräte wie FireTV und AppleTV.
Nähere Informationen zu ansteuerbaren Geräten über den Logitech Harmony Hub unter [Harmony Ultimate Hub](http://www.logitech.com/de-de/product/harmony-ultimate-hub "Harmony Ultimate Hub")

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

Die Instanz 'Modules' unterhalb von Kerninstanzen im Objektbaum von IP-Symcon (>=Ver. 4.x) öffnen und den Button _Hinzufügen_ drücken. Im Feld die folgende URL eintragen und mit _OK_ bestätigen:
	
    `git://github.com/Wolbolar/IPSymconHarmony.git`  
	
![Modules](docs/Modules.png?raw=true "Modulinstallation")	

### b. Einrichtung in IPS

Danach IP-Symcon 4.x eine Instanz hinzufügen (_**CTRL+1**_).

Hier nun als Hersteller _Logitech_ eingeben und _Logitech Harmony Hub_ auswählen.

![Modulauswahl](docs/Harmony-Hub-1.png?raw=true "Modulauswahl")

Im dem sich öffnenden Fenster zunächst bei Erstinstallation folgende Dinge auswählen:

![Modulform 1](docs/Harmony-Form-1.png?raw=true "Modul Form 1")
![Modulform 2](docs/Harmony-Form-2.png?raw=true "Modul Form 2")

**1.** Haken bei Öffnen setzten. Dies ist notwenig damit der I/O Instanz später aktiv ist.

**2.** IP Adresse des Logitech Harmony Hub eintragen

**3.** Port bleibt unverändert auf 5222

**4.** Email Adresse (entspricht Anmeldename) für MyHarmony

**5.** Passwort für MyHarmony

**6.** Vorher ist in IP Symcon eine Kategorie anlegen. Diese ist hier auszuwählen. Unter dieser Kategorie werden die Geräte Instanzen angelegt.

**7.** Hier einen Haken setzten. Wird in einer späteren Version entfernt. Die Variablen CommandOut und IOIN zeigen die ein- und ausgehenden Daten an.

Standardmäßig wird mit dem Druck auf Übernehmen nur die Instanz der Harmony Hub Geräte und das Schalten der Harmony Hub Aktivitäten im Webfront angelegt.
Sollte es gewünscht sein auch einzelne Befehle an die an dem Logitech Harmony Hub angelernten Geräte zu schicken, kann bei Harmony Variablen und /oder Harmony Skript ein Haken gesetzt werden.


**8.** _Optional_ Wenn diese Option gewählt wird werden für jede Befehlsgruppe eines Logitech Harmony Geräts eine Variable zum Schalten aus dem Webfront angelegt. **VORSICHT:** Diese Option sollte **nur gewählt werden wenn noch ausreichend Variablen in IP-Symcon verfügbar sind** oder die Variablenanzahl unbegrenzt ist, da eine hohe Anzahl an Variablen je nach angelernten Geräten im Harmony Hub verbraucht werden kann. Es werden bei jedem Harmony Hub Gerät für jede im Harmony Hub hinterlegte Controllgroup eine Variable zum Schalten im Webfront angelegt. Hier können je nach Anzahl der im Harmony Hub konfigurierten Geräte eine hohe Anzahl an Variablen anfallen.
Die Option ist für IP-Symcon Nutzer gedacht die noch genügend Variablen zur Verfügung haben und Befehle aus dem Webfront absetzten wollen.	

**9.** _Optional_ Dies Option kann gewählt werden als Alternative oder Ergänzung zu 8. Es werden für jede im Harmony Hub hinterlegte Controllgroup eine Subkategorie mit Scripten angelegt.
Das einzelne Script sendet dann denn jeweiligen Befehl (Skriptname) an den Logitech Harmony Hub.


Nach dem Druck auf Übernehmen wird zunächst der User Auth Token für den Logitech Harmony Hub abgefragt und in IP-Symcon abgelegt. Wenn das Abrufen des User Auth Token erfolgreich war kann mit Schritt 10 und 11 vorgefahren werden.

**10.** Nachdem der User Auth Token vorhanden ist kann auf _Konfiguration auslesen_ geklickt werden. Es dauert einen Moment nach vollständigem Empfang vom Logitech Harmony Hub wird die Konfiguration in IP-Symcon in der Variable Harmony Config abgespeichert. Wenn sich etwas an den Geräten im Logitech Harmony Hub ändert ist die Konfiguration erneut auszulesen.

**11.** Sobald die Konfiguration ausgelesen wurde kann auf _Setup Harmony_ gedrückt werden.
Abhängig von der optionalen Auswahl 8 und 9 werden nun die Instanzen, Skripte und Variablen angelegt. 

Im Webfront von IP-Symcon sieht das z.B. dann so aus:
![Webfront](docs/Harmony-Webfront-1.png?raw=true "Webfront")

Es lassen sich über das Webfront oder die Skripte dann Befehle absetzten. Die Aktivität wird im Webfront angezeigt.
Sobald ein Gerät oder Harmony Fernbedienung eine Harmony Aktivität auslöst wird diese auch in IP-Symcon aktualisiert.
Die aktuelle Aktivität wird in der Variable Harmony Activity, diese liegt unter dem Logitech Harmony Splitter, angezeigt. Es wird automatisch ein Link unter der oben gewählten Kategorie zu dieser Variable angelegt.

Die Variablennamen und die Bezeichnung der Befehle werden so angelegt wie diese von der Bezeichnung im Harmony Hub hinterlegt sind. Für jede angelegte Variable wird auch das Beschreibungsfeld genutzt, hier steht der eigentliche Befehl drinnen der an den Harmony Hub gesendet wird. Daher darf das Beschreibungsfeld der Variable nicht geändert werden. Die Bezeichnung der Variable sowie die Befehlsnamen die im Variablenprofile der Variable hinterlegt sind können individuell vom Nutzer angepasst werden. Dabei darf nur nicht die Reihenfolge im Variablenprofil verändert werden.

## 4. Funktionsreferenz

### Logitech Harmony Hub:

### Harmony Devices
 Die Harmony Devices sind über den Splitter und Setup Harmony anzulegen
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


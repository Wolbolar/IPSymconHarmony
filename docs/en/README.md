# IPSymconHarmony
[![PHPModule](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![StyleCI](https://github.styleci.io/repos/60624438/shield?branch=master)](https://github.styleci.io/repos/60624438)


Module for IP Symcon Version 5 and higher. Enables communication with a Logitech Harmony Hub and sending commands through the Logitech Harmony Hub.

## Documentation

**Table of Contents**

1. [Features](#1-features)
2. [Requirements](#2-requirements)
3. [Installation](#3-installation)
4. [Function reference](#4-functionreference)
5. [Configuration](#5-configuration)
6. [Annex](#6-annex)

## 1. Features

With the help of the Logitech Harmony Hub devices can be operated, which are otherwise controllable via IR remote controls or even newer devices such as FireTV and AppleTV which are controlled via Bluetooth.
For more information about controllable devices through the Logitech Harmony Hub, see [Logitech Harmony Elite](https://www.logitech.com/de-de/product/harmony-elite "Logitech Harmony Elite")

Using the module, the devices stored in the Logitech Harmony Hub can be imported into IP-Symcon and then switched from IP-Symcon via the Logitech Harmony Hub.
Harmony activities can be started from IP-Symcon. When the Harmony Hub performs an activity, the current running activity is submitted to IP-Symcon.

### send IR Code :  

 - Send an IR signal through the Logitech Harmony Hub to the hub known devices 

### FireTV:  (Bluetooth)

 - Sending commands to a FireTV   

### Show current activity:  

 - Shows the current active Harmony Hub activity 
 
### Start an activity:  

 - Starts an activity of the Harmony Hub  
	  
## 2. Requirements

- IPS 5.x.
- Logitech Harmony Hub on the same network as IP Symcon
- XMPP activated in the Harmony app

## 3. Installation

### a. Enable XMPP in the Harmony app

Open the Harmony app and then enable XMPP under _Harmony Setup -> Add and Edit Devices and Actions -> Remote Control / Keyboard and Hub -> Enable XMPP_.

### b. Loading the module

Open the IP Console's web console with _http://{IP-Symcon IP}:3777/console/_.

Then click on the module store (IP-Symcon > 5.1) icon in the upper right corner.

![Store](img/store_icon.png?raw=true "open store")

In the search field type

```
Logitech Harmony
```  


![Store](img/module_store_search_en.png?raw=true "module search")

Then select the module and click _Install_

![Store](img/install_en.png?raw=true "install")


#### Install alternative via Modules instance

_Open_ the object tree.

![Objektbaum](img/object_tree.png?raw=true "object tree")	

Open the instance _'Modules'_ below core instances in the object tree of IP-Symcon (>= Ver 5.x) with a double-click and press the _Plus_ button.

![Modules](img/modules.png?raw=true "modules")	

![Plus](img/plus.png?raw=true "Plus")	

![ModulURL](img/add_module.png?raw=true "Add Module")
 
Enter the following URL in the field and confirm with _OK_:


```	
https://github.com/Wolbolar/IPSymconHarmony 
```
    
and confirm with _OK_.    
    
Then an entry for the module appears in the list of the instance _Modules_

By default, the branch _master_ is loaded, which contains current changes and adjustments.
Only the _master_ branch is kept current.

![Master](img/master.png?raw=true "master") 

If an older version of IP-Symcon smaller than version 5.1 (min 4.3) is used, click on the gear on the right side of the list.
It opens another window,

![SelectBranch](img/select_branch_en.png?raw=true "select branch") 

here you can switch to another branch, for older versions smaller than 5.1 (min 4.3) select _Old-Version_ .

### c. Configuration in IPS

If scripts are to be created, they will be placed below a category. First, we create a category in the object tree _right mouse button -> add object -> category_, which we give an arbitrary name such as. _**Harmony devices**_.
Later, all scripts for devices of the Logitech Harmony Hub will be created under this category.

Then add an instance in IP-Symcon 5.x under splitter.

![Add_Splitter](img/add_splitter_en.png?raw=true "Add Splitter")

Here as manufacturer enter _Logitech_ and select _Logitech Harmony Hub_.

![Add Logitech Hub](img/add_splitter_en_1.png?raw=true "Add Logitech Hub")


In the window that opens, first select the following items during initial installation:

![Logitech Hub 1](img/logitech_hub_1.png?raw=true "Logitech Hub 1")


**1.** put slider on active. This is necessary for the I / O instance to be active later.

**2.** Enter the IP address of the Logitech Harmony Hub

**3.** Email address (corresponds to login name) for MyHarmony

**5.** MyHarmony password

**6.** Then press _APPLY CHANGES_ .

![Accept Changes](img/apply_changes_en.png?raw=true "Accept Changes")

**7.** Select _Read Configuration_ and wait a few seconds.

![Logitech Hub 2](img/logitech_hub_2.png?raw=true "Logitech Hub 2")

**8.** After the _Harmony Config_ variable has been updated, press _Setup Harmony_, then the instance can be closed.

Then a configurator is created.

![Konfigurator 1](img/configurator.png?raw=true "Configurator 1")

![Konfigurator 2](img/configurator_2.png?raw=true "Configurator 2")

The configurator now offers the following options:

![Konfigurator 3](img/configurator_3.png?raw=true "Configurator 3")


- _category harmony scripts_ is the category under which scripts are created
- _Harmony variables_ if not active only the instance is created, but scripts can still be used. Activate to switch over variables in the web front. _Attention! Many devices can consume many variables_.
  _Optional_ If this option is selected, a variable for switching from the web front will be created for each command group of a Logitech Harmony device. **CAUTION:** This option should **only be selected if there are still enough variables available in IP-Symcon** or the number of variables is unlimited, as a large number of variables can be consumed depending on the devices learned in the Harmony Hub. Every Harmony Hub device creates a variable for switching in the web front for each Controllgroup stored in the Harmony Hub. Depending on the number of devices configured in the Harmony Hub, a large number of variables may be generated here.
  The option is intended for IP Symcon users who still have enough variables available and want to drop commands from the web front.	
- _Harmony script_ If this is activated, the associated scripts will be created for each already created instance with _Setup Harmony_.
  _Optional_ This option can be chosen as an alternative or addition to variables. A subcategory with scripts is created for each Controllgroup stored in the Harmony Hub.
  The single script then sends the respective command (script name) to the Logitech Harmony Hub.

If something has been changed in the configuration in the Harmony Hub, press _Read Configuration_ and press _Refresh List_.
Individual devices can now be selected in the configurator and _Create_ creates the device in IP-Symcon.


![Konfigurator 4](img/configurator_4.png?raw=true "Configurator 4")

Mit _Setup Harmony_ , scripts are created for each device created in IP Symcon, depending on the settings (see above), and scripts are generated for the activities.

### Webfront Screen

![Webfront](img/Webfront.png?raw=true "Webfront")

You can then send commands via the web front or the scripts. The activity is displayed on the web front.
Once a device or Harmony Remote triggers a Harmony Activity, it will also be updated in IP Symcon.
The current activity is displayed in the variable Harmony Activity, which is located under the Logitech Harmony Splitter. A link under the category selected above is automatically created for this variable.

The variable names and the name of the commands are created as they are stored by the name in the Harmony Hub. For each created variable also the description field is used, here stands the actual command inside which is sent to the Harmony Hub. Therefore, the description field of the variable must not be changed. The name of the variable as well as the command names that are stored in the variable profile of the variable can be customized by the user. However, the order in the variable profile should not change

#### Respond to keystrokes on the Harmony Remote in IP-Symcon

In order to be able to react to keystrokes on a Harmony Remote, an additional device must first be integrated into the Harmony Hub and then integrated into the Harmony action in which the keystroke should react.
To do this, a new instance _SSDP Roku_ must first be created in IP-Symcon under Splitter.

![Webfront](img/SSDP_Roku.png?raw=true "Webfront")


Then another instance _Logitech Harmony Roku Emulator_ should be created.

![RokuEmulator](img/roku_emulator_1.png?raw=true "Roku Emulator")

Select a port and a Harmony Hub in the created instance.

![RokuEmulator](img/roku_emulator_2.png?raw=true "Roku Emulator")

Now the device can be searched for and added in the Harmony app. To do this, click in the Harmony app _Harmony setup_ -> _Add and edit devices and actions_ -> _Devices_ -> _Add device_ -> _Search for WiFi devices_.
After a while, the Harmony app should have found devices in the WLAN and a selection of the devices found should be displayed. There should also be an entry _Roku 3_.
If you click on the _i_ (info symbol) you should see the name IP-Symcon (Roku Device). With _Next_ the device is added to the devices of the Harmony Hub.
The device can now either be switched individually from the app or the device is integrated into Harmony actions.

#### Dim the light down on Play and dim it up during a break

After the Roku device has been added, this is also integrated into the Harmony action in which the key presses are to be evaluated.

![TVRoku1](img/tv_roku1.png?raw=true "TVRoku1") 

Then switch to the key assignment settings in MyHarmony and assign the key a command from the _IP-Symcon Roku 3_ device

![TVRoku2](img/tv_roku2.png?raw=true "TVRoku2") 

In IP-Symcon, the _Logitech Harmony Roku Emulator_ instance shows the last key press that was performed on the Roku Emulator device.
 
![TVRoku3](img/tv_roku3.png?raw=true "TVRoku3") 

In the instance, a script can be assigned to the key commands per action, which is executed as soon as the key has been pressed.

![TVRoku4](img/tv_roku4.png?raw=true "TVRoku4") 

Only the commands shown in IP-Symcon are transferred from the Harmony Remote to IP-Symcon. So you can e.g. when pressing Play the command _Play_ and when pressing Pause
assign the command _Instant Replay_ in the MyHarmony app. Now the appropriate script has to be assigned, in the example picture the scripts are called _Dimming up lighting_ and _Dimming down lighting_.
The respective scripts now include all commands that should be executed as soon as the play button is pressed. This is usually the standard function of the device that is controlled in the Harmony action and other commands.

An example of dimming a Hue lamp on Play

```php
<?php
IPS_RunScript(53512); // Dreambox Play
$list = ["BRIGHTNESS" => 0, "TRANSITIONTIME" => 40];// Helligkeit in (0 bis 254), transitiontime x 100ms 10 entspricht 1 s
$lightId = 33485; // Coachlampen
HUE_SetValues($lightId, $list);
``` 

Any device that can be controlled from IP-Symcon can then be addressed in the script. So you can e.g. also dim KNX, LCN, Homematic, Hue etc.

The settings are to be made for each Harmony action in which key presses are to be evaluated.

So you can then integrate the device into the Harmony actions, in which you want to evaluate a keystroke of the Harmony Remote, and place it on the buttons of the Harmony.
IP-Symcon then evaluates this keystroke and any device that is controlled by IP-Symcon can be switched in this way.

## 4. Function reference

### Logitech Harmony Hub:

### Harmony Devices
The Harmony Devices are to be created via the configurator
A command can be sent to each device
 
Reads out the available functions of the device and outputs them as an array
```php 
LHD_GetCommands(integer $InstanceID) 
```  
Parameter _$InstanceID_ ObjectID of the Harmony Hub device

  
 Sends a command to the Logitech Harmony Hub 
```php
LHD_Send(integer $InstanceID, string $Command)
``` 
 Parameter _$InstanceID_ ObjectID of the Harmony Hub device
 Parameter _$Command_ Command to be sent, available commands are read out via LHD_GetCommands.
 
### Harmony Hub
Activities of the Logitech Harmony Hub can be performed.
The current activity of the Logitech Harmony Hub is displayed in the variable Harmony Activity and can be switched on the web front.
 
If the activity is to be updated via functions or switched via a script, the following functions are to be used:
Requests the current activity of the Logitech Harmony Hub. The value is set in the variable Harmony Activity.
```php
HarmonyHub_getCurrentActivity(integer $InstanceID) 
```   
Parameter _$InstanceID_ ObjektID of the Harmony Hub Splitter

 
Reads all available activities of the Logitech Harmony Hub and returns an array.
```php
HarmonyHub_GetAvailableAcitivities(integer $InstanceID) 
```   
Parameter _$InstanceID_ ObjektID of the Harmony Hub Splitter
  
Reads all available Device IDs of the Logitech Harmony Hub and returns an array.
```php
HarmonyHub_GetHarmonyDeviceIDs(integer $InstanceID) 
```   
   
Parameter _$InstanceID_ of the Harmony Hub Splitter
 
Switches to the desired Logitech Harmony Hub activity
```php
HarmonyHub_startActivity(integer $InstanceID, integer $activityID)
``` 
Parameter _$InstanceID_ ObjektID of the Harmony Hub Splitter
Parameter _$activityID_ ID of the Harmony Activity, available IDs can be read out via HarmonyHub_GetAvailableAcitivities

## 5. Configuration:

### Logitech Harmony Hub:

| Property         | Type    | Standard Value | Function                                            |
| :--------------: | :-----: | :------------: | :-------------------------------------------------: |
| Open             | boolean | true           | Connect to Logitech Harmony Hub Active / Disable    |
| Host             | string  |                | IP address of the Logitech Harmony Hub              |
| Email            | string  |                | Email address to login MyHarmony                    |
| Passwort         | string  |                | Password to log in MyHarmony                        |
| ImportCategoryID | integer |                | ObjectID of the import category                     |
| HarmonyVars      | boolean |                | Active creates variables per control group          |
| HarmonyScript    | boolean |                | Active creates a script for each command            |


### Logitech Harmony Device:  

| Property        | Type    | Standard Value | Function                                                              |
| :-------------: | :-----: | :------------: | :-------------------------------------------------------------------: |
| Name            | string  |                | Name of the device                                                    |
| DeviceID        | integer |                | DeviceID of the device                                                |
| BluetoothDevice | boolean |     false      | Bluetooth Device                                                      |


## 6. Annnex

###  b. GUIDs und Data Flow:

#### Logitech Harmony Hub Splitter:

GUID: `{7E03C651-E5BF-4EC6-B1E8-397234992DB4}` 


#### Logitech Harmony Device:

GUID: `{C45FF6B3-92E9-4930-B722-0A6193C7FFB5}` 

Credits:
[Logitech Harmony Ultimate Smart Control Hub Library](https://www.symcon.de/forum/threads/22682-Logitech-Harmony-Ultimate-Smart-Control-Hub-library "Logitech-Harmony-Ultimate-Smart-Control-Hub-library") _Zapp_ 

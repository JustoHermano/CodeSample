
# Code Sample Repository

This is a small php code sample working with a home automation API. This script allow you to get recently added devices, get avbliable device action and run those actions.






## Environment Variables

To run this project, you will need to add the following environment variables to your .env file

`HOME_API_URL`

`HOME_API_TOKEN`


## Usage/Examples
Get recently added devices
```
php CodeSample.php --cmd=getRecentDevices
10 Most Recent Devices:
Name: [WiFi] Dan's Pixel 2 Presence  ID: 129
Name: Sengled Element Classic ID: 641
Name: Front Door Virtual Button ID: 65
Name: Generic Zigbee Outlet ID: 1
Name: Sengled Element Classic ID: 769
Name: Inovelli Z-Wave Smart Scene Dimmer S2 ID: 386
Name: Samsung Zigbee Button ID: 2
Name: [Group] Basement Fitness office ID: 387
Name: Sengled Element Classic ID: 3
Name: Sengled Element Classic ID: 4
```

To get a device's actions
```
php CodeSample.php --cmd=getDeviceActions --id=681

The following actions can be performed on [Group] Office
off
on
setColor
setColorTemperature
setHue
setLevel
setSaturation
```


To run an action on a device for example this turns on the device with id 681
```
php CodeSample.php --cmd=runAction --id=681 --action=on
```





This plugin add new REST API command, which collect data about customer and orders for Freshdesk Connector Application 
(https://www.freshworks.com/apps/freshdesk/shopware_5_connector/)

Installation guide:

1) Download the ZIP archive with the module from Github. 
2) Rename the plugin dir in archive - remove tag or branch name (MorfFreshdeskApi-1.0.0 -> MorfFreshdeskApi)
3) Unpack this archive to "custom/plugins" of Shopware 5 folder.
4) Go to your Shopware 5 admin panel > Configurations > Plugin Manager > Installed. 
5) Find "Freshdesk Connector" in the "Uninstalled" tab
6) Open the "Freshdesk Connector" plugin and click "Activate".

OR

1) Download the ZIP archive with the module from Github. 
2) Rename the plugin dir in archive - remove tag or branch name (MorfFreshdeskApi-1.0.0 -> MorfFreshdeskApi)
3) Go to your Shopware 5 admin panel > Configurations > Plugin Manager > Installed. 
4) Click on "Upload plugin" and select ZIP archive with the module
5) Open the "Freshdesk Connector" plugin and click "Activate".

OR

Use following commands in Shopware 5 store dir:
```
cd custom/plugins
git clone git@github.com:MorfDev/MorfFreshdeskApi.git MorfFreshdeskApi
```

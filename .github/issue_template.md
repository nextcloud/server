<!--
Thanks for reporting issues back to Nextcloud! This is the issue tracker of Nextcloud, if you have any support question please check out https://nextcloud.com/support

This is the bug tracker for the Server component. Find other components at https://github.com/nextcloud/

For reporting potential security issues please see https://nextcloud.com/security/

To make it possible for us to help you please fill out below information carefully.
--> 
### Steps to reproduce
1.
2.
3.

### Expected behaviour
Tell us what should happen

### Actual behaviour
Tell us what happens instead

### Server configuration
**Operating system**:

**Web server:**

**Database:**

**PHP version:**

**Nextcloud version:** (see Nextcloud admin page)

**Updated from an older Nextcloud/ownCloud or fresh install:**

**Where did you install Nextcloud from:**

**Signing status:**
<details>
<summary>Signing status</summary>

```
Login as admin user into your Nextcloud and access 
http://example.com/index.php/settings/integrity/failed 
paste the results here.
```
</details>

**List of activated apps:**
<details>
<summary>App list</summary>

```
If you have access to your command line run e.g.:
sudo -u www-data php occ app:list
from within your Nextcloud installation folder
```
</details>

**The content of config/config.php:**
<details>
<summary>Config report</summary>

```
If you have access to your command line run e.g.:
sudo -u www-data php occ config:list system
from within your Nextcloud installation folder

or 

Insert your config.php content here
(Without the database password, passwordsalt and secret)
```
</details>

**Are you using external storage, if yes which one:** local/smb/sftp/...

**Are you using encryption:** yes/no

**Are you using an external user-backend, if yes which one:** LDAP/ActiveDirectory/Webdav/...

#### LDAP configuration (delete this part if not used)
<details>
<summary>LDAP config</summary>

```
With access to your command line run e.g.:
sudo -u www-data php occ ldap:show-config
from within your Nextcloud installation folder

Without access to your command line download the data/owncloud.db to your local
computer or access your SQL server remotely and run the select query:
SELECT * FROM `oc_appconfig` WHERE `appid` = 'user_ldap';


Eventually replace sensitive data as the name/IP-address of your LDAP server or groups.
```
</details>

### Client configuration
**Browser:**

**Operating system:**

### Logs
#### Web server error log
<details>
<summary>Web server error log</summary>
```
Insert your webserver log here
```
</details>

#### Nextcloud log (data/nextcloud.log)
<details>
<summary>Nextcloud log</summary>
```
Insert your Nextcloud log here
```
</details>

#### Browser log
<details>
<summary>Browser log</summary>
```
Insert your browser log here, this could for example include:

a) The javascript console log
b) The network log
c) ...
```
</details>

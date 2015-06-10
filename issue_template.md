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

**ownCloud version:** (see ownCloud admin page)

**Updated from an older ownCloud or fresh install:**

**List of activated apps:**

```
If you have access to your command line run e.g.:
sudo -u www-data php occ app:list
from within your ownCloud installation folder
```

**The content of config/config.php:**

```
If you have access to your command line run e.g.:
sudo -u www-data php occ config:list system --public
from within your ownCloud installation folder

or 

Insert your config.php content here
(Without the database password, passwordsalt and secret)
```

**Are you using external storage, if yes which one:** local/smb/sftp/...

**Are you using encryption:** yes/no

**Are you using an external user-backend, if yes which one:** LDAP/ActiveDirectory/Webdav/...

#### LDAP configuration (delete this part if not used)

```
On ownCloud 7+ with access to your command line run e.g.:
sudo -u www-data php occ ldap:show-config
from within your ownCloud installation folder

On ownCloud 6 with access to your command line run e.g.:
sqlite3 data/owncloud.db or mysql -u root -p owncloud
then execute:  select * from oc_appconfig where appid='user_ldap';

Without access to your command line download the data/owncloud.db to your local
computer or access your SQL server remotely and run the select query above.

Eventually replace sensitive data as the name/IP-address of your LDAP server or groups.
```

### Client configuration
**Browser:**

**Operating system:**

### Logs
#### Web server error log
```
Insert your webserver log here
```

#### ownCloud log (data/owncloud.log)
```
Insert your ownCloud log here
```

#### Browser log
```
Insert your browser log here, this could for example include:

a) The javascript console log
b) The network log 
c) ...
```

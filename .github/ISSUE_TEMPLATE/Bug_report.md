---
name: 🐛 Bug report
about: Help us improving by reporting a bug
labels: bug, 0. Needs triage
---

<!--
If you are using nextcloud-snap, for bug reports or support
you should first head to:

https://github.com/nextcloud-snap/nextcloud-snap/issues

You can file a second bug report here if your issue
cannot be resolved there first.

For issues related to the Nextcloud Docker image, head to:

https://github.com/nextcloud/docker/issues
-->

<!--
Thanks for reporting issues back to Nextcloud!

Note: This is the **issue tracker of Nextcloud**, please do NOT use this to get answers to your questions or get help for fixing your installation. This is a place to report bugs to developers, after your server has been debugged. You can find help debugging your system on our home user forums: https://help.nextcloud.com or, if you use Nextcloud in a large organization, ask our engineers on https://portal.nextcloud.com. See also  https://nextcloud.com/support for support options.

Nextcloud is an open source project backed by Nextcloud GmbH. Most of our volunteers are home users and thus primarily care about issues that affect home users. Our paid engineers prioritize issues of our customers. If you are neither a home user nor a customer, consider paying somebody to fix your issue, do it yourself or become a customer.

Guidelines for submitting issues:

* Please search the existing issues first, it's likely that your issue was already reported or even fixed.
    - Go to https://github.com/nextcloud and type any word in the top search/command bar. You probably see something like "We couldn’t find any repositories matching ..." then click "Issues" in the left navigation.
    - You can also filter by appending e. g. "state:open" to the search string.
    - More info on search syntax within github: https://help.github.com/articles/searching-issues
    
* This repository https://github.com/nextcloud/server/issues is *only* for issues within the Nextcloud Server code. This also includes the apps: files, encryption, external storage, sharing, deleted files, versions, LDAP, and WebDAV Auth
  
* SECURITY: Report any potential security bug to us via our HackerOne page (https://hackerone.com/nextcloud) following our security policy (https://nextcloud.com/security/) instead of filing an issue in our bug tracker.  

* The issues in other components should be reported in their respective repositories: You will find them in our GitHub Organization (https://github.com/nextcloud/)
  
* You can also use the Issue Template app to prefill most of the required information: https://apps.nextcloud.com/apps/issuetemplate
-->

<!--- Please keep this note for other contributors -->

### How to use GitHub

* Please use the 👍 [reaction](https://blog.github.com/2016-03-10-add-reactions-to-pull-requests-issues-and-comments/) to show that you are affected by the same issue.
* Please don't comment if you have no relevant information to add. It's just extra noise for everyone subscribed to this issue.
* Subscribe to receive notifications on status change and new comments. 


## Bug Description

### Steps to reproduce
1.
2.
3.

### Expected behaviour
<!--- Tell us what should happen -->

### Actual behaviour
<!--- Tell us what happens instead -->

## Configuration

### Server configuration
<!--
Much of this information can be found in the
server admin panel at, e.g.:

https://example.com/index.php/settings/admin/serverinfo

or with the following steps:

* Click your user icon in the upper right corner
* Click "Settings"
* On the lefthand side, scroll down and click "System"
  (which should be the last item on the list)
-->
* Operating system: <!-- e.g. "Ubuntu 20.04.3 LTS" -->
* Web server: <!-- From server admin panel -->
* Database: <!-- From server admin panel -->
* PHP version: <!-- From server admin panel -->
* Nextcloud version: <!-- From server admin panel -->
* Nextcloud installed as: <!-- Update from an older Nextcloud/ownCloud or fresh install -->
* Nextcloud installed from: <!-- e.g. archive, setup-nextcloud.php, virtual machine, docker, snap, etc. -->
<!--
Note, again, that bug reports for Nextcloud appliance
installations can be directed to other issue trackers first.

Snap:
https://github.com/nextcloud-snap/nextcloud-snap/issues

Docker:
https://github.com/nextcloud/docker/issues
-->

### Signing status:
<details>
<summary>Signing status</summary>
<!--
Login as admin user into your Nextcloud and access 
http://example.com/index.php/settings/integrity/failed
-->

<!-- Paste content here -->

</details>

### List of activated apps
<details>
<summary>App list</summary>
<!-- 
If you have access to your command line run e.g.:

$ sudo -u www-data php occ app:list
$ sudo nextcloud.occ app:list

from within your Nextcloud installation folder
-->

<!-- Paste content here -->

</details>

### Nextcloud configuration
<details>
<summary>Config report</summary>
<!--
If you have access to your command line run e.g.:

$ sudo -u www-data php occ config:list system
$ sudo nextcloud.occ config:list system

from within your Nextcloud installation folder

or from your config.php, located at, e.g.:

/var/www/html/config/config.php
/var/snap/nextcloud/<version_number>/nextcloud/config/config.php

Make sure to remove all sensitive content such as passwords.
(e.g. database password, passwordsalt, secret, smtp password, …)
-->

```php
<!-- Paste content here -->
````

</details>

* Are you using external storage, if yes which one: <!-- local/smb/sftp/... -->
* Are you using encryption: <!-- yes/no -->
* Are you using an external user-backend, if yes which one: <!-- LDAP/ActiveDirectory/Webdav/... -->

### LDAP configuration (delete this part if not used)
<details>
<summary>LDAP config</summary>
<!-- 
With access to your command line run e.g.:
$ sudo -u www-data php occ ldap:show-config
from within your Nextcloud installation folder

Without access to your command line download the data/owncloud.db to your local
computer or access your SQL server remotely and run the select query:
SELECT * FROM `oc_appconfig` WHERE `appid` = 'user_ldap';

Make sure to remove sensitive data as the name/IP-address of your LDAP server or groups.
-->
```
<!-- Paste content here -->
```

</details>

### Client configuration

* Browser: <!-- e.g. "Firefox 92.0.1 (64-bit)" -->
* Operating system: <!-- e.g. "macOS 11.5.2" or "Ubuntu 20.04.3 LTS" -->

## Logs
<!-- Reports without logs might be closed as unqualified reports! -->

### Web server error log
<details>
<summary>Web server error log</summary>
<!--
May be located at:

/etc/apache2/error.log
/var/log/apache2/error.log
/var/log/httpd/error_log
/var/snap/nextcloud/<version_number>/logs/apache_errors.log
/var/snap/nextcloud/<version_number>/mysql/error.log
-->
```
<!-- Paste content here -->
```

</details>

### Nextcloud log (data/nextcloud.log)
<details>
<summary>Nextcloud log</summary>
<!--
May be located at:

/var/www/nextcloud/data/nextcloud.log
/var/snap/nextcloud/common/nextcloud/data/nextcloud.log

Alternately, from the server admin panel:

https://exampe.com/index.php/settings/admin/logging

or with the following steps:

* Click your user icon in the upper right corner
* Click "Settings"
* On the lefthand side, scroll down and click "Logging"
  (which should be the second-to-last item)
* If there isn't anything shown, you can expand the log scope
  by clicking "..." and selecting additional categories
-->
```
<!-- Paste content here -->
```

</details>

### Browser log
<details>
<summary>Browser log</summary>
<!-- 
This could for example include:

a) The javascript console log
b) The network log
c) ...

If you have difficulty finding logs for your browser,
you can do a web search for "<your_browser_name> logs".
Note that some types of logging may need to be manually enabled.

If you are using macOS, in your browser's "Help" menu,
there should be a search box where you can search for "console".

Keyboard shortcuts for opening browser developer tools
or the JavaScript console include:

* Chrome/Edge/Brave: Ctrl-Shift-I/Cmd-Shift-I
* Firefox: Ctrl-Shift-J/Cmd-Shift-J
* Safari: Cmd-Alt-C
-->

<!-- Which log is this? -->
```
<!-- Paste content here -->
```
    
<!-- Use separate blocks for additional logs:
 
Which log is this?
```
Paste content here
```
-->

</details>

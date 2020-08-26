---
name: 🐛 Bug report
about: Help us improving by reporting a bug
labels: bug, 0. Needs triage
---

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
  
* You can also use the Issue Template app to prefill most of the required information: https://apps.nextcloud.com/apps/issuetemplate (it may be outdated with the current version of the template, but is still viable!)
-->

<!--- Please keep this note for other contributors -->

### How to use GitHub

* Please use the 👍 [reaction](https://blog.github.com/2016-03-10-add-reactions-to-pull-requests-issues-and-comments/) to show that you are affected by the same issue.
* Please don't comment if you have no relevant information to add. It's just extra noise for everyone subscribed to this issue.
* Subscribe to receive notifications on status change and new comments. 



### Summary
<!-- Brief summary of the issue consisting of 1-3 Sentences. Example:-->
Downloading a file containing the word `xmas` causes the server to do a barrel roll. Does not occur when the server is wearing a Halloween-Costume.



### Steps to reproduce
<!-- Github is smart enough to count by itsself -->
1. Open Homepage
1. Navigate to Folder x y z
1. Describe what action leads to the bug
1. ... 
1. Profit?



### Expected behaviour
Tell us what should happen



### Actual behaviour
Tell us what happens instead



### Server Configuration
<!-- If the issue only occurs to specific users/client configurations please fill out the "Client Configuration"-Section below. -->
**Server OS:** [replace with OS and version]
**Nextcloud version:** [replace with OS and version] (see Nextcloud admin page)
**Lifetime of Nextcloud:** [replace with an approximate age of the nextcloud installation, e.g. 'fresh install', 'few weeks', 'several years']
**PHP version:** [replace with PHP version]
**Web server:**
<!-- Please change the appropriate [ ] to [x] and (if applicable) add the version number behind it. -->
- [ ] Apache
- [ ] Nginx
- [ ] _something else_ <!-- please specify -->

**Database:** <!-- Don't forget adding the version -->
- [ ] MySQL
- [ ] MariaDB
- [ ] PostgreSQL
- [ ] OracleDB
- [ ] SQLite

**Fresh install or updated from an older Nextcloud/ownCloud?:**
- [ ] fresh installation
  - [ ] using the webinstaller
  - [ ] using the archive file
  - [ ] using docker image
  - [ ] using snap
  - [ ] _something else_ <!-- please specify -->
- [ ] upgrade (from [replace with old version])
  - [ ] update from webinterface
  - [ ] update using `php updater/updater.phar`
  - [ ] manual upgrade using unpacking of archive and `php occ upgrade`
  - [ ] _something else_ <!-- please specify -->
  
**Are you using encryption?**
- [ ] yes
- [ ] no

**Which Storage-Solution(s) do you use for your nextcloud?**
<!-- This Section asks for the location `Data`-Folder AND any configured external Storage you are using -->
- [ ] Local <!-- e.g. the `Data`-Folder is on the same Device as the nextcloud server -->
- [ ] NFS
- [ ] Samba/smb/Windows Share(s) <!-- if you know which, please clarify if smb1, smb2 or smb3 -->
- [ ] SFTP
- [ ] WebDAV
- [ ] _something else_ <!-- please specify and add an additional bullet point for each solution -->

**Which User-Backend are you using:**
- [ ] internal 
- [ ] ActiveDirectory
- [ ] LDAP <!-- Please fill out the "LDAP-Configuration"-Section below! -->
- [ ] WebDAV
- [ ] _something else_ <!-- please specify -->



<!-- 
###############################################################
###############################################################
The following are templates for further details that can greatly help in narrowing down the issue!
So please fill out as much as you can of them! 

CAUTION! For some sections you might need to filter out sensitive information like IP-Adresses or Passwords!

If it seems to you, that a Section is not relevant to the issue at hand, please remove it. 
PLEASE fill out the "Nextcloud configuration"- and "Nextcloud Log"-Sections!
###############################################################
###############################################################
-->

### List of activated apps
<!-- 
Some apps can cause unforessen issues when used together. 
Knowing which apps installed are installed helps in finding the culprit.
-->
<details>
<summary>App list</summary>

```
If you have access to your command line run e.g.:
sudo -u www-data php occ app:list
from within your Nextcloud installation folder. 
If you don't please please remove this section.
```

</details>



### Client configuration
<!-- When an issue is limited to a specific Browser configuration it helps greatly to know what that configuration is! -->
<details>
<summary>Client Config</summary>

```
Browser: [please include the Version]
Operating system: [please include the kernel-version or windows build number]

if there are errors or warnings in the Browser Console (CTRL+SHIFT+J), please paste them here!
Make sure they don't contain sensitive information (like filenames/IP-Adresses/...)!

This section is for all kinds of client-side logs. Maybe the Browsers Network-Log might be relevant, then this would be the place to put it.
```

</details>



### Nextcloud configuration
<!-- This is a quite important section, as many issues arise from an oversight in the config! -->
<details>
<summary>Config report</summary>

```
If you have access to your command line run e.g.:
sudo -u www-data php occ config:list system
from within your Nextcloud installation folder

or 

Insert your [nextcloud]/config/config.php content here. 
Make sure to remove all sensitive content such as passwords. (e.g. database password, passwordsalt, secret, smtp password, …)
```

</details>



### LDAP configuration
<!-- Important for when you are using LDAP as a user-backend! -->
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



### Nextcloud log
<!-- As with most issues: Knowing what the program has to say about it REALLY helps in knowing why something doesn't work as expected! -->
<details>
<summary>Nextcloud log</summary>

```
Depending on your configuration you might have more than one 'nextcloud.log'
Either in "[nextcloud]/data/nextcloud.log"
or in "[yourDataPath]/nextcloud.log"
The important one is usually the one with the more recent modification time.

We do NOT NEED THE ENTIRE Log. Please go to the end of the logfile and look for the applicable line.
Then please add a few lines before and after that and remove any sensitive information (e.g. remoteAddr, user, parts of url, parts of message, if those contain sensitive information).
To find out which line is the important first look for the correct username in the '"user":'-Field 
then look for the appropriate '"message":'-Field.


EXAMPLE:
{"reqId":"PFfc77ZgiEQJ30W51INt","level":2,"time":"2020-08-01T01:00:00+00:00","remoteAddr":"REDACTED","user":"REDACTED","app":"fulltextsearch","method":"GET","url":"/index.php/settings/user","message":"Issue while loading Provider: ocsms/OCA\\OcSms\\Provider\\FullTextSearchProvider - OCP\\AppFramework\\QueryException Could not resolve OCA\\OcSms\\Provider\\FullTextSearchProvider! Class OCA\\OcSms\\Provider\\FullTextSearchProvider does not exist","userAgent":"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:79.0) Gecko/20100101 Firefox/79.0","version":"19.0.1.1"}
{"reqId":"naeop61NUmHvPzglsn8r","level":2,"time":"2020-08-01T01:00:01+00:00","remoteAddr":"REDACTED","user":"REDACTED","app":"fulltextsearch","method":"GET","url":"/index.php/settings/admin/overview","message":"Issue while loading Provider: ocsms/OCA\\OcSms\\Provider\\FullTextSearchProvider - OCP\\AppFramework\\QueryException Could not resolve OCA\\OcSms\\Provider\\FullTextSearchProvider! Class OCA\\OcSms\\Provider\\FullTextSearchProvider does not exist","userAgent":"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:79.0) Gecko/20100101 Firefox/79.0","version":"19.0.1.1"}
{"reqId":"DYuAX24xsi7XFUceyoW5","level":2,"time":"2020-08-01T01:00:02+00:00","remoteAddr":"REDACTED","user":"REDACTED","app":"fulltextsearch","method":"GET","url":"/index.php/settings/admin/overview","message":"Issue while loading Provider: ocsms/OCA\\OcSms\\Provider\\FullTextSearchProvider - OCP\\AppFramework\\QueryException Could not resolve OCA\\OcSms\\Provider\\FullTextSearchProvider! Class OCA\\OcSms\\Provider\\FullTextSearchProvider does not exist","userAgent":"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:79.0) Gecko/20100101 Firefox/79.0","version":"19.0.1.1"}
```

</details>



### Signing status
<!-- This section helps identify, why some features/apps might be flunky. -->
<details>
<summary>Signing status</summary>

```
If you can login to your nextcloud, please replace this text with the content of 
the url http://your.domain/[pathToNextCloud]/index.php/settings/integrity/failed 
(e.g. http://nextcloud.your.domain/index.php/settings/integrity/failed)
here.
```

</details>



### Updater log
<!-- If you encounter the given issue after an update, the log of the update is also quite helpful -->
<details>
<summary>Updater log</summary>

```
As with the nextcloud.log there are multiple locations where the updater.log might be!
Either in "[nextcloud]/data/updater.log"
or in "[datadirectory]/updater.log"

In the case of the updater the full logfile would be helpful, as it can be quite hard to identify the issue in it.
Make sure to remove sensitive paths in it. e.g. lines containing
- "configFileName [absolute path to config.php]"
- "storage location: [absolute path to archive download location in data-folder]"
- A few lines after "checkForExpectedFilesAndFolders()" might be a list of unexpected/modified files in the nextcloud-folder
  Example for this last point looks like this: 
    #0 /var/www/nextcloud/updater/index.php(1330): Updater->checkForExpectedFilesAndFolders()
    #1 {main}
    File:/var/www/nextcloud/updater/index.php
    Line:395
    Data:
    Array
    (
      [0] => a_random_file_that_does_not_belong_here.log
      [1] => another_random_file.log
    )

```

</details>



### Web server error
<!-- If, for example, you get "Internal Server Errors" this greatly helps narrowing why. -->
<details>
<summary>Web server error</summary>

```
Insert the specific Error(s) here. 
Make sure to remove sensitive Data like IP-Adresses.
```

</details>

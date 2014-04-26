<?php

/* Only enable this for local development and not in productive environments */
/* This will disable the minifier and outputs some additional debug informations */
define("DEBUG", true);

$CONFIG = array(
/* Flag to indicate ownCloud is successfully installed (true = installed) */
"installed" => false,

/* Type of database, can be sqlite, mysql or pgsql */
"dbtype" => "sqlite",

/* Name of the ownCloud database */
"dbname" => "owncloud",

/* User to access the ownCloud database */
"dbuser" => "",

/* Password to access the ownCloud database */
"dbpassword" => "",

/* Host running the ownCloud database */
"dbhost" => "",

/* Prefix for the ownCloud tables in the database */
"dbtableprefix" => "",

/* Define the salt used to hash the user passwords. All your user passwords are lost if you lose this string. */
"passwordsalt" => "",

/* Force use of HTTPS connection (true = use HTTPS) */
"forcessl" => false,

/* Blacklist a specific file and disallow the upload of files with this name - WARNING: USE THIS ONLY IF YOU KNOW WHAT YOU ARE DOING. */
"blacklisted_files" => array('.htaccess'),

/* The automatic hostname detection of ownCloud can fail in certain reverse proxy situations. This option allows to manually override the automatic detection. You can also add a port. For example "www.example.com:88" */
"overwritehost" => "",

/* The automatic protocol detection of ownCloud can fail in certain reverse proxy situations. This option allows to manually override the protocol detection. For example "https" */
"overwriteprotocol" => "",

/* The automatic webroot detection of ownCloud can fail in certain reverse proxy situations. This option allows to manually override the automatic detection. For example "/domain.tld/ownCloud" */
"overwritewebroot" => "",

/* The automatic detection of ownCloud can fail in certain reverse proxy situations. This option allows to define a manually override condition as regular expression for the remote ip address. For example "^10\.0\.0\.[1-3]$" */
"overwritecondaddr" => "",

/* A proxy to use to connect to the internet. For example "myproxy.org:88" */
"proxy" => "",

/* The optional authentication for the proxy to use to connect to the internet. The format is: [username]:[password] */
"proxyuserpwd" => "",

/* List of trusted domains, to prevent host header poisoning ownCloud is only using these Host headers */
'trusted_domains' => array('demo.owncloud.org', 'otherdomain.owncloud.org'),

/* Theme to use for ownCloud */
"theme" => "",

/* Optional ownCLoud default language - overrides automatic language detection on public pages like login or shared items. This has no effect on the users's language preference configured under "personal -> language" once they have logged in */
"default_language" => "en",

/* Path to the parent directory of the 3rdparty directory */
"3rdpartyroot" => "",

/* URL to the 3rdparty directory, as seen by the browser */
"3rdpartyurl" => "",

/* Default app to load on login */
"defaultapp" => "files",

/* Enable the help menu item in the settings */
"knowledgebaseenabled" => true,

/* URL to use for the help page, server should understand OCS */
"knowledgebaseurl" => "http://api.apps.owncloud.com/v1",

/* Enable installing apps from the appstore */
"appstoreenabled" => true,

/* URL of the appstore to use, server should understand OCS */
"appstoreurl" => "http://api.apps.owncloud.com/v1",

/* Enable SMTP class debugging */
"mail_smtpdebug" => false,

/* Mode to use for sending mail, can be sendmail, smtp, qmail or php, see PHPMailer docs */
"mail_smtpmode" => "sendmail",

/* Host to use for sending mail, depends on mail_smtpmode if this is used */
"mail_smtphost" => "127.0.0.1",

/* Port to use for sending mail, depends on mail_smtpmode if this is used */
"mail_smtpport" => 25,

/* SMTP server timeout in seconds for sending mail, depends on mail_smtpmode if this is used */
"mail_smtptimeout" => 10,

/* SMTP connection prefix or sending mail, depends on mail_smtpmode if this is used.
   Can be '', ssl or tls */
"mail_smtpsecure" => "",

/* authentication needed to send mail, depends on mail_smtpmode if this is used
 * (false = disable authentication)
 */
"mail_smtpauth" => false,

/* authentication type needed to send mail, depends on mail_smtpmode if this is used
 * Can be LOGIN (default), PLAIN or NTLM */
"mail_smtpauthtype" => "LOGIN",

/* Username to use for sendmail mail, depends on mail_smtpauth if this is used */
"mail_smtpname" => "",

/* Password to use for sendmail mail, depends on mail_smtpauth if this is used */
"mail_smtppassword" => "",

/* How long should ownCloud keep deleted files in the trash bin, default value:  180 days */
'trashbin_retention_obligation' => 180,

/* allow user to change his display name, if it is supported by the back-end */
'allow_user_to_change_display_name' => true,

/* Check 3rdparty apps for malicious code fragments */
"appcodechecker" => "",

/* Check if ownCloud is up to date */
"updatechecker" => true,

/* Place to log to, can be owncloud and syslog (owncloud is log menu item in admin menu) */
"log_type" => "owncloud",

/* File for the owncloud logger to log to, (default is ownloud.log in the data dir */
"logfile" => "",

/* Loglevel to start logging at. 0=DEBUG, 1=INFO, 2=WARN, 3=ERROR (default is WARN) */
"loglevel" => "",

/* Lifetime of the remember login cookie, default is 15 days */
"remember_login_cookie_lifetime" => 60*60*24*15,

/* Life time of a session after inactivity */
"session_lifetime" => 60 * 60 * 24,

/* Custom CSP policy, changing this will overwrite the standard policy */
"custom_csp_policy" => "default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; frame-src *; img-src *; font-src 'self' data:; media-src *",

/* Enable/disable X-Frame-Restriction */
/* HIGH SECURITY RISK IF DISABLED*/
"xframe_restriction" => true,

/* The directory where the user data is stored, default to data in the owncloud
 * directory. The sqlite database is also stored here, when sqlite is used.
 */
// "datadirectory" => "",

/* Enable maintenance mode to disable ownCloud */
"maintenance" => false,

"apps_paths" => array(

/* Set an array of path for your apps directories
 key 'path' is for the fs path and the key 'url' is for the http path to your
 applications paths. 'writable' indicate if the user can install apps in this folder.
 You must have at least 1 app folder writable or you must set the parameter : appstoreenabled to false
*/
	array(
		'path'=> '/var/www/owncloud/apps',
		'url' => '/apps',
		'writable' => true,
	),
),
'user_backends'=>array(
	array(
		'class'=>'OC_User_IMAP',
		'arguments'=>array('{imap.gmail.com:993/imap/ssl}INBOX')
	)
),
//links to custom clients
'customclient_desktop' => '', //http://owncloud.org/sync-clients/
'customclient_android' => '', //https://play.google.com/store/apps/details?id=com.owncloud.android
'customclient_ios' => '', //https://itunes.apple.com/us/app/owncloud/id543672169?mt=8

// date format to be used while writing to the owncloud logfile
'logdateformat' => 'F d, Y H:i:s',

/* timezone used while writing to the owncloud logfile (default: UTC) */
'logtimezone' => 'Europe/Berlin',
);

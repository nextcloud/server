<?php

define("DEBUG", true);

$CONFIG = array(
/* Flag to indicate OwnCloud is successfully installed (true = installed) */
"installed" => false,
/* Type of database, can be sqlite, mysql or pgsql */
"dbtype" => "sqlite",
/* Name of the OwnCloud database */
"dbname" => "owncloud",
/* User to access the OwnCloud database */
"dbuser" => "",
/* Password to access the OwnCloud database */
"dbpassword" => "",
/* Host running the OwnCloud database */
"dbhost" => "",
/* Prefix for the OwnCloud tables in the database */
"dbtableprefix" => "",
/* Force use of HTTPS connection (true = use HTTPS) */
"forcessl" => false,
/* Theme to use for OwnCloud */
"theme" => "",
/* Path to the 3rdparty directory */
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
/* Mode to use for sending mail, can be sendmail, smtp, qmail or php, see PHPMailer docs */
"mail_smtpmode" => "sendmail",
/* Host to use for sending mail, depends on mail_smtpmode if this is used */
"mail_smtphost" => "127.0.0.1",
/* authentication needed to send mail, depends on mail_smtpmode if this is used
 * (false = disable authentication)
 */
"mail_smtpauth" => false,
/* Username to use for sendmail mail, depends on mail_smtpauth if this is used */
"mail_smtpname" => "",
/* Password to use for sendmail mail, depends on mail_smtpauth if this is used */
"mail_smtppassword" => "",
/* Check 3rdparty apps for malicious code fragments */
"appcodechecker" => "",
/* Place to log to, can be owncloud and syslog (owncloud is log menu item in admin menu) */
"log_type" => "owncloud",
/* File for the owncloud logger to log to, (default is ownloud.log in the data dir */
"logfile" => "",
/* Loglevel to start logging at. 0=DEBUG, 1=INFO, 2=WARN, 3=ERROR (default is WARN) */
"loglevel" => "",
"passwordsalt" => "",
"updatechecker" => true,
/* Set this to false to disable the check for writable apps dir.
 * If the apps dir is not writable, you can't download&install extra apps
 * in the admin apps menu.
 */
"writable_appsdir" => true,
/* The directory where the user data is stored, default to data in the owncloud
 * directory. The sqlite database is also stored here, when sqlite is used.
 */
// "datadirectory" => ""
);
?>

<?php


// Owner
$CONFIG_FOOTEROWNERNAME = 'Frank Karlitschek';
$CONFIG_FOOTEROWNEREMAIL = 'karlitschek@kde.org';


// ADMIN ACCOUNT
$CONFIG_ADMINLOGIN = 'frank';
$CONFIG_ADMINPASSWORD = '123';


// DB Config
$CONFIG_DBHOST = 'localhost';
$CONFIG_DBNAME = 'owncloud';
$CONFIG_DBUSER = 'owncloud';
$CONFIG_DBPWD = 'owncloud12345';

// directories
$CONFIG_DATADIRECTORY = '/www/testy';
$CONFIG_DOCUMENTROOT = '/www/owncloud/htdocs';


// force SSL
$CONFIG_HTTPFORCESSL = false;


// other
$CONFIG_DATEFORMAT = 'j M Y G:i';

// plugins
//$CONFIG_LOADPLUGINS = 'music test';
$CONFIG_LOADPLUGINS = '';


// set the right include path
// don´t change unless you know what you are doing
set_include_path(get_include_path().PATH_SEPARATOR.$CONFIG_DOCUMENTROOT.PATH_SEPARATOR.$CONFIG_DOCUMENTROOT.'/inc');

require_once('lib_base.php');

?>

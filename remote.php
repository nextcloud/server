<?php
$RUNTIME_NOSETUPFS = true;
//$RUNTIME_NOAPPS = TRUE;
require_once('lib/base.php');
$file = OCP\CONFIG::getAppValue('core', $_GET['service']);
if(is_null($file)){
	header('HTTP/1.0 404 Not Found');
	exit;
}
$baseuri = OC::$WEBROOT . '/remote.php?service=' . $_GET['service'] . '&amp;p=';
parse_str($_GET['p'], $_GET);
require_once(OC::$APPSROOT . $file);
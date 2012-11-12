<?php

// Check for autosetup:
$autosetup_file = OC::$SERVERROOT."/config/autoconfig.php";
if( file_exists( $autosetup_file )) {
	OC_Log::write('core', 'Autoconfig file found, setting up owncloud...', OC_Log::INFO);
	include $autosetup_file;
	$_POST['install'] = 'true';
	$_POST = array_merge ($_POST, $AUTOCONFIG);
	unlink($autosetup_file);
}

OC_Util::addScript('setup');

$hasSQLite = (is_callable('sqlite_open') or class_exists('SQLite3'));
$hasMySQL = is_callable('mysql_connect');
$hasPostgreSQL = is_callable('pg_connect');
$hasOracle = is_callable('oci_connect');
$datadir = OC_Config::getValue('datadirectory', OC::$SERVERROOT.'/data');

// Test if  .htaccess is working
$content = "deny from all";
file_put_contents(OC::$SERVERROOT.'/data/.htaccess', $content);

$opts = array(
	'hasSQLite' => $hasSQLite,
	'hasMySQL' => $hasMySQL,
	'hasPostgreSQL' => $hasPostgreSQL,
	'hasOracle' => $hasOracle,
	'directory' => $datadir,
	'secureRNG' => OC_Util::secureRNG_available(),
	'htaccessWorking' => OC_Util::ishtaccessworking(),
	'errors' => array(),
);

if(isset($_POST['install']) AND $_POST['install']=='true') {
	// We have to launch the installation process :
	$e = OC_Setup::install($_POST);
	$errors = array('errors' => $e);

	if(count($e) > 0) {
		//OC_Template::printGuestPage("", "error", array("errors" => $errors));
		$options = array_merge($_POST, $opts, $errors);
		OC_Template::printGuestPage("", "installation", $options);
	}
	else {
		header("Location: ".OC::$WEBROOT.'/');
		exit();
	}
}
else {
	OC_Template::printGuestPage("", "installation", $opts);
}

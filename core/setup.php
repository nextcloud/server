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

$hasSQLite = class_exists('SQLite3');
$hasMySQL = is_callable('mysql_connect');
$hasPostgreSQL = is_callable('pg_connect');
$hasOracle = is_callable('oci_connect');
$hasMSSQL = is_callable('sqlsrv_connect');
$datadir = OC_Config::getValue('datadirectory', OC::$SERVERROOT.'/data');
$vulnerableToNullByte = false;
if(@file_exists(__FILE__."\0Nullbyte")) { // Check if the used PHP version is vulnerable to the NULL Byte attack (CVE-2006-7243)
	$vulnerableToNullByte = true;
} 

// Protect data directory here, so we can test if the protection is working
OC_Setup::protectDataDirectory();

$opts = array(
	'hasSQLite' => $hasSQLite,
	'hasMySQL' => $hasMySQL,
	'hasPostgreSQL' => $hasPostgreSQL,
	'hasOracle' => $hasOracle,
	'hasMSSQL' => $hasMSSQL,
	'directory' => $datadir,
	'secureRNG' => OC_Util::secureRNG_available(),
	'htaccessWorking' => OC_Util::ishtaccessworking(),
	'vulnerableToNullByte' => $vulnerableToNullByte,
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
		header( 'Location: '.OC_Helper::linkToRoute( 'post_setup_check' ));
		exit();
	}
}
else {
	OC_Template::printGuestPage("", "installation", $opts);
}

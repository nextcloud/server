<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
//check for addressbooks rights or create new one
ob_start();
 
OCP\JSON::checkLoggedIn();
OCP\App::checkAppEnabled('contacts');
session_write_close();

$nl = "\n";

global $progresskey;
$progresskey = 'contacts.import-' . $_GET['progresskey'];

if (isset($_GET['progress']) && $_GET['progress']) {
	echo OC_Cache::get($progresskey);
	die;
}

function writeProgress($pct) {
	global $progresskey;
	OC_Cache::set($progresskey, $pct, 300);
}
writeProgress('10');
$view = $file = null;
if(isset($_POST['fstype']) && $_POST['fstype'] == 'OC_FilesystemView') {
	$view = OCP\Files::getStorage('contacts');
	$file = $view->file_get_contents('/' . $_POST['file']);
} else {
	$file = OC_Filesystem::file_get_contents($_POST['path'] . '/' . $_POST['file']);
}
if(!$file) {
	OCP\JSON::error(array('message' => 'Import file was empty.'));
	exit();
}
if(isset($_POST['method']) && $_POST['method'] == 'new'){
	$id = OC_Contacts_Addressbook::add(OCP\USER::getUser(), $_POST['addressbookname']);
	if(!$id) {
		OCP\JSON::error(array('message' => 'Error creating address book.'));
		exit();
	}
	OC_Contacts_Addressbook::setActive($id, 1);
}else{
	$id = $_POST['id'];
	if(!$id) {
		OCP\JSON::error(array('message' => 'Error getting the ID of the address book.'));
		exit();
	}
	OC_Contacts_App::getAddressbook($id); // is owner access check
}
//analyse the contacts file
writeProgress('40');
$lines = explode($nl, $file);
$inelement = false;
$parts = array();
$card = array();
foreach($lines as $line){
	if(strtoupper(trim($line)) == 'BEGIN:VCARD'){
		$inelement = true;
	} elseif (strtoupper(trim($line)) == 'END:VCARD') {
		$card[] = $line;
		$parts[] = implode($nl, $card);
		$card = array();
		$inelement = false;
	}
	if ($inelement === true && trim($line) != '') {
		$card[] = $line;
	}
}
//import the contacts
writeProgress('70');
$imported = 0;
$failed = 0;
if(!count($parts) > 0) {
	OCP\JSON::error(array('message' => 'No contacts to import in .'.$_POST['file'].' Please check if the file is corrupted.'));
	exit();
}
foreach($parts as $part){
	$card = OC_VObject::parse($part);
	if (!$card) {
		$failed += 1;
		OCP\Util::writeLog('contacts','Import: skipping card. Error parsing VCard: '.$part, OCP\Util::ERROR);
		continue; // Ditch cards that can't be parsed by Sabre.
	}
	try {
		OC_Contacts_VCard::add($id, $card);
		$imported += 1;
	} catch (Exception $e) {
		OCP\Util::writeLog('contacts', 'Error importing vcard: '.$e->getMessage().$nl.$card, OCP\Util::ERROR);
		$failed += 1;
	}
}
//done the import
writeProgress('100');
sleep(3);
OC_Cache::remove($progresskey);
if(isset($_POST['fstype']) && $_POST['fstype'] == 'OC_FilesystemView') {
	if(!$view->unlink('/' . $_POST['file'])) {
		OCP\Util::writeLog('contacts','Import: Error unlinking OC_FilesystemView ' . '/' . $_POST['file'], OCP\Util::ERROR);
	}
}
OCP\JSON::success(array('data' => array('imported'=>$imported, 'failed'=>$failed)));

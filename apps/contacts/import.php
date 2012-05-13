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
$nl = "\n";
$progressfile = 'import_tmp/' . md5(session_id()) . '.txt';
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '10');
	fclose($progressfopen);
}
$view = $file = null;
if(isset($_POST['fstype']) && $_POST['fstype'] == 'OC_FilesystemView') {
	$view = OCP\App::getStorage('contacts');
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
	OC_Contacts_Addressbook::setActive($id, 1);
}else{
	$id = $_POST['id'];
	OC_Contacts_App::getAddressbook($id); // is owner access check
}
//analyse the contacts file
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '20');
	fclose($progressfopen);
}
$searchfor = array('VCARD');
$parts = $searchfor;
$filearr = explode($nl, $file);
$inelement = false;
$parts = array();
$i = 0;
foreach($filearr as $line){
	foreach($searchfor as $search){
		if(substr_count($line, $search) == 1){
			list($attr, $val) = explode(':', $line);
			if($attr == 'BEGIN'){
				$parts[]['begin'] = $i;
				$inelement = true;
			}
			if($attr == 'END'){
				$parts[count($parts) - 1]['end'] = $i;
				$inelement = false;
			}
		}
	}
	$i++;
}
//import the contacts
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '40');
	fclose($progressfopen);
}
$start = '';
for ($i = 0; $i < $parts[0]['begin']; $i++) { 
	if($i == 0){
		$start = $filearr[0];
	}else{
		$start .= $nl . $filearr[$i];
	}
}
$end = '';
for($i = $parts[count($parts) - 1]['end'] + 1;$i <= count($filearr) - 1; $i++){
	if($i == $parts[count($parts) - 1]['end'] + 1){
		$end = $filearr[$parts[count($parts) - 1]['end'] + 1];
	}else{
		$end .= $nl . $filearr[$i];
	}
}
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '50');
	fclose($progressfopen);
}
$importready = array();
foreach($parts as $part){
	for($i = $part['begin']; $i <= $part['end'];$i++){
		if($i == $part['begin']){
			$content = $filearr[$i];
		}else{
			$content .= $nl . $filearr[$i];
		}
	}
	$importready[] = $start . $nl . $content . $nl . $end;
}
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '70');
	fclose($progressfopen);
}
if(count($parts) == 1){
	$importready = array($file);
}
$imported = 0;
$failed = 0;
foreach($importready as $import){
	$card = OC_VObject::parse($import);
	if (!$card) {
		$failed += 1;
		OCP\Util::writeLog('contacts','Import: skipping card. Error parsing VCard: '.$import, OCP\Util::ERROR);
		continue; // Ditch cards that can't be parsed by Sabre.
	}
	$imported += 1;
	OC_Contacts_VCard::add($id, $card);
}
//done the import
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '100');
	fclose($progressfopen);
}
sleep(3);
if(is_writable('import_tmp/')){
	unlink($progressfile);
}
if(isset($_POST['fstype']) && $_POST['fstype'] == 'OC_FilesystemView') {
	if(!$view->unlink('/' . $_POST['file'])) {
		OCP\Util::writeLog('contacts','Import: Error unlinking OC_FilesystemView ' . '/' . $_POST['file'], OCP\Util::ERROR);
	}
}
OCP\JSON::success(array('data' => array('imported'=>$imported, 'failed'=>$failed)));

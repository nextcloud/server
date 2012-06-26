<?php
/**
* @author  Victor Dubiniuk
* Copyright (c) 2012 Victor Dubiniuk <victor.dubiniuk@gmail.com>
* Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
* This file is licensed under the Affero General Public License version 3 or
* later.
* See the COPYING-README file.
*/
	
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
OCP\JSON::callCheck();

$ids = $_POST['ids'];
$aid = intval($_POST['aid']);
OC_Contacts_App::getAddressbook($aid);
	
if(!is_array($ids)) {
	$ids = array($ids,);
}
$goodids = array();
foreach ($ids as $id){
	try {
		$card = OC_Contacts_App::getContactObject( intval($id) );
		if($card) {
			$goodids[] = $id;
		}
	} catch (Exception $e) {
		OCP\Util::writeLog('contacts', 'Error moving contact "'.$id.'" to addressbook "'.$aid.'"'.$e->getMessage(), OCP\Util::ERROR);
	}
}
try {
	OC_Contacts_VCard::moveToAddressBook($aid, $ids);
}  catch (Exception $e) {
	$msg = $e->getMessage();
	OCP\Util::writeLog('contacts', 'Error moving contacts "'.implode(',', $ids).'" to addressbook "'.$aid.'"'.$msg, OCP\Util::ERROR);
	OC_JSON::error(array('data' => array('message' => $msg,)));
}
	
OC_JSON::success(array('data' => array('ids' => $goodids,)));
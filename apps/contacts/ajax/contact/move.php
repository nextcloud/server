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

$id = intval($_POST['id']);
$aid = intval($_POST['aid']);
$isaddressbook = isset($_POST['isaddressbook']) ? true: false;

// Ownership checking
OC_Contacts_App::getAddressbook($aid);
try {
	OC_Contacts_VCard::moveToAddressBook($aid, $id, $isaddressbook);
}  catch (Exception $e) {
	$msg = $e->getMessage();
	OCP\Util::writeLog('contacts', 'Error moving contacts "'.implode(',', $id).'" to addressbook "'.$aid.'"'.$msg, OCP\Util::ERROR);
	OC_JSON::error(array('data' => array('message' => $msg,)));
}

OC_JSON::success(array('data' => array('ids' => $id,)));
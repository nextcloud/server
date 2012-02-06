<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');
function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('contacts','ajax/editname.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('contacts','ajax/editname.php: '.$msg, OC_Log::DEBUG);
}

$tmpl = new OC_TEMPLATE("contacts", "part.edit_name_dialog");

$id = isset($_GET['id'])?$_GET['id']:'';
if($id) {
	$vcard = OC_Contacts_App::getContactVCard($id);
	$name = array('', '', '', '', '');
	if($vcard->__isset('N')) {
		$property = $vcard->__get('N');
		if($property) {
			$name = OC_Contacts_VCard::structureProperty($property);
		}
	}
	$tmpl->assign('name',$name);
	$tmpl->assign('id',$id);
} else {
	$addressbooks = OC_Contacts_Addressbook::active(OC_User::getUser());
	$tmpl->assign('addressbooks', $addressbooks);
}
$tmpl->printpage();

?>

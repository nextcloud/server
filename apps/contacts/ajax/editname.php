<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/editname.php: '.$msg, OCP\Util::DEBUG);
	exit();
}
function debug($msg) {
	OCP\Util::writeLog('contacts','ajax/editname.php: '.$msg, OCP\Util::DEBUG);
}

$tmpl = new OCP\Template("contacts", "part.edit_name_dialog");

$id = isset($_GET['id'])?$_GET['id']:'';
debug('id: '.$id);
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
	bailOut(OC_Contacts_App::$l10n->t('Contact ID is missing.'));
	//$addressbooks = OC_Contacts_Addressbook::active(OCP\USER::getUser());
	//$tmpl->assign('addressbooks', $addressbooks);
}
$tmpl->printpage();

?>

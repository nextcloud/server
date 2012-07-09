<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
require_once('loghandler.php');

$tmpl = new OCP\Template("contacts", "part.edit_name_dialog");

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
	$name = array_map('htmlspecialchars', $name['value']);
	$tmpl->assign('name',$name, false);
	$tmpl->assign('id',$id, false);
} else {
	bailOut(OC_Contacts_App::$l10n->t('Contact ID is missing.'));
}
$page = $tmpl->fetchPage();
OCP\JSON::success(array('data' => array('page'=>$page)));

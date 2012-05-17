<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');

$id = $_GET['id'];
$checksum = isset($_GET['checksum'])?$_GET['checksum']:'';
$vcard = OC_Contacts_App::getContactVCard($id);
$adr_types = OC_Contacts_App::getTypesOfProperty('ADR');

$tmpl = new OCP\Template("contacts", "part.edit_address_dialog");
if($checksum) {
	$line = OC_Contacts_App::getPropertyLineByChecksum($vcard, $checksum);
	$element = $vcard->children[$line];
	$adr = OC_Contacts_VCard::structureProperty($element);
	$tmpl->assign('adr',$adr);
}

$tmpl->assign('id',$id);
$tmpl->assign('adr_types',$adr_types);

$tmpl->printpage();

?>

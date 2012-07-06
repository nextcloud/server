<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');

require_once(__DIR__.'../loghandler.php');

$addressbooks = OC_Contacts_Addressbook::all(OCP\USER::getUser());
if(count($addressbooks) == 0) {
	bailOut(OC_Contacts_App::$l10n->t('No address books found.'));
}
$addressbookids = array();
foreach($addressbooks as $addressbook) {
	$addressbookids[] = $addressbook['id'];
} 
$contacts = OC_Contacts_VCard::all($addressbookids);
if(count($contacts) == 0) {
	bailOut(OC_Contacts_App::$l10n->t('No contacts found.'));
}

OC_Contacts_App::scanCategories($contacts);
$categories = OC_Contacts_App::getCategories();

OCP\JSON::success(array('data' => array('categories'=>$categories)));

?>

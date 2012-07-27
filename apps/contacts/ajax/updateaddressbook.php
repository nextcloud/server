<?php
/**
 * Copyright (c) 2011-2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
require_once 'loghandler.php';

$bookid = $_POST['id'];
OC_Contacts_App::getAddressbook($bookid); // is owner access check

$name = trim(strip_tags($_POST['name']));
if(!$name) {
	bailOut(OC_Contacts_App::$l10n->t('Cannot update addressbook with an empty name.'));
}

if(!OC_Contacts_Addressbook::edit($bookid, $name, null)) {
	bailOut(OC_Contacts_App::$l10n->t('Error updating addressbook.'));
}

if(!OC_Contacts_Addressbook::setActive($bookid, $_POST['active'])) {
	bailOut(OC_Contacts_App::$l10n->t('Error (de)activating addressbook.'));
}

$addressbook = OC_Contacts_App::getAddressbook($bookid);
$tmpl = new OCP\Template('contacts', 'part.chooseaddressbook.rowfields');
$tmpl->assign('addressbook', $addressbook);
OCP\JSON::success(array(
	'page' => $tmpl->fetchPage(),
	'addressbook' => $addressbook,
));

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
require_once  __DIR__.'/../loghandler.php';

$id = $_POST['id'];
$name = trim(strip_tags($_POST['name']));
$description = trim(strip_tags($_POST['description']));
if(!$id) {
	bailOut(OC_Contacts_App::$l10n->t('id is not set.'));
}

if(!$name) {
	bailOut(OC_Contacts_App::$l10n->t('Cannot update addressbook with an empty name.'));
}

if(!OC_Contacts_Addressbook::edit($id, $name, $description)) {
	bailOut(OC_Contacts_App::$l10n->t('Error updating addressbook.'));
}

if(!OC_Contacts_Addressbook::setActive($id, $_POST['active'])) {
	bailOut(OC_Contacts_App::$l10n->t('Error (de)activating addressbook.'));
}

OC_Contacts_App::getAddressbook($id); // is owner access check
$addressbook = OC_Contacts_App::getAddressbook($id);
OCP\JSON::success(array(
	'addressbook' => $addressbook,
));

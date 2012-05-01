<?php
/**
 * Copyright (c) 2011-2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

$bookid = $_POST['id'];
OC_Contacts_App::getAddressbook($bookid); // is owner access check

$name = trim(strip_tags($_POST['name']));
if(!$name) {
	OC_JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Cannot update addressbook with an empty name.'))));
	OCP\Util::writeLog('contacts','ajax/updateaddressbook.php: Cannot update addressbook with an empty name: '.strip_tags($_POST['name']), OCP\Util::ERROR);
	exit();
}

if(!OC_Contacts_Addressbook::edit($bookid, $name, null)) {
	OC_JSON::error(array('data' => array('message' => $l->t('Error updating addressbook.'))));
	OCP\Util::writeLog('contacts','ajax/updateaddressbook.php: Error adding addressbook: ', OCP\Util::ERROR);
	//exit();
}

if(!OC_Contacts_Addressbook::setActive($bookid, $_POST['active'])) {
	OC_JSON::error(array('data' => array('message' => $l->t('Error (de)activating addressbook.'))));
	OCP\Util::writeLog('contacts','ajax/updateaddressbook.php: Error (de)activating addressbook: '.$bookid, OCP\Util::ERROR);
	//exit();
}

$addressbook = OC_Contacts_App::getAddressbook($bookid);
$tmpl = new OC_Template('contacts', 'part.chooseaddressbook.rowfields');
$tmpl->assign('addressbook', $addressbook);
OC_JSON::success(array(
	'page' => $tmpl->fetchPage(),
	'addressbook' => $addressbook,
));

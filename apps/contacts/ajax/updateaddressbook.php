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
OCP\JSON::callCheck();

$bookid = $_POST['id'];
OC_Contacts_App::getAddressbook($bookid); // is owner access check

$name = trim(strip_tags($_POST['name']));
if(!$name) {
	OCP\JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Cannot update addressbook with an empty name.'))));
	OCP\Util::writeLog('contacts','ajax/updateaddressbook.php: Cannot update addressbook with an empty name: '.strip_tags($_POST['name']), OCP\Util::ERROR);
	exit();
}

if(!OC_Contacts_Addressbook::edit($bookid, $name, null)) {
	OCP\JSON::error(array('data' => array('message' => $l->t('Error updating addressbook.'))));
	OCP\Util::writeLog('contacts','ajax/updateaddressbook.php: Error adding addressbook: ', OCP\Util::ERROR);
	//exit();
}

if(!OC_Contacts_Addressbook::setActive($bookid, $_POST['active'])) {
	OCP\JSON::error(array('data' => array('message' => $l->t('Error (de)activating addressbook.'))));
	OCP\Util::writeLog('contacts','ajax/updateaddressbook.php: Error (de)activating addressbook: '.$bookid, OCP\Util::ERROR);
	//exit();
}

$addressbook = OC_Contacts_App::getAddressbook($bookid);
$tmpl = new OCP\Template('contacts', 'part.chooseaddressbook.rowfields');
$tmpl->assign('addressbook', $addressbook);
OCP\JSON::success(array(
	'page' => $tmpl->fetchPage(),
	'addressbook' => $addressbook,
));

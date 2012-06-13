<?php
/**
 * Copyright (c) 2011-2012 Thomas Tanghus <thomas@tanghus.net>
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
OCP\JSON::callCheck();

$userid = OCP\USER::getUser();
$name = trim(strip_tags($_POST['name']));
if(!$name) {
	OCP\JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Cannot add addressbook with an empty name.'))));
	OCP\Util::writeLog('contacts','ajax/createaddressbook.php: Cannot add addressbook with an empty name: '.strip_tags($_POST['name']), OCP\Util::ERROR);
	exit();
}
$bookid = OC_Contacts_Addressbook::add($userid, $name, null);
if(!$bookid) {
	OCP\JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Error adding addressbook.'))));
	OCP\Util::writeLog('contacts','ajax/createaddressbook.php: Error adding addressbook: '.$_POST['name'], OCP\Util::ERROR);
	exit();
}

if(!OC_Contacts_Addressbook::setActive($bookid, 1)) {
	OCP\JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Error activating addressbook.'))));
	OCP\Util::writeLog('contacts','ajax/createaddressbook.php: Error activating addressbook: '.$bookid, OCP\Util::ERROR);
	//exit();
}
$addressbook = OC_Contacts_App::getAddressbook($bookid);
$tmpl = new OCP\Template('contacts', 'part.chooseaddressbook.rowfields');
$tmpl->assign('addressbook', $addressbook);
OCP\JSON::success(array(
	'page' => $tmpl->fetchPage(),
	'addressbook' => $addressbook,
));

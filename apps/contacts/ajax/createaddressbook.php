<?php
/**
 * Copyright (c) 2011-2012 Thomas Tanghus <thomas@tanghus.net>
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

$userid = OCP\USER::getUser();
$name = trim(strip_tags($_POST['name']));
if(!$name) {
	OC_JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Cannot add addressbook with an empty name.'))));
	OCP\Util::writeLog('contacts','ajax/createaddressbook.php: Cannot add addressbook with an empty name: '.strip_tags($_POST['name']), OCP\Util::ERROR);
	exit();
}
$bookid = OC_Contacts_Addressbook::add($userid, $name, null);
if(!$bookid) {
	OC_JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Error adding addressbook.'))));
	OCP\Util::writeLog('contacts','ajax/createaddressbook.php: Error adding addressbook: '.$_POST['name'], OCP\Util::ERROR);
	exit();
}

if(!OC_Contacts_Addressbook::setActive($bookid, 1)) {
	OC_JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Error activating addressbook.'))));
	OCP\Util::writeLog('contacts','ajax/createaddressbook.php: Error activating addressbook: '.$bookid, OCP\Util::ERROR);
	//exit();
}
$addressbook = OC_Contacts_App::getAddressbook($bookid);
$tmpl = new OC_Template('contacts', 'part.chooseaddressbook.rowfields');
$tmpl->assign('addressbook', $addressbook);
OC_JSON::success(array(
	'page' => $tmpl->fetchPage(),
	'addressbook' => $addressbook,
));

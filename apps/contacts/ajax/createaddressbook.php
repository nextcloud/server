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
require_once 'loghandler.php';

$userid = OCP\USER::getUser();
$name = trim(strip_tags($_POST['name']));
if(!$name) {
	bailOut('Cannot add addressbook with an empty name.');
}
$bookid = OC_Contacts_Addressbook::add($userid, $name, null);
if(!$bookid) {
	bailOut('Error adding addressbook: '.$name);
}

if(!OC_Contacts_Addressbook::setActive($bookid, 1)) {
	bailOut('Error activating addressbook.');
}
$addressbook = OC_Contacts_App::getAddressbook($bookid);
$tmpl = new OCP\Template('contacts', 'part.chooseaddressbook.rowfields');
$tmpl->assign('addressbook', $addressbook);
OCP\JSON::success(array(
	'page' => $tmpl->fetchPage(),
	'addressbook' => $addressbook,
));

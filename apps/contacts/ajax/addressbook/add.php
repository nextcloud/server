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
require_once  __DIR__.'/../loghandler.php';

debug('name: '.$_POST['name']);

$userid = OCP\USER::getUser();
$name = isset($_POST['name'])?trim(strip_tags($_POST['name'])):null;
$description = isset($_POST['description'])
	? trim(strip_tags($_POST['description']))
	: null;

if(is_null($name)) {
	bailOut('Cannot add addressbook with an empty name.');
}
$bookid = OC_Contacts_Addressbook::add($userid, $name, $description);
if(!$bookid) {
	bailOut('Error adding addressbook: '.$name);
}

if(!OC_Contacts_Addressbook::setActive($bookid, 1)) {
	bailOut('Error activating addressbook.');
}
$addressbook = OC_Contacts_App::getAddressbook($bookid);
OCP\JSON::success(array('data' => array('addressbook' => $addressbook)));

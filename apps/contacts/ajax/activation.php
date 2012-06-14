<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
OCP\JSON::callCheck();

$bookid = $_POST['bookid'];
$book = OC_Contacts_App::getAddressbook($bookid);// is owner access check

if(!OC_Contacts_Addressbook::setActive($bookid, $_POST['active'])) {
	OCP\Util::writeLog('contacts','ajax/activation.php: Error activating addressbook: '.$bookid, OCP\Util::ERROR);
	OCP\JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Error (de)activating addressbook.'))));
	exit();
}

OCP\JSON::success(array(
	'active' => OC_Contacts_Addressbook::isActive($bookid),
	'bookid' => $bookid,
	'book'   => $book,
));

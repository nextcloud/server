<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ("../../../lib/base.php");
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

$bookid = $_POST['bookid'];
$book = OC_Contacts_App::getAddressbook($bookid);// is owner access check

if(!OC_Contacts_Addressbook::setActive($bookid, $_POST['active'])) {
	OC_Log::write('contacts','ajax/activation.php: Error activating addressbook: '.$bookid, OC_Log::ERROR);
	OC_JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Error (de)activating addressbook.'))));
	exit();
}

OC_JSON::success(array(
	'active' => OC_Contacts_Addressbook::isActive($bookid),
	'bookid' => $bookid,
	'book'   => $book,
));

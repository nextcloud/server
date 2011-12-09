<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('../../../lib/base.php');

$l10n = new OC_L10N('contacts');

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

$userid = OC_User::getUser();
$bookid = OC_Contacts_Addressbook::add($userid, $_POST['name'], null);
OC_Contacts_Addressbook::setActive($bookid, 1);
$addressbook = OC_Contacts_Addressbook::find($bookid);
$tmpl = new OC_Template('contacts', 'part.chooseaddressbook.rowfields');
$tmpl->assign('addressbook', $addressbook);
OC_JSON::success(array(
	'page' => $tmpl->fetchPage(),
	'addressbook' => $addressbook,
));

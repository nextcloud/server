<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ("../../../lib/base.php");
if(!OC_USER::isLoggedIn()) {
	die("<script type=\"text/javascript\">document.location = oc_webroot;</script>");
}
OC_JSON::checkAppEnabled('contacts');
$bookid = $_POST['bookid'];
OC_Contacts_Addressbook::setActive($bookid, $_POST['active']);
$book = OC_Contacts_Addressbook::find($bookid);

/* is there an OC_JSON::error() ? */
OC_JSON::success(array(
	'active' => $book['active'],
	'bookid' => $bookid,
));

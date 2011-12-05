<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');
$l10n = new OC_L10N('contacts');
if(!OC_USER::isLoggedIn()) {
	die("<script type=\"text/javascript\">document.location = oc_webroot;</script>");
}
OC_JSON::checkAppEnabled('contacts');
$addressbook = OC_Contacts_Addressbook::find($_GET['bookid']);
$tmpl = new OC_Template("contacts", "part.editaddressbook");
$tmpl->assign('new', false);
$tmpl->assign('addressbook', $addressbook);
$tmpl->printPage();
?>

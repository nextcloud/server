<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');
$book = array(
	'id' => 'new',
	'displayname' => '',
);
$tmpl = new OC_Template('contacts', 'part.editaddressbook');
$tmpl->assign('new', true);
$tmpl->assign('addressbook', $book);
$tmpl->printPage();
?>

<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
$book = array(
	'id' => 'new',
	'displayname' => '',
);
$tmpl = new OCP\Template('contacts', 'part.editaddressbook');
$tmpl->assign('new', true);
$tmpl->assign('addressbook', $book);
$tmpl->printPage();
?>

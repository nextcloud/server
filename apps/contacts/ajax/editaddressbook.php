<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
$addressbook = OC_Contacts_App::getAddressbook($_GET['bookid']);
$tmpl = new OCP\Template("contacts", "part.editaddressbook");
$tmpl->assign('new', false);
$tmpl->assign('addressbook', $addressbook);
$tmpl->printPage();
?>

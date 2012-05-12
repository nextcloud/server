<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');

$ids = OC_Contacts_Addressbook::activeIds(OCP\USER::getUser());
$contacts = OC_Contacts_VCard::all($ids);
$tmpl = new OCP\Template("contacts", "part.contacts");
$tmpl->assign('contacts', $contacts);
$page = $tmpl->fetchPage();

OCP\JSON::success(array('data' => array( 'page' => $page )));
?>

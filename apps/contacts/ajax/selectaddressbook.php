<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');

$addressbooks = OC_Contacts_Addressbook::all(OCP\USER::getUser());
$tmpl = new OCP\Template("contacts", "part.selectaddressbook");
$tmpl->assign('addressbooks', $addressbooks);
$page = $tmpl->fetchPage();
OCP\JSON::success(array('data' => array('page' => $page )));

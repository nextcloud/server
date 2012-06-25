<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');

$books = OC_Contacts_Addressbook::all(OCP\USER::getUser());
if(count($books) > 1) {
	$addressbooks = array();
	foreach($books as $book) {
		$addressbooks[] = array('id' => $book['id'], 'name' => $book['displayname']);
	}
	$tmpl = new OCP\Template("contacts", "part.selectaddressbook");
	$tmpl->assign('addressbooks', $addressbooks);
	$page = $tmpl->fetchPage();
	OCP\JSON::success(array('data' => array( 'type' => 'dialog', 'page' => $page )));
} else {
	OCP\JSON::success(array('data' => array( 'type' => 'result', 'id' => $books[0]['id'] )));
}
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
$contacts_alphabet = OC_Contacts_VCard::all($ids);
$active_addressbooks = OC_Contacts_Addressbook::active(OCP\USER::getUser());

// Our new array for the contacts sorted by addressbook
$contacts_addressbook = array();
foreach($contacts_alphabet as $contact):
	if(is_null($contacts_addressbook[$contact['addressbookid']])) {
		$contacts_addressbook[$contact['addressbookid']] = array();
	}
	$contacts_addressbook[$contact['addressbookid']][] = $contact;
endforeach;

// FIXME: this is kind of ugly - just to replace the keys of the array
// perhaps we could do some magic combine_array() instead...
foreach($contacts_addressbook as $addressbook_id => $contacts):
	foreach($active_addressbooks as $addressbook):
		if($addressbook_id == $addressbook['id']) {
			unset($contacts_addressbook[$addressbook_id]);
			$contacts_addressbook[$addressbook['displayname']] = $contacts;
		}
	endforeach;
endforeach;
// This one should be ok for a small amount of Addressbooks
ksort($contacts_addressbook);

$tmpl = new OCP\Template("contacts", "part.contacts");
$tmpl->assign('contacts', $contacts_addressbook, false);
$page = $tmpl->fetchPage();

OCP\JSON::success(array('data' => array( 'page' => $page )));
?>

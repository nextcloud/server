<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

function cmp($a, $b)
{
    if ($a['displayname'] == $b['displayname']) {
        return 0;
    }
    return ($a['displayname'] < $b['displayname']) ? -1 : 1;
}
 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');

$ids = OC_Contacts_Addressbook::activeIds(OCP\USER::getUser());
$contacts_alphabet = OC_Contacts_VCard::all($ids);
$active_addressbooks = OC_Contacts_Addressbook::active(OCP\USER::getUser());

// Our new array for the contacts sorted by addressbook
$contacts_addressbook = array();
foreach($contacts_alphabet as $contact) {
	if(!isset($contacts_addressbook[$contact['addressbookid']])) {
		$contacts_addressbook[$contact['addressbookid']] = array('contacts' => array());
	}
	$display = trim($contact['fullname']);
	if(!$display) {
		$vcard = OC_Contacts_App::getContactVCard($contact['id']);
		if(!is_null($vcard)) {
			$struct = OC_Contacts_VCard::structureContact($vcard);
			$display = isset($struct['EMAIL'][0])?$struct['EMAIL'][0]['value']:'[UNKNOWN]';
		}
	}
	$contacts_addressbook[$contact['addressbookid']]['contacts'][] = array('id' => $contact['id'], 'addressbookid' => $contact['addressbookid'], 'displayname' => htmlspecialchars($display));
}

foreach($contacts_addressbook as $addressbook_id => $contacts) {
	foreach($active_addressbooks as $addressbook) {
		if($addressbook_id == $addressbook['id']) {
			$contacts_addressbook[$addressbook_id]['displayname'] = $addressbook['displayname'];
		}
	}
}

usort($contacts_addressbook, 'cmp');

$tmpl = new OCP\Template("contacts", "part.contacts");
$tmpl->assign('books', $contacts_addressbook, false);
$page = $tmpl->fetchPage();

OCP\JSON::success(array('data' => array( 'page' => $page )));


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

$start = isset($_GET['startat'])?$_GET['startat']:0;
$aid = isset($_GET['aid'])?$_GET['aid']:null;

if(is_null($aid)) {
	// Called initially to get the active addressbooks.
	$active_addressbooks = OC_Contacts_Addressbook::active(OCP\USER::getUser());
} else {
	// called each time more contacts has to be shown.
	$active_addressbooks = array(OC_Contacts_Addressbook::find($aid));
}


session_write_close();

// create the addressbook associate array
$contacts_addressbook = array();
$ids = array();
foreach($active_addressbooks as $addressbook) {
	$ids[] = $addressbook['id'];
	if(!isset($contacts_addressbook[$addressbook['id']])) {
		$contacts_addressbook[$addressbook['id']]
				= array('contacts' => array('type' => 'book',));
		$contacts_addressbook[$addressbook['id']]['displayname']
				= $addressbook['displayname'];
	}
}

$contacts_alphabet = array();

// get next 50 for each addressbook.
foreach($ids as $id) {
	if($id) {
		$contacts_alphabet = array_merge(
				$contacts_alphabet,
				OC_Contacts_VCard::all($id, $start, 50)
		);
	}
}
// Our new array for the contacts sorted by addressbook
if($contacts_alphabet) {
	foreach($contacts_alphabet as $contact) {
		// This should never execute.
		if(!isset($contacts_addressbook[$contact['addressbookid']])) {
			$contacts_addressbook[$contact['addressbookid']] = array(
				'contacts' => array('type' => 'book',)
			);
		}
		$display = trim($contact['fullname']);
		if(!$display) {
			$vcard = OC_Contacts_App::getContactVCard($contact['id']);
			if(!is_null($vcard)) {
				$struct = OC_Contacts_VCard::structureContact($vcard);
				$display = isset($struct['EMAIL'][0])
					? $struct['EMAIL'][0]['value']
					: '[UNKNOWN]';
			}
		}
		$contacts_addressbook[$contact['addressbookid']]['contacts'][] = array(
					'type' => 'contact',
					'id' => $contact['id'],
					'addressbookid' => $contact['addressbookid'],
					'displayname' => htmlspecialchars($display)
		);
	}
}
unset($contacts_alphabet);
uasort($contacts_addressbook, 'cmp');

OCP\JSON::success(array('data' => array('entries' => $contacts_addressbook)));


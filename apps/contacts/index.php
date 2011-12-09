<?php
/**
 * ownCloud - Addressbook
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack mail@jakobsack.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

function contacts_namesort($a,$b){
	return strcmp($a['fullname'],$b['fullname']);
}

// Init owncloud
require_once('../../lib/base.php');

// Check if we are a user
OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('contacts');

// Check if the user has an addressbook
$openaddressbooks = OC_Contacts_Addressbook::activeAddressbookIds(OC_User::getUser());
OC_Log::write('contacts','Got IDs'.implode(',', $openaddressbooks),OC_Log::DEBUG);
// if( count($addressbooks) == 0){
// 	OC_Contacts_Addressbook::add(OC_User::getUser(),'default','Default Address Book');
// 	$addressbooks = OC_Contacts_Addressbook::all(OC_User::getUser());
// }
// $prefbooks = OC_Preferences::getValue(OC_User::getUser(),'contacts','openaddressbooks',null);
// if(is_null($prefbooks)){
// 	$prefbooks = $addressbooks[0]['id'];
// 	OC_Preferences::setValue(OC_User::getUser(),'contacts','openaddressbooks',$prefbooks);
// }

// Load the files we need
OC_App::setActiveNavigationEntry( 'contacts_index' );

// Load a specific user?
$id = isset( $_GET['id'] ) ? $_GET['id'] : null;

// sort addressbooks  (use contactsort)
usort($addressbooks,'contacts_namesort');
// Addressbooks to load
//$openaddressbooks = explode(';',$prefbooks);

$contacts = array();
foreach( $openaddressbooks as $addressbook ){
	$addressbookcontacts = OC_Contacts_VCard::all($addressbook);
	OC_Log::write('contacts','index.php. Getting contacts for: '.$addressbook,OC_Log::DEBUG);
	foreach( $addressbookcontacts as $contact ){
		if(is_null($contact['fullname'])){
			continue;
		}
		$contacts[] = $contact;
		//$contacts[] = array( 'name' => $contact['fullname'], 'id' => $contact['id'] );
	}
}

usort($contacts,'contacts_namesort');

$details = array();
if( !is_null($id)/* || count($contacts)*/){
	if(is_null($id)) $id = $contacts[0]['id'];
	$vcard = OC_Contacts_App::getContactVCard($id);
	$details = OC_Contacts_VCard::structureContact($vcard);
}

<<<<<<< HEAD
// Include Style and Script
OC_Util::addScript('contacts','interface');
OC_Util::addStyle('contacts','styles');
OC_Util::addStyle('contacts','formtastic');
OC_Util::addScript('', 'jquery.multiselect');
OC_Util::addStyle('', 'jquery.multiselect');

$l10n = new OC_L10N('contacts');
$adr_types = OC_Contacts_VCard::getTypesOfProperty($l10n, 'ADR');
$phone_types = OC_Contacts_VCard::getTypesOfProperty($l10n, 'TEL');
=======
$adr_types = OC_Contacts_App::getTypesOfProperty('ADR');
$phone_types = OC_Contacts_App::getTypesOfProperty('TEL');
>>>>>>> eeaf539a4414e3081b6f6652167363a3221a1973

// Process the template
$tmpl = new OC_Template( 'contacts', 'index', 'user' );
$tmpl->assign('adr_types',$adr_types);
$tmpl->assign('phone_types',$phone_types);
$tmpl->assign('addressbooks', $addressbooks);
$tmpl->assign('contacts', $contacts);
$tmpl->assign('details', $details );
$tmpl->assign('id',$id);
$tmpl->printPage();

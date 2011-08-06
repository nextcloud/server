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

function contactsort($a,$b){
	return strcmp($a['name'],$b['name']);
}

// Init owncloud
require_once('../../lib/base.php');

// Check if we are a user
if( !OC_User::isLoggedIn()){
	header( 'Location: '.OC_Helper::linkTo( '', 'index.php' ));
	exit();
}

// Load the files we need
OC_App::setActiveNavigationEntry( 'contacts_index' );

// Load a specific user?
$id = isset( $_GET['id'] ) ? $_GET['id'] : null;

// Addressbooks to load
$openaddressbooks = explode(';',OC_Preferences::getValue(OC_User::getUser(),'contacts','openaddressbooks',null));

$contacts = array();
foreach( $openaddressbooks as $addressbook ){
	$addressbookcontacts = OC_Contacts_Addressbook::allCards($addressbook);
	foreach( $addressbookcontacts as $contact ){
		$contacts[] = array( 'name' => $contact['fullname'], 'id' => $contact['id'] );
	}
}


usort($contacts,'contactsort');
$details = array();

if( !is_null($id) || count($contacts)){
	$contact = OC_Contacts_Addressbook::findCard(is_null($id)?$contacts[0]['id']:$id);
	$vcard = Sabre_VObject_Reader::read($contact['carddata']);
	$details = OC_Contacts_Addressbook::structureContact($vcard);
}

// Process the template
$tmpl = new OC_Template( 'contacts', 'index', 'user' );
$tmpl->assign('contacts', $contacts);
$tmpl->assign('details', $details );
$tmpl->assign('id',$id);
$tmpl->printPage();

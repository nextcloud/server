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

// Get active address books. This creates a default one if none exists.
$ids = OC_Contacts_Addressbook::activeIds(OC_User::getUser());
$contacts = OC_Contacts_VCard::all($ids);

$addressbooks = OC_Contacts_Addressbook::active(OC_User::getUser());

// Load the files we need
OC_App::setActiveNavigationEntry( 'contacts_index' );

// Load a specific user?
$id = isset( $_GET['id'] ) ? $_GET['id'] : null;
$details = array();

if(is_null($id) && count($contacts) > 0) {
	$id = $contacts[0]['id'];
}
$vcard = null;
$details = null;
if(!is_null($id)) {
	$vcard = OC_Contacts_App::getContactVCard($id);
	if(!is_null($vcard)) {
		$details = OC_Contacts_VCard::structureContact($vcard);
	}
}

// Include Style and Script
OC_Util::addScript('contacts','interface');
OC_Util::addScript('contacts','jquery.inview');
OC_Util::addScript('', 'jquery.multiselect');
OC_Util::addStyle('contacts','styles');
//OC_Util::addStyle('contacts','formtastic');

$property_types = OC_Contacts_App::getAddPropertyOptions();
$adr_types = OC_Contacts_App::getTypesOfProperty('ADR');
$phone_types = OC_Contacts_App::getTypesOfProperty('TEL');

// Process the template
$tmpl = new OC_Template( 'contacts', 'index', 'user' );
$tmpl->assign('property_types',$property_types);
$tmpl->assign('adr_types',$adr_types);
$tmpl->assign('phone_types',$phone_types);
$tmpl->assign('addressbooks', $addressbooks);
$tmpl->assign('contacts', $contacts);
$tmpl->assign('details', $details );
$tmpl->assign('id',$id);
$tmpl->printPage();

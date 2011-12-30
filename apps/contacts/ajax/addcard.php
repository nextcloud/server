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

// Init owncloud
require_once('../../../lib/base.php');

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

$aid = $_POST['id'];
$addressbook = OC_Contacts_App::getAddressbook( $aid );

$fn = $_POST['fn'];
$values = $_POST['value'];
$parameters = $_POST['parameters'];

$vcard = new OC_VObject('VCARD');
$vcard->setUID();
$vcard->setString('FN',$fn);

// Data to add ...
$add = array('TEL', 'EMAIL', 'ORG');
$address = false;
for($i = 0; $i < 7; $i++){
	if( isset($values['ADR'][$i] ) && $values['ADR'][$i]) $address = true;
}
if( $address ) $add[] = 'ADR';

// Add data
foreach( $add as $propname){
	if( !( isset( $values[$propname] ) && $values[$propname] )){
		continue;
	}
	$value = $values[$propname];
	if( isset( $parameters[$propname] ) && count( $parameters[$propname] )){
		$prop_parameters = $parameters[$propname];
	}
	else{
		$prop_parameters = array();
	}
	$vcard->addProperty($propname, $value, $prop_parameters);
}
$id = OC_Contacts_VCard::add($aid,$vcard->serialize());
OC_Log::write('contacts','ajax/addcard.php - adding id: '.$id,OC_Log::DEBUG);

OC_Contacts_App::renderDetails($id, $vcard);

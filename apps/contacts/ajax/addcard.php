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

$aid = $_POST['id'];
$l10n = new OC_L10N('contacts');

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

$addressbook = OC_Contacts_Addressbook::find( $aid );
if( $addressbook === false || $addressbook['userid'] != OC_USER::getUser()){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('This is not your addressbook.')))); // Same here (as with the contact error). Could this error be improved?
	exit();
}

$fn = $_POST['fn'];
$values = $_POST['value'];
$parameters = $_POST['parameters'];

$vcard = new Sabre_VObject_Component('VCARD');
$vcard->add(new Sabre_VObject_Property('FN',$fn));
$vcard->add(new Sabre_VObject_Property('UID',OC_Contacts_VCard::createUID()));
foreach(array('ADR', 'TEL', 'EMAIL', 'ORG') as $propname){
	if( !( isset( $values[$propname] ) && $values[$propname] )){
		continue;
	}
	$value = $values[$propname];
	if( isset( $parameters[$propname] ) && count$parameters[$propname] ){
		$prop_parameters = $parameters[$propname];
	}
	else{
		$prop_parameters = array();
	}
	OC_Contacts_VCard::addVCardProperty($vcard, $propname, $value, $prop_parameters);
}
$id = OC_Contacts_VCard::add($aid,$vcard->serialize());

$details = OC_Contacts_VCard::structureContact($vcard);
$name = $details['FN'][0]['value'];
$tmpl = new OC_Template('contacts','part.details');
$tmpl->assign('details',$details);
$tmpl->assign('id',$id);
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'id' => $id, 'name' => $name, 'page' => $page )));

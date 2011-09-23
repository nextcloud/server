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
if( !OC_User::isLoggedIn()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('You need to log in.'))));
	exit();
}

$addressbook = OC_Contacts_Addressbook::find( $aid );
if( $addressbook === false || $addressbook['userid'] != OC_USER::getUser()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('This is not your addressbook.')))); // Same here (as with the contact error). Could this error be improved?
	exit();
}

$fn = $_POST['fn'];

$vcard = new Sabre_VObject_Component('VCARD');
$vcard->add(new Sabre_VObject_Property('FN',$fn));
$vcard->add(new Sabre_VObject_Property('UID',OC_Contacts_VCard::createUID()));
$id = OC_Contacts_VCard::add($aid,$vcard->serialize());

$details = OC_Contacts_VCard::structureContact($vcard);
$tmpl = new OC_Template('contacts','part.details');
$tmpl->assign('details',$details);
$tmpl->assign('id',$id);
$page = $tmpl->fetchPage();

echo json_encode( array( 'status' => 'success', 'data' => array( 'id' => $id, 'page' => $page )));

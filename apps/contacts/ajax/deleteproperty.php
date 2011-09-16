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

$id = $_GET['id'];
$checksum = $_GET['checksum'];


$l10n = new OC_L10N('contacts');

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('You need to log in!'))));
	exit();
}


$card = OC_Contacts_VCard::find( $id );
if( $card === false ){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('Can not find Contact!'))));
	exit();
}

$addressbook = OC_Contacts_Addressbook::find( $card['addressbookid'] );
if( $addressbook === false || $addressbook['userid'] != OC_USER::getUser()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('This is not your contact!'))));
	exit();
}

$vcard = OC_Contacts_VCard::parse($card['carddata']);
// Check if the card is valid
if(is_null($vcard)){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('Unable to parse vCard!'))));
	exit();
}

$line = null;
for($i=0;$i<count($vcard->children);$i++){
	if(md5($vcard->children[$i]->serialize()) == $checksum ){
		$line = $i;
	}
}
if(is_null($line)){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => $l10n->t('Information about vCard is incorrect. Please reload page!'))));
	exit();
}

unset($vcard->children[$line]);

OC_Contacts_VCard::edit($id,$vcard->serialize());
echo json_encode( array( 'status' => 'success', 'data' => array( 'id' => $id )));

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
require_once('../../lib/base.php');

$id = $_GET['id'];

$l10n = new OC_L10N('contacts');

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo $l10n->t('You need to log in!');
	exit();
}


$card = OC_Contacts_VCard::find( $id );
if( $card === false ){
	echo $l10n->t('Can not find Contact!');
	exit();
}

$addressbook = OC_Contacts_Addressbook::find( $card['addressbookid'] );
if( $addressbook === false || $addressbook['userid'] != OC_USER::getUser()){
	echo $l10n->t('This is not your contact!');
	exit();
}

$content = OC_Contacts_VCard::parse($card['carddata']);

// invalid vcard
if( is_null($content)){
	echo $l10n->t('This card is not RFC compatible!');
	exit();
}
// Photo :-)
foreach($content->children as $child){
	if($child->name == 'PHOTO'){
		$mime = 'image/jpeg';
		foreach($child->parameters as $parameter){
			if( $parameter->name == 'TYPE' ){
				$mime = $parameter->value;
			}
		}
		$photo = base64_decode($child->value);
		header('Content-Type: '.$mime);
		header('Content-Length: ' . strlen($photo));
		echo $photo;
		exit();
	}
}
// Logo :-/
foreach($content->children as $child){
	if($child->name == 'PHOTO'){
		$mime = 'image/jpeg';
		foreach($child->parameters as $parameter){
			if($parameter->name == 'TYPE'){
				$mime = $parameter->value;
			}
		}
		$photo = base64_decode($child->value());
		header('Content-Type: '.$mime);
		header('Content-Length: ' . strlen($photo));
		echo $photo;
		exit();
	}
}

// Not found :-(
echo $l10n->t('This card does not contain photo data!');

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
OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('contacts');

$id = $_GET['id'];
if(isset($GET['refresh'])) {
	header("Cache-Control: no-cache, no-store, must-revalidate");
}
$l10n = new OC_L10N('contacts');

$card = OC_Contacts_VCard::find( $id );
if( $card === false ){
	echo $l10n->t('Contact could not be found.');
	exit();
}

$addressbook = OC_Contacts_Addressbook::find( $card['addressbookid'] );
if( $addressbook === false || $addressbook['userid'] != OC_USER::getUser()){
	echo $l10n->t('This is not your contact.'); // This is a weird error, why would it come up? (Better feedback for users?)
	exit();
}

$content = OC_VObject::parse($card['carddata']);
$image = new OC_Image();
// invalid vcard
if( is_null($content)){
	$image->loadFromFile('img/person_large.png');
	header('Content-Type: '.$image->mimeType());
	$image();
	//echo $l10n->t('This card is not RFC compatible.');
	exit();
} else {
	// Photo :-)
	foreach($content->children as $child){
		if($child->name == 'PHOTO'){
			$mime = 'image/jpeg';
			foreach($child->parameters as $parameter){
				if( $parameter->name == 'TYPE' ){
					$mime = $parameter->value;
				}
			}
			if($image->loadFromBase64($child->value)) {
				if($image->width() > 200 || $image->height() > 200) {
					$image->resize(200);
				}
				header('Content-Type: '.$mime);
				$image();
				exit();
			} else {
				$image->loadFromFile('img/person_large.png');
				header('Content-Type: '.$image->mimeType());
				$image();
			}
			//$photo = base64_decode($child->value);
			//header('Content-Type: '.$mime);
			//header('Content-Length: ' . strlen($photo));
			//echo $photo;
			//exit();
		}
	}
}
$image->loadFromFile('img/person_large.png');
header('Content-Type: '.$image->mimeType());
$image();
/*
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
*/
// Not found :-(
//echo $l10n->t('This card does not contain a photo.');

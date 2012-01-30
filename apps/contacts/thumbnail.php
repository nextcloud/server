<?php
/**
 * ownCloud - Addressbook
 *
 * @author Thomas Tanghus
 * @copyright 2011-2012 Thomas Tanghus <thomas@tanghus.net>
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
OC_JSON::checkLoggedIn();
//OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('contacts');

function getStandardImage(){
	$date = new DateTime('now');
	$date->add(new DateInterval('P10D'));
	header('Expires: '.$date->format(DateTime::RFC850));
	header('Cache-Control: cache');
	header('Pragma: cache');
	header('Location: '.OC_Helper::imagePath('contacts', 'person.png'));
	exit();
// 	$src_img = imagecreatefrompng('img/person.png');
// 	header('Content-Type: image/png');
// 	imagepng($src_img);
// 	imagedestroy($src_img);
}

if(!function_exists('imagecreatefromjpeg')) {
	OC_Log::write('contacts','thumbnail.php. GD module not installed',OC_Log::DEBUG);
	getStandardImage();
	exit();
}

$id = $_GET['id'];

$l10n = new OC_L10N('contacts');

$card = OC_Contacts_VCard::find( $id );
if( $card === false ){
	OC_Log::write('contacts','thumbnail.php. Contact could not be found: '.$id,OC_Log::ERROR);
	getStandardImage();
	exit();
}

// FIXME: Is this check necessary? It just takes up CPU time.
$addressbook = OC_Contacts_Addressbook::find( $card['addressbookid'] );
if( $addressbook === false || $addressbook['userid'] != OC_USER::getUser()){
	OC_Log::write('contacts','thumbnail.php. Wrong contact/addressbook - WTF?',OC_Log::ERROR);
	exit();
}

$content = OC_VObject::parse($card['carddata']);

// invalid vcard
if( is_null($content)){
	OC_Log::write('contacts','thumbnail.php. The VCard for ID '.$id.' is not RFC compatible',OC_Log::ERROR);
	getStandardImage();
	exit();
}

$thumbnail_size = 23;

// Find the photo from VCard.
foreach($content->children as $child){
	if($child->name == 'PHOTO'){
		$image = new OC_Image();
		if($image->loadFromBase64($child->value)) {
			if($image->centerCrop()) {
				if($image->resize($thumbnail_size)) {
					header('ETag: '.md5($child->value));
					if(!$image()) {
						OC_Log::write('contacts','thumbnail.php. Couldn\'t display thumbnail for ID '.$id,OC_Log::ERROR);
						getStandardImage();
						exit();
					}
				} else {
					OC_Log::write('contacts','thumbnail.php. Couldn\'t resize thumbnail for ID '.$id,OC_Log::ERROR);
					getStandardImage();
					exit();
				}
			}else{
				OC_Log::write('contacts','thumbnail.php. Couldn\'t crop thumbnail for ID '.$id,OC_Log::ERROR);
				getStandardImage();
				exit();
			}
		} else {
			OC_Log::write('contacts','thumbnail.php. Couldn\'t load image string for ID '.$id,OC_Log::ERROR);
			getStandardImage();
			exit();
		}
		exit();
	}
}
getStandardImage();

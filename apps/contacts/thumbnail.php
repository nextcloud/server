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

if(!function_exists('imagecreatefromjpeg')) {
	OC_Log::write('contacts','GD module not installed',OC_Log::ERROR);
	header('Content-Type: image/png');
	// TODO: Check if it works to read the file and echo the content.
	return 'img/person.png';
}

function getStandardImage(){
	$src_img = imagecreatefrompng('img/person.png');
	header('Content-Type: image/png');
	imagepng($src_img);
	imagedestroy($src_img);
}


$id = $_GET['id'];

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

// invalid vcard
if( is_null($content)){
	echo $l10n->t('This card is not RFC compatible.');
	exit();
}

// define the width and height for the thumbnail
// note that theese dimmensions are considered the maximum dimmension and are not fixed,
// because we have to keep the image ratio intact or it will be deformed
$thumbnail_width = 23;
$thumbnail_height = 23;

// Photo :-)
foreach($content->children as $child){
	if($child->name == 'PHOTO'){
		foreach($child->parameters as $parameter){
			if( $parameter->name == 'TYPE' ){
				$mime = $parameter->value;
			}
		}
		$data = base64_decode($child->value);
		$src_img = imagecreatefromstring($data);
		if ($src_img !== false) {
			//gets the dimmensions of the image
			$width_orig=imageSX($src_img);
			$height_orig=imageSY($src_img);
			$ratio_orig = $width_orig/$height_orig;
			
			if ($thumbnail_width/$thumbnail_height > $ratio_orig) {
				$new_height = $thumbnail_width/$ratio_orig;
				$new_width = $thumbnail_width;
			} else {
				$new_width = $thumbnail_height*$ratio_orig;
				$new_height = $thumbnail_height;
			}
			
			$x_mid = $new_width/2;  //horizontal middle
			$y_mid = $new_height/2; //vertical middle
			
			$process = imagecreatetruecolor(round($new_width), round($new_height)); 
			if ($process == false) {
				getStandardImage();
				//echo 'Error creating process image: '.$new_width.'x'.$new_height;
				OC_Log::write('contacts','Error creating process image for '.$id.' '.$new_width.'x'.$new_height,OC_Log::ERROR);
				imagedestroy($process);
				imagedestroy($src_img);
				exit();
			}

			imagecopyresampled($process, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);
			if ($process == false) {
				getStandardImage();
				//echo 'Error resampling process image: '.$new_width.'x'.$new_height;
				OC_Log::write('contacts','Error resampling process image for '.$id.' '.$new_width.'x'.$new_height,OC_Log::ERROR);
				imagedestroy($process);
				imagedestroy($src_img);
				exit();
			}
			$thumb = imagecreatetruecolor($thumbnail_width, $thumbnail_height); 
			if ($process == false) {
				getStandardImage();
				//echo 'Error creating thumb image: '.$thumbnail_width.'x'.$thumbnail_height;
				OC_Log::write('contacts','Error creating thumb image for '.$id.' '.$thumbnail_width.'x'.$thumbnail_height,OC_Log::ERROR);
				imagedestroy($process);
				imagedestroy($src_img);
				exit();
			}
			imagecopyresampled($thumb, $process, 0, 0, ($x_mid-($thumbnail_width/2)), ($y_mid-($thumbnail_height/2)), $thumbnail_width, $thumbnail_height, $thumbnail_width, $thumbnail_height);
			if ($thumb !== false) {
				header('Content-Type: image/png');
				imagepng($thumb);
			} else {
				getStandardImage();
				OC_Log::write('contacts','Error resampling thumb image for '.$id.' '.$thumbnail_width.'x'.$thumbnail_height,OC_Log::ERROR);
				//echo 'An error occurred resampling thumb.';
			}
			imagedestroy($thumb);
			imagedestroy($process);
			imagedestroy($src_img);
		}
		else {
			getStandardImage();
		}
		exit();
	}
}
getStandardImage();

// Not found :-(
//echo $l10n->t('This card does not contain a photo.');

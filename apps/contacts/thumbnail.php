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
	header('Expires: '.$date->format(DateTime::RFC2822));
	header('Cache-Control: cache');
	header('Pragma: cache');
	header("HTTP/1.1 307 Temporary Redirect");
	header('Location: '.OC_Helper::imagePath('contacts', 'person.png'));
}

if(!function_exists('imagecreatefromjpeg')) {
	OC_Log::write('contacts','thumbnail.php. GD module not installed',OC_Log::DEBUG);
	getStandardImage();
	exit();
}

$id = $_GET['id'];

$contact = OC_Contacts_App::getContactVCard($id);

// invalid vcard
if( is_null($contact)){
	OC_Log::write('contacts','thumbnail.php. The VCard for ID '.$id.' is not RFC compatible',OC_Log::ERROR);
	getStandardImage();
	exit();
}

$thumbnail_size = 23;

// Find the photo from VCard.
$image = new OC_Image();
$photo = $contact->getAsString('PHOTO');
$etag = md5($photo);
$rev_string = $contact->getAsString('REV');
if ($rev_string) {
	$rev = DateTime::createFromFormat(DateTime::W3C, $rev_string);
	$last_modified_time = $rev->format(DateTime::RFC2822);
} else {
	$last_modified_time = null;
}

header('Cache-Control: cache');
header('Pragma: cache');
if ($rev_string) {
	header('Last-Modified: '.$last_modified_time);
}
header('ETag: '.$etag);

if (@trim($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time ||
    @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
	header('HTTP/1.1 304 Not Modified');
	exit;
}

if($image->loadFromBase64($photo)) {
	if($image->centerCrop()) {
		if($image->resize($thumbnail_size)) {
			if($image->show()) {
				exit();
			} else {
				OC_Log::write('contacts','thumbnail.php. Couldn\'t display thumbnail for ID '.$id,OC_Log::ERROR);
			}
		} else {
			OC_Log::write('contacts','thumbnail.php. Couldn\'t resize thumbnail for ID '.$id,OC_Log::ERROR);
		}
	}else{
		OC_Log::write('contacts','thumbnail.php. Couldn\'t crop thumbnail for ID '.$id,OC_Log::ERROR);
	}
} else {
	OC_Log::write('contacts','thumbnail.php. Couldn\'t load image string for ID '.$id,OC_Log::ERROR);
}
getStandardImage();

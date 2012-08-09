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

OCP\JSON::checkLoggedIn();
//OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('contacts');
session_write_close();

function getStandardImage() {
	OCP\Response::enableCaching();
	OCP\Response::redirect(OCP\Util::imagePath('contacts', 'person.png'));
}

if(!extension_loaded('gd') || !function_exists('gd_info')) {
	OCP\Util::writeLog('contacts',
		'thumbnail.php. GD module not installed', OCP\Util::DEBUG);
	getStandardImage();
	exit();
}

$id = $_GET['id'];
$caching = null;

$contact = OC_Contacts_App::getContactVCard($id);

// invalid vcard
if(is_null($contact)) {
	OCP\Util::writeLog('contacts',
		'thumbnail.php. The VCard for ID ' . $id . ' is not RFC compatible',
		OCP\Util::ERROR);
	getStandardImage();
	exit();
}
OCP\Response::enableCaching($caching);
OC_Contacts_App::setLastModifiedHeader($contact);

$thumbnail_size = 23;

// Find the photo from VCard.
$image = new OC_Image();
$photo = $contact->getAsString('PHOTO');
if($photo) {
	if($image->loadFromBase64($photo)) {
		if($image->centerCrop()) {
			if($image->resize($thumbnail_size)) {
				$modified = OC_Contacts_App::lastModified($contact);
				// Force refresh if modified within the last minute.
				if(!is_null($modified)) {
					$caching = (time() - $modified->format('U') > 60) ? null : 0;
				}
				OCP\Response::enableCaching($caching);
				if(!is_null($modified)) {
					OCP\Response::setLastModifiedHeader($modified);
				}
				OCP\Response::setETagHeader(md5($photo));
				if($image->show()) {
					exit();
				} else {
					OCP\Util::writeLog('contacts',
						'thumbnail.php. Couldn\'t display thumbnail for ID ' . $id,
						OCP\Util::ERROR);
				}
			} else {
				OCP\Util::writeLog('contacts',
					'thumbnail.php. Couldn\'t resize thumbnail for ID ' . $id,
					OCP\Util::ERROR);
			}
		}else{
			OCP\Util::writeLog('contacts',
				'thumbnail.php. Couldn\'t crop thumbnail for ID ' . $id,
				OCP\Util::ERROR);
		}
	} else {
		OCP\Util::writeLog('contacts',
			'thumbnail.php. Couldn\'t load image string for ID ' . $id,
			OCP\Util::ERROR);
	}
}
getStandardImage();

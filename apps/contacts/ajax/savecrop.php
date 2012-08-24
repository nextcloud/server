<?php
/**
 * ownCloud - Addressbook
 *
 * @author Thomas Tanghus
 * @copyright 2012 Thomas Tanghus <thomas@tanghus.net>
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
// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
OCP\JSON::callCheck();

// Firefox and Konqueror tries to download application/json for me.  --Arthur
OCP\JSON::setContentTypeHeader('text/plain; charset=utf-8');

require_once 'loghandler.php';

$image = null;

$x1 = (isset($_POST['x1']) && $_POST['x1']) ? $_POST['x1'] : 0;
//$x2 = isset($_POST['x2']) ? $_POST['x2'] : -1;
$y1 = (isset($_POST['y1']) && $_POST['y1']) ? $_POST['y1'] : 0;
//$y2 = isset($_POST['y2']) ? $_POST['y2'] : -1;
$w = (isset($_POST['w']) && $_POST['w']) ? $_POST['w'] : -1;
$h = (isset($_POST['h']) && $_POST['h']) ? $_POST['h'] : -1;
$tmpkey = isset($_POST['tmpkey']) ? $_POST['tmpkey'] : '';
$id = isset($_POST['id']) ? $_POST['id'] : '';

if($tmpkey == '') {
	bailOut('Missing key to temporary file.');
}

if($id == '') {
	bailOut('Missing contact id.');
}

OCP\Util::writeLog('contacts', 'savecrop.php: key: '.$tmpkey, OCP\Util::DEBUG);

$data = OC_Cache::get($tmpkey);
if($data) {
	$image = new OC_Image();
	if($image->loadFromdata($data)) {
		$w = ($w != -1 ? $w : $image->width());
		$h = ($h != -1 ? $h : $image->height());
		OCP\Util::writeLog('contacts',
			'savecrop.php, x: '.$x1.' y: '.$y1.' w: '.$w.' h: '.$h,
			OCP\Util::DEBUG);
		if($image->crop($x1, $y1, $w, $h)) {
			if(($image->width() <= 200 && $image->height() <= 200)
						|| $image->resize(200)) {
				$vcard = OC_Contacts_App::getContactVCard($id);
				if(!$vcard) {
					OC_Cache::remove($tmpkey);
					bailOut(OC_Contacts_App::$l10n
						->t('Error getting contact object.'));
				}
				if($vcard->__isset('PHOTO')) {
					OCP\Util::writeLog('contacts',
						'savecrop.php: PHOTO property exists.',
						OCP\Util::DEBUG);
					$property = $vcard->__get('PHOTO');
					if(!$property) {
						OC_Cache::remove($tmpkey);
						bailOut(OC_Contacts_App::$l10n
							->t('Error getting PHOTO property.'));
					}
					$property->setValue($image->__toString());
					$property->parameters[]
						= new Sabre_VObject_Parameter('ENCODING', 'b');
					$property->parameters[]
						= new Sabre_VObject_Parameter('TYPE', $image->mimeType());
					$vcard->__set('PHOTO', $property);
				} else {
					OCP\Util::writeLog('contacts',
						'savecrop.php: files: Adding PHOTO property.',
						OCP\Util::DEBUG);
					$vcard->addProperty('PHOTO',
						$image->__toString(), array('ENCODING' => 'b',
						'TYPE' => $image->mimeType()));
				}
				$now = new DateTime;
				$vcard->setString('REV', $now->format(DateTime::W3C));
				if(!OC_Contacts_VCard::edit($id, $vcard)) {
					bailOut(OC_Contacts_App::$l10n->t('Error saving contact.'));
				}
				OCP\JSON::success(array(
					'data' => array(
						'id' => $id,
						'width' => $image->width(),
						'height' => $image->height(),
						'lastmodified' => OC_Contacts_App::lastModified($vcard)->format('U')
					)
				));
			} else {
				bailOut(OC_Contacts_App::$l10n->t('Error resizing image'));
			}
		} else {
			bailOut(OC_Contacts_App::$l10n->t('Error cropping image'));
		}
	} else {
		bailOut(OC_Contacts_App::$l10n->t('Error creating temporary image'));
	}
} else {
	bailOut(OC_Contacts_App::$l10n->t('Error finding image: ').$tmpkey);
}

OC_Cache::remove($tmpkey);

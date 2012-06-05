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

// Firefox and Konqueror tries to download application/json for me.  --Arthur
OCP\JSON::setContentTypeHeader('text/plain');
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/currentphoto.php: '.$msg, OCP\Util::ERROR);
	exit();
}

if (!isset($_GET['id'])) {
	bailOut(OC_Contacts_App::$l10n->t('No contact ID was submitted.'));
}

$contact = OC_Contacts_App::getContactVCard($_GET['id']);
// invalid vcard
if( is_null($contact)) {
	bailOut(OC_Contacts_App::$l10n->t('Error reading contact photo.'));
} else {
	$image = new OC_Image();
	if(!$image->loadFromBase64($contact->getAsString('PHOTO'))) {
		$image->loadFromBase64($contact->getAsString('LOGO'));
	}
	if($image->valid()) {
		$tmpkey = 'contact-photo-'.$contact->getAsString('UID');
		if(OC_Cache::set($tmpkey, $image->data(), 600)) {
			OCP\JSON::success(array('data' => array('id'=>$_GET['id'], 'tmp'=>$tmpkey)));
			exit();
		} else {
			bailOut(OC_Contacts_App::$l10n->t('Error saving temporary file.'));
		}
	} else {
		bailOut(OC_Contacts_App::$l10n->t('The loading photo is not valid.'));
	}
}

?>

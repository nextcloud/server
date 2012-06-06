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
function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/oc_photo.php: '.$msg, OCP\Util::ERROR);
	exit();
}

if(!isset($_GET['id'])) {
	bailOut(OC_Contacts_App::$l10n->t('No contact ID was submitted.'));
}

if(!isset($_GET['path'])) {
	bailOut(OC_Contacts_App::$l10n->t('No photo path was submitted.'));
}

$localpath = OC_Filesystem::getLocalFile($_GET['path']);
$tmpkey = 'contact-photo-'.$_GET['id'];

if(!file_exists($localpath)) {
	bailOut(OC_Contacts_App::$l10n->t('File doesn\'t exist:').$localpath);
}

$image = new OC_Image();
if(!$image) {
	bailOut(OC_Contacts_App::$l10n->t('Error loading image.'));
}
if(!$image->loadFromFile($localpath)) {
	bailOut(OC_Contacts_App::$l10n->t('Error loading image.'));
}
if($image->width() > 400 || $image->height() > 400) {
	$image->resize(400); // Prettier resizing than with browser and saves bandwidth.
}
if(!$image->fixOrientation()) { // No fatal error so we don't bail out.
	OCP\Util::writeLog('contacts','ajax/oc_photo.php: Couldn\'t save correct image orientation: '.$localpath, OCP\Util::DEBUG);
}
if(OC_Cache::set($tmpkey, $image->data(), 600)) {
	OCP\JSON::success(array('data' => array('id'=>$_GET['id'], 'tmp'=>$tmpkey)));
	exit();
} else {
	bailOut('Couldn\'t save temporary image: '.$tmpkey);
}

?>

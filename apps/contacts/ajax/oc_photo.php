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
// Firefox and Konqueror tries to download application/json for me.  --Arthur
OCP\JSON::setContentTypeHeader('text/plain');
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
OCP\JSON::callCheck();
function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/oc_photo.php: '.$msg, OCP\Util::ERROR);
	exit();
}
function debug($msg) {
	OCP\Util::writeLog('contacts','ajax/oc_photo.php: '.$msg, OCP\Util::DEBUG);
}

if(!isset($_GET['id'])) {
	bailOut(OC_Contacts_App::$l10n->t('No contact ID was submitted.'));
}

if(!isset($_GET['path'])) {
	bailOut(OC_Contacts_App::$l10n->t('No photo path was submitted.'));
}

$localpath = OC_Filesystem::getLocalFile($_GET['path']);
$tmpfname = tempnam(get_temp_dir(), "occOrig");

if(!file_exists($localpath)) {
	bailOut(OC_Contacts_App::$l10n->t('File doesn\'t exist:').$localpath);
}
file_put_contents($tmpfname, file_get_contents($localpath));

$image = new OC_Image();
if(!$image) {
	bailOut(OC_Contacts_App::$l10n->t('Error loading image.'));
}
if(!$image->loadFromFile($tmpfname)) {
	bailOut(OC_Contacts_App::$l10n->t('Error loading image.'));
}
if($image->width() > 400 || $image->height() > 400) {
	$image->resize(400); // Prettier resizing than with browser and saves bandwidth.
}
if(!$image->fixOrientation()) { // No fatal error so we don't bail out.
	debug('Couldn\'t save correct image orientation: '.$tmpfname);
}
if($image->save($tmpfname)) {
	OCP\JSON::success(array('data' => array('id'=>$_GET['id'], 'tmp'=>$tmpfname)));
	exit();
} else {
	bailOut('Couldn\'t save temporary image: '.$tmpfname);
}

?>

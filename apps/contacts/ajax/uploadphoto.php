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
// Firefox and Konqueror tries to download application/json for me.  --Arthur
OCP\JSON::setContentTypeHeader('text/plain');
function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/uploadphoto.php: '.$msg, OCP\Util::DEBUG);
	exit();
}
function debug($msg) {
	OCP\Util::writeLog('contacts','ajax/uploadphoto.php: '.$msg, OCP\Util::DEBUG);
}

// If it is a Drag'n'Drop transfer it's handled here.
$fn = (isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : false);
if ($fn) {
	if (!isset($_GET['id'])) {
		bailOut(OC_Contacts_App::$l10n->t('No contact ID was submitted.'));
	}
	$id = $_GET['id'];
	$tmpkey = 'contact-photo-'.md5($fn);
	$data = file_get_contents('php://input');
	$image = new OC_Image();
	sleep(1); // Apparently it needs time to load the data.
	if($image->loadFromData($data)) {
		if($image->width() > 400 || $image->height() > 400) {
			$image->resize(400); // Prettier resizing than with browser and saves bandwidth.
		}
		if(!$image->fixOrientation()) { // No fatal error so we don't bail out.
			debug('Couldn\'t save correct image orientation: '.$tmpkey);
		}
		if(OC_Cache::set($tmpkey, $image->data(), 600)) {
			OCP\JSON::success(array('data' => array('mime'=>$_SERVER['CONTENT_TYPE'], 'name'=>$fn, 'id'=>$id, 'tmp'=>$tmpkey)));
			exit();
		} else {
			bailOut(OC_Contacts_App::$l10n->t('Couldn\'t save temporary image: ').$tmpkey);
		}
	} else {
		bailOut(OC_Contacts_App::$l10n->t('Couldn\'t load temporary image: ').$tmpkey.$data);
	}
}

// Uploads from file dialog are handled here.
if (!isset($_POST['id'])) {
	bailOut(OC_Contacts_App::$l10n->t('No contact ID was submitted.'));
}
if (!isset($_FILES['imagefile'])) {
	bailOut(OC_Contacts_App::$l10n->t('No file was uploaded. Unknown error'));
}

$error = $_FILES['imagefile']['error'];
if($error !== UPLOAD_ERR_OK) {
	$errors = array(
		0=>OC_Contacts_App::$l10n->t("There is no error, the file uploaded with success"),
		1=>OC_Contacts_App::$l10n->t("The uploaded file exceeds the upload_max_filesize directive in php.ini").ini_get('upload_max_filesize'),
		2=>OC_Contacts_App::$l10n->t("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"),
		3=>OC_Contacts_App::$l10n->t("The uploaded file was only partially uploaded"),
		4=>OC_Contacts_App::$l10n->t("No file was uploaded"),
		6=>OC_Contacts_App::$l10n->t("Missing a temporary folder")
	);
	bailOut($errors[$error]);
}
$file=$_FILES['imagefile'];

if(file_exists($file['tmp_name'])) {
	$tmpkey = 'contact-photo-'.md5(basename($file['tmp_name']));
	$image = new OC_Image();
	if($image->loadFromFile($file['tmp_name'])) {
		if($image->width() > 400 || $image->height() > 400) {
			$image->resize(400); // Prettier resizing than with browser and saves bandwidth.
		}
		if(!$image->fixOrientation()) { // No fatal error so we don't bail out.
			debug('Couldn\'t save correct image orientation: '.$tmpkey);
		}
		if(OC_Cache::set($tmpkey, $image->data(), 600)) {
			OCP\JSON::success(array('data' => array('mime'=>$file['type'],'size'=>$file['size'],'name'=>$file['name'], 'id'=>$_POST['id'], 'tmp'=>$tmpkey)));
			exit();
		} else {
			bailOut(OC_Contacts_App::$l10n->t('Couldn\'t save temporary image: ').$tmpkey);
		}
	} else {
		bailOut(OC_Contacts_App::$l10n->t('Couldn\'t load temporary image: ').$file['tmp_name']);
	}
} else {
	bailOut('Temporary file: \''.$file['tmp_name'].'\' has gone AWOL?');
}
?>

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
function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/uploadphoto.php: '.$msg, OCP\Util::DEBUG);
	exit();
}
function debug($msg) {
	OCP\Util::writeLog('contacts','ajax/uploadphoto.php: '.$msg, OCP\Util::DEBUG);
}
OCP\JSON::setContentTypeHeader('text/plain');
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
OCP\JSON::callCheck();

// If it is a Drag'n'Drop transfer it's handled here.
$fn = (isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : false);
if ($fn) {
	// AJAX call
	if (!isset($_GET['id'])) {
		OCP\Util::writeLog('contacts','ajax/uploadphoto.php: No contact ID was submitted.', OCP\Util::DEBUG);
		OCP\JSON::error(array('data' => array( 'message' => 'No contact ID was submitted.' )));
		exit();
	}
	$id = $_GET['id'];
	$tmpfname = tempnam(get_temp_dir(), 'occOrig');
	file_put_contents($tmpfname, file_get_contents('php://input'));
	$image = new OC_Image();
	if($image->loadFromFile($tmpfname)) {
		if($image->width() > 400 || $image->height() > 400) {
			$image->resize(400); // Prettier resizing than with browser and saves bandwidth.
		}
		if(!$image->fixOrientation()) { // No fatal error so we don't bail out.
			debug('Couldn\'t save correct image orientation: '.$tmpfname);
		}
		if($image->save($tmpfname)) {
			OCP\JSON::success(array('data' => array('mime'=>$_SERVER['CONTENT_TYPE'], 'name'=>$fn, 'id'=>$id, 'tmp'=>$tmpfname)));
			exit();
		} else {
			bailOut('Couldn\'t save temporary image: '.$tmpfname);
		}
	} else {
		bailOut('Couldn\'t load temporary image: '.$file['tmp_name']);
	}
}


if (!isset($_POST['id'])) {
	OCP\Util::writeLog('contacts','ajax/uploadphoto.php: No contact ID was submitted.', OCP\Util::DEBUG);
	OCP\JSON::error(array('data' => array( 'message' => 'No contact ID was submitted.' )));
	exit();
}
if (!isset($_FILES['imagefile'])) {
	OCP\Util::writeLog('contacts','ajax/uploadphoto.php: No file was uploaded. Unknown error.', OCP\Util::DEBUG);
	OCP\JSON::error(array('data' => array( 'message' => 'No file was uploaded. Unknown error' )));
	exit();
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

$tmpfname = tempnam(get_temp_dir(), "occOrig");
if(file_exists($file['tmp_name'])) {
	$image = new OC_Image();
	if($image->loadFromFile($file['tmp_name'])) {
		if($image->width() > 400 || $image->height() > 400) {
			$image->resize(400); // Prettier resizing than with browser and saves bandwidth.
		}
		if(!$image->fixOrientation()) { // No fatal error so we don't bail out.
			debug('Couldn\'t save correct image orientation: '.$tmpfname);
		}
		if($image->save($tmpfname)) {
			OCP\JSON::success(array('data' => array('mime'=>$file['type'],'size'=>$file['size'],'name'=>$file['name'], 'id'=>$_POST['id'], 'tmp'=>$tmpfname)));
			exit();
		} else {
			bailOut('Couldn\'t save temporary image: '.$tmpfname);
		}
	} else {
		bailOut('Couldn\'t load temporary image: '.$file['tmp_name']);
	}
} else {
	bailOut('Temporary file: \''.$file['tmp_name'].'\' has gone AWOL?');
}

?>

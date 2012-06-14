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
 * TODO: Translatable strings.
 *       Remember to delete tmp file at some point.
 */
 
function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/savecrop.php: '.$msg, OCP\Util::DEBUG);
	exit();
}

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
OCP\JSON::callCheck();

// Firefox and Konqueror tries to download application/json for me.  --Arthur
OCP\JSON::setContentTypeHeader('text/plain');

$image = null;

$x1 = (isset($_POST['x1']) && $_POST['x1']) ? $_POST['x1'] : 0;
//$x2 = isset($_POST['x2']) ? $_POST['x2'] : -1;
$y1 = (isset($_POST['y1']) && $_POST['y1']) ? $_POST['y1'] : 0;
//$y2 = isset($_POST['y2']) ? $_POST['y2'] : -1;
$w = (isset($_POST['w']) && $_POST['w']) ? $_POST['w'] : -1;
$h = (isset($_POST['h']) && $_POST['h']) ? $_POST['h'] : -1;
$tmp_path = isset($_POST['tmp_path']) ? $_POST['tmp_path'] : '';
$id = isset($_POST['id']) ? $_POST['id'] : '';

if($tmp_path == '') {
	bailOut('Missing path to temporary file.');
}

if($id == '') {
	bailOut('Missing contact id.');
}

OCP\Util::writeLog('contacts','savecrop.php: files: '.$tmp_path.'  exists: '.file_exists($tmp_path), OCP\Util::DEBUG);

if(file_exists($tmp_path)) {
	$image = new OC_Image();
	if($image->loadFromFile($tmp_path)) {
		$w = ($w != -1 ? $w : $image->width());
		$h = ($h != -1 ? $h : $image->height());
		OCP\Util::writeLog('contacts','savecrop.php, x: '.$x1.' y: '.$y1.' w: '.$w.' h: '.$h, OCP\Util::DEBUG);
		if($image->crop($x1, $y1, $w, $h)) {
			if(($image->width() <= 200 && $image->height() <= 200) || $image->resize(200)) {
				$tmpfname = tempnam(get_temp_dir(), "occCropped"); // create a new file because of caching issues.
				if($image->save($tmpfname)) {
					unlink($tmp_path);
					$card = OC_Contacts_App::getContactVCard($id);
					if(!$card) {
						unlink($tmpfname);
						bailOut('Error getting contact object.');
					}
					if($card->__isset('PHOTO')) {
						OCP\Util::writeLog('contacts','savecrop.php: PHOTO property exists.', OCP\Util::DEBUG);
						$property = $card->__get('PHOTO');
						if(!$property) {
							unlink($tmpfname);
							bailOut('Error getting PHOTO property.');
						}
						$property->setValue($image->__toString());
						$property->parameters[] = new Sabre_VObject_Parameter('ENCODING', 'b');
						$property->parameters[] = new Sabre_VObject_Parameter('TYPE', $image->mimeType());
						$card->__set('PHOTO', $property);
					} else {
						OCP\Util::writeLog('contacts','savecrop.php: files: Adding PHOTO property.', OCP\Util::DEBUG);
						$card->addProperty('PHOTO', $image->__toString(), array('ENCODING' => 'b', 'TYPE' => $image->mimeType()));
					}
					$now = new DateTime;
					$card->setString('REV', $now->format(DateTime::W3C));
					if(!OC_Contacts_VCard::edit($id,$card)) {
						bailOut('Error saving contact.');
					}
					unlink($tmpfname);
					//$result=array( "status" => "success", 'mime'=>$image->mimeType(), 'tmp'=>$tmp_path);
					$tmpl = new OCP\Template("contacts", "part.contactphoto");
					$tmpl->assign('tmp_path', $tmpfname);
					$tmpl->assign('mime', $image->mimeType());
					$tmpl->assign('id', $id);
					$tmpl->assign('refresh', true);
					$tmpl->assign('width', $image->width());
					$tmpl->assign('height', $image->height());
					$page = $tmpl->fetchPage();
					OCP\JSON::success(array('data' => array('page'=>$page, 'tmp'=>$tmpfname)));
					exit();
				} else {
					if(file_exists($tmpfname)) {
						unlink($tmpfname);
					}
					bailOut('Error saving temporary image');
				}
			} else {
				bailOut('Error resizing image');
			}
		} else {
			bailOut('Error cropping image');
		}
	} else {
		bailOut('Error creating temporary image');
	}
} else {
	bailOut('Error finding image: '.$tmp_path);
}

if($tmp_path != '' && file_exists($tmp_path)) {
	unlink($tmp_path);
}

?>

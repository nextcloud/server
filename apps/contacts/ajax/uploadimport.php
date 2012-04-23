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
// Init owncloud
require_once('../../../lib/base.php');

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');
function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('contacts','ajax/uploadimport.php: '.$msg, OC_Log::ERROR);
	exit();
}
function debug($msg) {
	OC_Log::write('contacts','ajax/uploadimport.php: '.$msg, OC_Log::DEBUG);
}

// If it is a Drag'n'Drop transfer it's handled here.
$fn = (isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : false);
if($fn) {
	/*$dir = OC_App::getStorage('contacts');
	$handle = $dir->touch(''.$fn);
	if(!$handle) {
		bailOut('Bugger!');
	} else {
		bailOut('Yippie!');
	}
	debug('Internal path: '.$dir->getInternalPath());*/
	$tmpfile = md5(rand());
	if(OC_Filesystem::file_put_contents('/'.$tmpfile, file_get_contents('php://input'))) {
		debug($fn.' uploaded');
		OC_JSON::success(array('data' => array('path'=>'', 'file'=>$tmpfile)));
	} else {
		bailOut(OC_Contacts_App::$l10n->t('Error uploading contacts to storage.'));
	}
}

?>

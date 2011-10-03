<?php
/**
 * ownCloud - files_texteditor
 *
 * @author Tom Needham
 * @copyright 2011 Tom Needham contact@tomneedham.com
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

// Save the file data
$filecontents = htmlspecialchars_decode($_POST['filecontents']);
$file = $_POST['file'];
$dir = $_POST['dir'];
$path = $dir.'/'.$file;
$force = isset($_POST['force']) ? $_POST['force'] : false;
$sessionname = sha1('oc_file_hash_'.$path);

function do_save($path,$filecontents){
	$sessionname = md5('oc_file_hash_'.$path);
	OC_Filesystem::update_session_file_hash($sessionname,sha1(htmlspecialchars($filecontents)));
	OC_Filesystem::file_put_contents($path, $filecontents);
}

// Check if file modified whilst editing?
if(isset($_SESSION[$sessionname])){
    if(!empty($_SESSION[$sessionname])){
        // Compare to current hash of file.
        $savedfilecontents = htmlspecialchars(OC_Filesystem::file_get_contents($path));
        $hash = md5($savedfilecontents);
        $originalhash = $_SESSION[$sessionname];
        // Compare with hash taken when file was opened
        if($hash != $originalhash){
            // Someone has played with the file while you were editing
            // Force save?
            if($force){
            	do_save($path, $filecontents);
            	OC_JSON::success();
            } else {	
            	// No force
            	OC_JSON::error(array('data' => array( 'message' => $l10n->t('The file has been edited since you opened it. Overwrite the file?'))));
            }
        } else  {
            // No body has edited it whilst you were, so save the file
            // Update the session hash.
            do_save($path,$filecontents);
            OC_JSON::success();
        }
	}
} else {
    // No session value set for soem reason, just save the file.
	do_save($path,$filecontents);
	OC_JSON::success();
}
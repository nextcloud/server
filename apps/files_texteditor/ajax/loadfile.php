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

// Set the session key for the file we are about to edit.
$path = isset($_GET['path']) ? $_GET['path'] : false;

if($path){
	$sessionname = md5('oc_file_hash_'.$path);
	$filecontents = OC_Filesystem::file_get_contents($path);
	OC_Filesystem::update_session_file_hash($sessionname,sha1(htmlspecialchars($filecontents)));
	OC_JSON::success();
} else {
	OC_JSON::error();
}	
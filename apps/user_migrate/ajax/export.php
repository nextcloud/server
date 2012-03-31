<?php

/**
 * ownCloud - user_migrate
 *
 * @author Tom Needham
 * @copyright 2012 Tom Needham tom@owncloud.com
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
OC_Util::checkAppEnabled('user_migrate');
	OC_JSON::error();	
	die();
// Which operation
if( $_GET['operation']=='create' ){
$uid = !empty( $_POST['uid'] ) ? $_POST['uid'] :  OC_User::getUser();
if( $uid != OC_User::getUser() ){
    // Needs to be admin to export someone elses account
	OC_JSON::error();	
	die();
}
// Create the export zip
if( !$path = OC_Migrate::export( $uid ) ){
	// Error
	OC_JSON::error();
	die();
} else {
	// Save path in session
	$_SESSION['ocuserexportpath'] = $path;
}
OC_JSON::success();
die();
} else if( $_GET['operation']=='download' ){
	// Download the export
	$path = isset( $_SESSION['ocuserexportpath'] ) ? $_SESSION['ocuserexportpath'] : false;
	if( !$path ){
		die();	
	}
	header("Content-Type: application/zip");
	header("Content-Disposition: attachment; filename=" . basename($path));
	header("Content-Length: " . filesize($path));
	@ob_end_clean();
	readfile($path);
	unlink( $path );
	$_SESSION['ocuserexportpath'] = '';	
}

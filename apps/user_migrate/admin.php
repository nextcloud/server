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
OC_Util::checkAdminUser();
OC_Util::checkAppEnabled('user_migrate');

// Import?
if (isset($_POST['user_import'])) {
	
	$root = OC::$SERVERROOT . "/";
	$importname = "owncloud_import_" . date("y-m-d_H-i-s");
	
	// Save data dir for later
	$datadir = OC_Config::getValue( 'datadirectory' );
	
	// Copy the uploaded file
	$from = $_FILES['owncloud_import']['tmp_name'];
	$to = get_temp_dir().'/'.$importname.'.zip';
	if( !move_uploaded_file( $from, $to ) ){
		OC_Log::write( 'user_migrate', "Failed to copy the uploaded file", OC_Log::ERROR );
		exit();		
	}
	
	if( !OC_Migrate::import( $to, 'user' ) ){
		die( 'failed to to import' );	
	}
		
		
} else {
// fill template
    $tmpl = new OC_Template('user_migrate', 'admin');
    return $tmpl->fetchPage();
}
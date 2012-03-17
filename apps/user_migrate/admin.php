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
		OC_Log::write('migration',"Failed to copy the uploaded file",OC_Log::INFO);
		exit();		
	}
	
	// Extract zip
	$zip = new ZipArchive();
	if ($zip->open(get_temp_dir().'/'.$importname.'.zip') != TRUE) {
		OC_Log::write('migration',"Failed to open zip file",OC_Log::INFO);
		exit();
	}
	$zip->extractTo(get_temp_dir().'/'.$importname.'/');
	$zip->close();
	
	$importdir = get_temp_dir() . '/' . $importname;
	
	// Delete uploaded file
	unlink( $importdir . '.zip' );
	
	// Find folder
	$files = scandir( $importdir );
	unset($files[0]);
	unset($files[1]);
	
	// Get the user
	if( count($files) != 1 ){
		OC_Log::write('migration', 'Invalid import file', OC_Log::ERROR);
		die('invalid import');	
	}
	
	$user = reset($files);
	
	// Check for dbexport.xml and export info and data dir
	$files = scandir( $importdir . '/' . $user );
	$required = array( 'migration.db', 'exportinfo.json', 'files');
	foreach($required as $require){
		if( !in_array( $require, $files) ){
			OC_Log::write('migration', 'Invlaid import file', OC_Log::ERROR);
			die('invalid import');	
		}	
	}
	
	$migrateinfo = $importdir . '/' . $user . '/exportinfo.json';
	$migrateinfo = json_decode( file_get_contents( $migrateinfo ) );
	$olduid = $migrateinfo->migrateinfo->uid;
	
	// Check if uid is available
	if( OC_User::UserExists( $olduid ) ){
		OC_Log::write('migration','Username exists', OC_Log::ERROR);	
		die('user exists');
	}
	
	// Create the user
	if( !OC_Migrate::createUser( $olduid, $migrateinfo->migrateinfo->hash ) ){
		OC_Log::write('migration', 'Failed to create the new user', OC_Log::ERROR);
		die('coundlt create new user');
	}
	
	$datadir = OC_Config::getValue( 'datadirectory' );
	// Copy data
	if( !copy_r( $importdir . '/files', $datadir . '/' ) ){
		OC_Log::write('migration','Failed to copy user files to destination', OC_Log::ERROR);
		die('failed to copy user files');	
	}
	
	// Import user data
	if( !OC_Migrate::importUser( $importdir . '/migration.db', $migrateinfo ) ){
		OC_Log::write('migration','Failed to import user data', OC_Log::ERROR);
		die('failed to import user data');	
	}
	
	// All done!
	die('done');
		
} else {
// fill template
    $tmpl = new OC_Template('user_migrate', 'admin');
    return $tmpl->fetchPage();
}
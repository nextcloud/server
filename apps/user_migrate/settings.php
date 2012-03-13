<?php

/**
 * ownCloud - user_migrate
 *
 * @author Thomas Schmidt
 * @copyright 2011 Thomas Schmidt tom@opensuse.org
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
OC_Util::checkAppEnabled('user_migrate');

define('DS', '/');

if (isset($_POST['user_export'])) {
	
	// Setup the export
    $zip = new ZipArchive();
    $tmp = get_temp_dir();
    $user = OC_User::getUser();
    // Create owncoud dir
    if( !file_exists( $tmp . '/owncloud' ) ){
    	if( !mkdir( $tmp . '/owncloud' ) ){
    		die('Failed to create the owncloud tmp directory');	
    	}	
    }
    // Create the export dir
    $exportdir = $tmp . '/owncloud' . '/export_' . $user . '_' . date("y-m-d_H-i-s");
    if( !file_exists( $exportdir ) ){
    	if( !mkdir( $exportdir ) ){
    		die('Failed to create the owncloud export directory');	
    	}	
    }
	$filename = $exportdir . '/owncloud_export_' . $user . '_' . date("y-m-d_H-i-s") . ".zip";
    OC_Log::write('user_migrate',"Creating export file at: " . $filename,OC_Log::INFO);
    if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
		exit("Cannot open <$filename>\n");
    }
	
	// Migrate the app info
	$info = json_encode( OC_Migrate::export( $user ) );
	$infofile = $exportdir . '/exportinfo.json';
	if( !file_put_contents( $infofile, $info ) ){
		die('Failed to save the export info');	
	}
	$zip->addFile( $infofile, "exportinfo.json");
	$zip->addFile(OC::$SERVERROOT . '/data/' . $user . '/migration.db', "migration.db");

	// Add the data dir
	zipAddDir(OC::$SERVERROOT . "/data/" . $user, $zip, true, "files/");
	
	// Save the zip
    $zip->close();
    
    // Send the zip
    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=" . basename($filename));
    header("Content-Length: " . filesize($filename));
    @ob_end_clean();
    readfile($filename);
    // Cleanup
    unlink($filename);
    unlink($infofile);
    rmdir($exportdir);
    
} if( isset( $_POST['user_import'] ) ){
	// TODO
}else {
	
	// fill template
    $tmpl = new OC_Template('user_migrate', 'settings');
    return $tmpl->fetchPage();
    
}

function zipAddDir($dir, $zip, $recursive=true, $internalDir='') {
    $dirname = basename($dir);
    $zip->addEmptyDir($internalDir . $dirname);
    $internalDir.=$dirname.='/';

    if ($dirhandle = opendir($dir)) {
		while (false !== ( $file = readdir($dirhandle))) {

			if (( $file != '.' ) && ( $file != '..' )) {

			if (is_dir($dir . '/' . $file) && $recursive) {
				zipAddDir($dir . '/' . $file, $zip, $recursive, $internalDir);
			} elseif (is_file($dir . '/' . $file)) {
				$zip->addFile($dir . '/' . $file, $internalDir . $file);
			}
			}
		}
		closedir($dirhandle);
    } else {
		OC_Log::write('user_migrate',"Was not able to open directory: " . $dir,OC_Log::ERROR);
    }
}

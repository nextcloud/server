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
OC_Util::checkAppEnabled('user_migrate');


if (isset($_POST['user_migrate'])) {
	// Looks like they want to migrate
	$errors = array();
    $root = OC::$SERVERROOT . "/";
    $user = OC_User::getUser();
    $zip = new ZipArchive();
    $tempdir = get_temp_dir();
    $filename = $tempdir . "/" . $user . "_export_" . date("y-m-d_H-i-s") . ".zip";
    OC_Log::write('user_migrate',"Creating user export file at: " . $filename,OC_Log::INFO);
    if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
		exit("Cannot open <$filename>\n");
    }

	// Does the user want to include their files?
    if (isset($_POST['user_files'])) {
	    // needs to handle data outside of the default data dir.
		// adding user files
		OC_Log::write('user_migrate',"Adding owncloud user files of $user to export",OC_Log::INFO);
		zipAddDir($root . "data/" . $user, $zip, true, "files/");
    }
    
    // Does the user want their app data?
    if (isset($_POST['user_appdata'])) {
		// adding owncloud system files
		OC_Log::write('user_migrate',"Adding app data to user export",OC_Log::INFO);
		// Call to OC_Migrate for the xml file.
		//$appdatafile = $tempdir . "/appdata.xml";
		//$fh = fopen($appdatafile, 'w');
		$appdata = OC_Migrate::export(OC_User::getUser());
		//fwrite($fh, $appdata);
		//$zip->addFile($appdatafile, "appdata.xml");
		//fclose($fh);
    }

    $zip->close();

    //header("Content-Type: application/zip");
    //header("Content-Disposition: attachment; filename=" . basename($filename));
    //header("Content-Length: " . filesize($filename));
    //@ob_end_clean();
    echo $appdata;
    //readfile($filename);
    unlink($filename);
} else {
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
		OC_Log::write('admin_export',"Was not able to open directory: " . $dir,OC_Log::ERROR);
    }
}

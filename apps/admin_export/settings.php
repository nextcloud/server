<?php

/**
 * ownCloud - admin export
 *
 * @author Thomas Schmidt
 * @copyright 2011 Thomas Schmidt tom@opensuse.org
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
OC_Util::checkAppEnabled('admin_export');
if (isset($_POST['admin_export'])) {
    $root = OC::$SERVERROOT . "/";
    $zip = new ZipArchive();
    $filename = get_temp_dir() . "/owncloud_export_" . date("y-m-d_H-i-s") . ".zip";
    OC_Log::write('admin_export',"Creating export file at: " . $filename,OC_Log::INFO);
    if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
	exit("Cannot open <$filename>\n");
    }

    if (isset($_POST['owncloud_system'])) {
	// adding owncloud system files
	OC_Log::write('admin_export',"Adding owncloud system files to export",OC_Log::INFO);
	zipAddDir($root, $zip, false);
	foreach (array(".git", "3rdparty", "apps", "core", "files", "l10n", "lib", "ocs", "search", "settings", "tests") as $dirname) {
	    zipAddDir($root . $dirname, $zip, true, basename($root) . "/");
	}
    }

    if (isset($_POST['owncloud_config'])) {
	// adding owncloud config
	// todo: add database export
	OC_Log::write('admin_export',"Adding owncloud config to export",OC_Log::INFO);
	zipAddDir($root . "config/", $zip, true, basename($root) . "/");
	$zip->addFile($root . '/data/.htaccess', basename($root) . "/data/owncloud.db");
    }

    if (isset($_POST['user_files'])) {
	// adding user files
	$zip->addFile($root . '/data/.htaccess', basename($root) . "/data/.htaccess");
	$zip->addFile($root . '/data/index.html', basename($root) . "/data/index.html");
	foreach (OC_User::getUsers() as $i) {
		OC_Log::write('admin_export',"Adding owncloud user files of $i to export",OC_Log::INFO);
	    zipAddDir($root . "data/" . $i, $zip, true, basename($root) . "/data/");
	}
    }

    $zip->close();

    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=" . basename($filename));
    header("Content-Length: " . filesize($filename));
    @ob_end_clean();
    readfile($filename);
    unlink($filename);
} else {
// fill template
    $tmpl = new OC_Template('admin_export', 'settings');
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

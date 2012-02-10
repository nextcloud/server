<?php

/**
 * ownCloud - admin export
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
OC_Util::checkAdminUser();
OC_Util::checkAppEnabled('admin_export');

define('DS', '/');



if (isset($_POST['admin_export'])) {
    $root = OC::$SERVERROOT . "/";
    $zip = new ZipArchive();
    $tempdir = get_temp_dir();
    $filename = $tempdir . "/owncloud_export_" . date("y-m-d_H-i-s") . ".zip";
    OC_Log::write('admin_export',"Creating export file at: " . $filename,OC_Log::INFO);
    if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
		exit("Cannot open <$filename>\n");
    }

    if (isset($_POST['owncloud_system'])) {
		// adding owncloud system files
		OC_Log::write('admin_export',"Adding owncloud system files to export",OC_Log::INFO);
		zipAddDir($root, $zip, false);
		foreach (array(".git", "3rdparty", "apps", "core", "files", "l10n", "lib", "ocs", "search", "settings", "tests") as $dirname) {
		    zipAddDir($root . $dirname, $zip, true, "/");
		}
    }

    if (isset($_POST['owncloud_config'])) {
	// adding owncloud config
	// todo: add database export
	$dbfile = $tempdir . "/dbexport.xml";
	OC_DB::getDbStructure( $dbfile, 'MDB2_SCHEMA_DUMP_ALL');
	
	// Now add in *dbname* and *dbtableprefix*
	$dbexport = file_get_contents( $dbfile );
	
	$dbnamestring = "<database>\n\n <name>" . OC_Config::getValue( "dbname", "owncloud" );
	$dbtableprefixstring = "<table>\n\n  <name>" . OC_Config::getValue( "dbtableprefix", "_oc" );
	
	$dbexport = str_replace( $dbnamestring, "<database>\n\n <name>*dbname*", $dbexport );
	$dbexport = str_replace( $dbtableprefixstring, "<table>\n\n  <name>*dbtableprefix*", $dbexport );
	
	// Write the new db export file
	file_put_contents( $dbfile, $dbexport );
	
	$zip->addFile($dbfile, "dbexport.xml");

	OC_Log::write('admin_export',"Adding owncloud config to export",OC_Log::INFO);
	zipAddDir($root . "config/", $zip, true, "/");
	$zip->addFile($root . '/data/.htaccess', "data/owncloud.db");
    }

    if (isset($_POST['user_files'])) {
    // needs to handle data outside of the default data dir.
	// adding user files
	$zip->addFile($root . '/data/.htaccess', "data/.htaccess");
	$zip->addFile($root . '/data/index.html', "data/index.html");
	foreach (OC_User::getUsers() as $i) {
		OC_Log::write('admin_export',"Adding owncloud user files of $i to export",OC_Log::INFO);
	    zipAddDir($root . "data/" . $i, $zip, true, "/data/");
	}
    }
    $zip->close();
    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=" . basename($filename));
    header("Content-Length: " . filesize($filename));
    @ob_end_clean();
    readfile($filename);
    unlink($filename);
    unlink($dbfile);
} else if( isset($_POST['admin_import']) ){
	
	$root = OC::$SERVERROOT . "/";
	$importname = "owncloud_import_" . date("y-m-d_H-i-s");
	
	// Save data dir for later
	$datadir = OC_Config::getValue( 'datadirectory' );
	
	// Copy the uploaded file
	$from = $_FILES['owncloud_import']['tmp_name'];
	$to = get_temp_dir().'/'.$importname.'.zip';
	if( !move_uploaded_file( $from, $to ) ){
		OC_Log::write('admin_export',"Failed to copy the uploaded file",OC_Log::INFO);
		exit();		
	}
	
	// Extract zip
	$zip = new ZipArchive();
	if ($zip->open(get_temp_dir().'/'.$importname.'.zip') != TRUE) {
		OC_Log::write('admin_export',"Failed to open zip file",OC_Log::INFO);
		exit();
	}
	$zip->extractTo(get_temp_dir().'/'.$importname.'/');
	$zip->close();
	
	// Delete uploaded file
	unlink( get_temp_dir() . '/' . $importname . '.zip' );
	
	// Now we need to check if everything is present. Data and dbexport.xml
	
	
	// Delete current data folder.
	OC_Log::write('admin_export',"Deleting current data dir",OC_Log::INFO);
	unlinkRecursive( $datadir, false );
	
	// Copy over data
	if( !copy_r( get_temp_dir() . '/' . $importname . '/data', $datadir ) ){
		OC_Log::write('admin_export',"Failed to copy over data directory",OC_Log::INFO);
		exit();	
	}
	
	// TODO: Import db		
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

function unlinkRecursive($dir, $deleteRootToo) 
{ 
    if(!$dh = @opendir($dir)) 
    { 
        return; 
    } 
    while (false !== ($obj = readdir($dh))) 
    { 
        if($obj == '.' || $obj == '..') 
        { 
            continue; 
        } 

        if (!@unlink($dir . '/' . $obj)) 
        { 
            unlinkRecursive($dir.'/'.$obj, true); 
        } 
    } 

    closedir($dh); 
    
    if ($deleteRootToo) 
    { 
        @rmdir($dir); 
    } 
    
    return; 
} 
 

    function copy_r( $path, $dest )
    {
        if( is_dir($path) )
        {
            @mkdir( $dest );
            $objects = scandir($path);
            if( sizeof($objects) > 0 )
            {
                foreach( $objects as $file )
                {
                    if( $file == "." || $file == ".." )
                        continue;
                    // go on
                    if( is_dir( $path.DS.$file ) )
                    {
                        copy_r( $path.DS.$file, $dest.DS.$file );
                    }
                    else
                    {
                        copy( $path.DS.$file, $dest.DS.$file );
                    }
                }
            }
            return true;
        }
        elseif( is_file($path) )
        {
            return copy($path, $dest);
        }
        else
        {
            return false;
        }
    }

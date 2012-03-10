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

// Get paramteres
$filecontents = $_POST['filecontents'];
$path = isset($_POST['path']) ? $_POST['path'] : '';
$mtime = isset($_POST['mtime']) ? $_POST['mtime'] : '';


if($path != '' && $mtime != '')
{
	// Get file mtime
	$filemtime = OC_Filesystem::filemtime($path);
	if($mtime != $filemtime)
	{
		// Then the file has changed since opening
		OC_JSON::error();
		OC_Log::write('files_texteditor',"File: ".$path." modified since opening.",OC_Log::ERROR);	
	}
	else
	{
		// File same as when opened
		// Save file
		if(OC_Filesystem::is_writeable($path))	
		{
			OC_Filesystem::file_put_contents($path, $filecontents);
			// Clear statcache
			clearstatcache();
			// Get new mtime
			$newmtime = OC_Filesystem::filemtime($path);
			OC_JSON::success(array('data' => array('mtime' => $newmtime)));
		}
		else
		{
			// Not writeable!
			OC_JSON::error(array('data' => array( 'message' => 'Insufficient permissions')));	
			OC_Log::write('files_texteditor',"User does not have permission to write to file: ".$path,OC_Log::ERROR);
		}
	}
} else {
	OC_JSON::error(array('data' => array( 'message' => 'File path or mtime not supplied')));
	OC_Log::write('files_texteditor',"Invalid path supplied:".$path,OC_Log::ERROR);	
}

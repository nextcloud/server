<?php
/**
 * ownCloud - Editor
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
require_once('../../lib/base.php');

// Check if we are a user
OC_Util::checkLoggedIn();

$filecontents = htmlspecialchars(OC_Filesystem::file_get_contents($_GET['dir'].'/'.$_GET['file'] ));
$filehash = md5($filecontents);
$file = $_GET['file'];
$dir = $_GET['dir'];
$path = $dir.'/'.$file;

// Add scripts
OC_UTIL::addStyle('editor', 'styles');
OC_UTIL::addScript('editor','editor');
OC_UTIL::addScript('editor','aceeditor/ace');

// Get file type
if(substr_count($file,'.')!=0){
	// Find extension
	$parts = explode(".",$file);
	$plaintypes = array('txt','doc','rtf');
	$filetype = 'plain';
	if(!in_array($parts[1],$plaintypes)){
		// TODO ADD THESE
		$types = array('php' => 'php',
						'js' => 'javascript',
						'html' => 'html',
						'css' => 'css',
						'pl' => 'perl',
						'py' => 'python',
						'rb' => 'ruby',
						'xml' => 'xml',
						'svg' => 'svg');
		$filetype = $types[$parts[1]];
		OC_UTIL::addScript('editor','aceeditor/mode-'.$filetype);		
	}
} else {
	// Treat as plain text
	$filetype = 'plain';
		
}

// Add theme
OC_UTIL::addScript('editor','aceeditor/theme-clouds');

OC_App::setActiveNavigationEntry( 'editor_index' );

// Save a hash of the file for later
$sessionname = md5('oc_file_hash_'.$path);
$_SESSION[$sessionname] = $filehash;

// Process the template
$tmpl = new OC_Template( 'editor', 'index', 'user' );
$tmpl->assign('filetype',$filetype);
$tmpl->assign('filecontents', $filecontents);
$tmpl->assign('file',$file);
$tmpl->assign('dir',$dir);
$tmpl->printPage();

<?php

/**
* ownCloud - ajax frontend
*
* @author Robin Appelman
* @copyright 2010 Robin Appelman icewind1991@gmail.com
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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/
require_once('../inc/lib_base.php');

$arguments=$_POST;
if(!isset($_POST['action']) and isset($_GET['action'])){
	$arguments=$_GET;
}

foreach($arguments as &$argument){
	$argument=stripslashes($argument);
}
global $CONFIG_DATADIRECTORY;
ob_clean();
if($arguments['action']){
	switch($arguments['action']){
		case 'delete':
			echo (OC_FILES::delete($arguments['dir'],$arguments['file']))?'true':'false';
			break;
		case 'rename':
			echo (OC_FILES::move($arguments['dir'],$arguments['file'],$arguments['dir'],$arguments['newname']))?'true':'false';
			break;
		case 'new':
			echo (OC_FILES::newfile($arguments['dir'],$arguments['name'],$arguments['type']))?'true':'false';
			break;
		case 'move':
			echo (OC_FILES::move($arguments['sourcedir'],$arguments['source'],$arguments['targetdir'],$arguments['target']))?'true':'false';
			break;
		case 'copy':
			echo (OC_FILES::copy($arguments['sourcedir'],$arguments['source'],$arguments['targetdir'],$arguments['target']))?'true':'false';
			break;
		case 'get':
			OC_FILES::get($arguments['dir'],$arguments['file']);
			break;
		case 'getfiles':
			$max_upload=min(return_bytes(ini_get('post_max_size')),return_bytes(ini_get('upload_max_filesize')));
			$files=OC_FILES::getdirectorycontent($arguments['dir']);
			$files['__max_upload']=$max_upload;
			echo json_encode($files);
			break;
		case 'gettree':
			echo json_encode(OC_FILES::getTree($arguments['dir']));
			break;
		case 'find':
			echo json_encode(OC_FILESYSTEM::find($arguments['path']));
			break;
		case 'login':
			if(OC_USER::login($arguments['username'],$arguments['password'])){
				echo 'true';
			}else{
				echo 'false';
			}
			break;
		case 'checklogin':
			if(OC_USER::isLoggedIn()){
				echo 'true';
			}else{
				echo 'false';
			}
			break;
		case 'pull':
			return OC_FILES::pull($arguments['source'],$arguments['token'],$arguments['dir'],$arguments['file']);
	}
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}
?>

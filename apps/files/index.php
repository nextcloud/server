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
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

// Check if we are a user
OCP\User::checkLoggedIn();

// Load the files we need
OCP\Util::addStyle( 'files', 'files' );
OCP\Util::addscript( 'files', 'jquery.iframe-transport' );
OCP\Util::addscript( 'files', 'jquery.fileupload' );
OCP\Util::addscript( 'files', 'files' );
OCP\Util::addscript( 'files', 'filelist' );
OCP\Util::addscript( 'files', 'fileactions' );
if(!isset($_SESSION['timezone'])){
	OCP\Util::addscript( 'files', 'timezone' );
}
OCP\App::setActiveNavigationEntry( 'files_index' );
// Load the files
$dir = isset( $_GET['dir'] ) ? stripslashes($_GET['dir']) : '';
// Redirect if directory does not exist
if(!OC_Filesystem::is_dir($dir.'/')) {
	header('Location: '.$_SERVER['PHP_SELF'].'');
}

$files = array();
foreach( OC_Files::getdirectorycontent( $dir ) as $i ){
	$i['date'] = OCP\Util::formatDate($i['mtime'] );
	if($i['type']=='file'){
		$fileinfo=pathinfo($i['name']);
		$i['basename']=$fileinfo['filename'];
		if (!empty($fileinfo['extension'])) {
			$i['extension']='.' . $fileinfo['extension'];
		}
		else {
			$i['extension']='';
		}
	}
	if($i['directory']=='/'){
		$i['directory']='';
	}
	$files[] = $i;
}

// Make breadcrumb
$breadcrumb = array();
$pathtohere = '';
foreach( explode( '/', $dir ) as $i ){
	if( $i != '' ){
		$pathtohere .= '/'.str_replace('+','%20', urlencode($i));
		$breadcrumb[] = array( 'dir' => $pathtohere, 'name' => $i );
	}
}

// make breadcrumb und filelist markup
$list = new OCP\Template( 'files', 'part.list', '' );
$list->assign( 'files', $files );
$list->assign( 'baseURL', OCP\Util::linkTo('files', 'index.php').'&dir=');
$list->assign( 'downloadURL', OCP\Util::linkTo('files', 'download.php').'?file=');
$breadcrumbNav = new OCP\Template( 'files', 'part.breadcrumb', '' );
$breadcrumbNav->assign( 'breadcrumb', $breadcrumb );
$breadcrumbNav->assign( 'baseURL', OCP\Util::linkTo('files', 'index.php').'&dir=');

$upload_max_filesize = OCP\Util::computerFileSize(ini_get('upload_max_filesize'));
$post_max_size = OCP\Util::computerFileSize(ini_get('post_max_size'));
$maxUploadFilesize = min($upload_max_filesize, $post_max_size);

$freeSpace=OC_Filesystem::free_space('/');
$freeSpace=max($freeSpace,0);
$maxUploadFilesize = min($maxUploadFilesize ,$freeSpace);

$tmpl = new OCP\Template( 'files', 'index', 'user' );
$tmpl->assign( 'fileList', $list->fetchPage() );
$tmpl->assign( 'breadcrumb', $breadcrumbNav->fetchPage() );
$tmpl->assign( 'dir', $dir);
$tmpl->assign( 'readonly', !OC_Filesystem::is_writable($dir.'/'));
$tmpl->assign( 'files', $files );
$tmpl->assign( 'uploadMaxFilesize', $maxUploadFilesize);
$tmpl->assign( 'uploadMaxHumanFilesize', OCP\Util::humanFileSize($maxUploadFilesize));
$tmpl->assign( 'allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));
$tmpl->printPage();

?>

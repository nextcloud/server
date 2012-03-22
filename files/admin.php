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


// Init owncloud
require_once('../lib/base.php');

OC_Util::checkAdminUser();

$htaccessWorking=(getenv('htaccessWorking')=='true');
if($_POST) {
	if(isset($_POST['maxUploadSize'])){
		$maxUploadFilesize=$_POST['maxUploadSize'];
		OC_Files::setUploadLimit(OC_Helper::computerFileSize($maxUploadFilesize));
	}
	if(isset($_POST['maxZipInputSize'])) {
		$maxZipInputSize=$_POST['maxZipInputSize'];
		OC_Config::setValue('maxZipInputSize', OC_Helper::computerFileSize($maxZipInputSize));
	}
	OC_Config::setValue('allowZipDownload', isset($_POST['allowZipDownload']));
}else{
	$upload_max_filesize = OC_Helper::computerFileSize(ini_get('upload_max_filesize'));
	$post_max_size = OC_Helper::computerFileSize(ini_get('post_max_size'));
	$maxUploadFilesize = min($upload_max_filesize, $post_max_size);
	$maxZipInputSize = OC_Helper::humanfilesize(OC_Config::getValue('maxZipInputSize', OC_Helper::computerFileSize('800 MB')));
}
$allowZipDownload = intval(OC_Config::getValue('allowZipDownload', true));

OC_App::setActiveNavigationEntry( "files_administration" );

$tmpl = new OC_Template( 'files', 'admin' );
$tmpl->assign( 'htaccessWorking', $htaccessWorking );
$tmpl->assign( 'uploadMaxFilesize', $maxUploadFilesize);
$tmpl->assign( 'allowZipDownload', $allowZipDownload);
$tmpl->assign( 'maxZipInputSize', $maxZipInputSize);
return $tmpl->fetchPage();
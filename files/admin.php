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


// Check if we are a user
if( !OC_User::isLoggedIn() || !OC_Group::inGroup( OC_User::getUser(), 'admin' )){
	header( "Location: ".OC_Helper::linkTo( "index.php" ));
	exit();
}

$htaccessWorking=(getenv('htaccessWorking')=='true');
if(isset($_POST['maxUploadSize'])){
	$maxUploadFilesize=$_POST['maxUploadSize'];
	OC_Files::setUploadLimit(OC_Helper::computerFileSize($maxUploadFilesize));
}else{
	$maxUploadFilesize = ini_get('upload_max_filesize').'B';
}

OC_App::setActiveNavigationEntry( "files_administration" );
// return template
$tmpl = new OC_Template( "files", "admin", "user" );
$tmpl->assign( 'htaccessWorking', $htaccessWorking );
$tmpl->assign( 'uploadMaxFilesize', $maxUploadFilesize);
$tmpl->printPage();

?>

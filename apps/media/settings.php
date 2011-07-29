<?php

/**
* ownCloud - media plugin
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


require_once('../../lib/base.php');

if( !OC_User::isLoggedIn()){
	header( "Location: ".OC_Helper::linkTo( "index.php" ));
	exit();
}

require( 'lib_collection.php' );

OC_Util::addStyle('media','style');
OC_Util::addScript('media','settings');

OC_App::setActiveNavigationEntry( 'media_settings' );

$folderNames=explode(PATH_SEPARATOR,OC_Preferences::getValue($_SESSION['user_id'],'media','paths',''));
$folders=array();
foreach($folderNames as $folder){
	if($folder){
		$folders[]=array('name'=>$folder,'songs'=>OC_MEDIA_COLLECTION::getSongCountByPath($folder));
	}
}

$tmpl = new OC_Template( 'media', 'settings', 'admin' );
$tmpl->assign('folders',$folders);
$tmpl->assign('autoupdate',OC_Preferences::getValue($_SESSION['user_id'],'media','autoupdate',false));
$tmpl->printPage();
?>


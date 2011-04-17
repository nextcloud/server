<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
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

require_once( 'lib/base.php' );
require_once( 'appconfig.php' );
require_once( 'template.php' );


// check if the server is correctly configured for ownCloud
$errors=OC_UTIL::checkServer();
if(count($errors)>0){
	OC_TEMPLATE::printGuestPage( "", "error", array( "errors" => $errors ));
}elseif(isset($_POST['install']) and $_POST['install']=='true'){
	require_once 'installer.php';
}elseif (!OC_CONFIG::getValue('installed',false)) {
	$hasSQLite=is_callable('sqlite_open');
	$hasMySQL=is_callable('mysql_connect');
	$datadir=OC_CONFIG::getValue('datadir',$SERVERROOT.'/data');
	OC_TEMPLATE::printGuestPage( "", "installation",array('hasSQLite'=>$hasSQLite,'hasMySQL'=>$hasMySQL,'datadir'=>$datadir));
}elseif( OC_USER::isLoggedIn()){
	if( isset($_GET["logout"]) and ($_GET["logout"]) ){
		OC_USER::logout();
		header( "Location: $WEBROOT");
		exit();
	}
	else{
		header( "Location: ".$WEBROOT.'/'.OC_APPCONFIG::getValue( "core", "defaultpage", "files/index.php" ));
		exit();
	}
}elseif(isset($_POST["user"])){
	if( OC_USER::login( $_POST["user"], $_POST["password"] )){
		header( "Location: ".$WEBROOT.'/'.OC_APPCONFIG::getValue( "core", "defaultpage", "files/index.php" ));
		exit();
	}else{
		OC_TEMPLATE::printGuestPage( "", "login", array( "error" => true));
	}
}else{
	OC_TEMPLATE::printGuestPage( "", "login", array( "error" => false ));
}

?>

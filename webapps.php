<?php

/**
* ownCloud status page. usefull if you want to check from the outside if an owncloud installation exists
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

$RUNTIME_NOAPPS = TRUE; //no apps, yet

require_once('lib/base.php');


//valid user account 
if(isset($_SERVER['PHP_AUTH_USER'])) $authuser=$_SERVER['PHP_AUTH_USER']; else $authuser='';
if(isset($_SERVER['PHP_AUTH_PW']))   $authpw=$_SERVER['PHP_AUTH_PW']; else $authpw='';

if(!OC_User::login($authuser,$authpw)){
	header('WWW-Authenticate: Basic realm="your valid user account"');
	header('HTTP/1.0 401 Unauthorized');
	exit;
}else{

	$apps=OC_App::getEnabledApps();
	$values=array();
	foreach($apps as $app) {
		$info=OC_App::getAppInfo($app);
		if(isset($info['standalone'])) {
			$newvalue=array('name'=>$info['name'],'url'=>OC_Helper::linkToAbsolute($app,''),'icon'=>'');
			$values[]=$newvalue;
		}

	}

	echo(json_encode($values));
	exit;


}

?>

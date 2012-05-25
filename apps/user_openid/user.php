<?php

/**
* ownCloud
*
* @author Robin Appelman
* @copyright 2011 Robin Appelman icewind1991@gmail.com
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

$USERNAME=substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'.php/')+5);
if(strpos($USERNAME,'?')!==false){
	$USERNAME=substr($USERNAME,0,strpos($USERNAME,'?'));
}
if(substr($USERNAME,-1,1)=='/'){//openid sometimes add slashes to the username
	$USERNAME=substr($USERNAME,0,-1);
}


if($USERNAME=='' and isset($_SERVER['PHP_AUTH_USER'])){
	$USERNAME=$_SERVER['PHP_AUTH_USER'];
}

$RUNTIME_NOAPPS=true;
$RUNTIME_NOAPPS=false;
OCP\App::checkAppEnabled('user_openid');

if(!OCP\User::userExists($USERNAME)){
	OCP\Util::writeLog('user_openid',$USERNAME.' doesn\'t exist',OCP\Util::WARN);
	$USERNAME='';
}
$IDENTITY=OCP\Util::linkToAbsolute( "user_openid", "user.php" ).'/'.$USERNAME;

require_once 'phpmyid.php';


?>

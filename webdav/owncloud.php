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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/


require_once('../inc/lib_base.php');
oc_require_once('HTTP/WebDAV/Server/Filesystem.php');


ini_set('default_charset', 'UTF-8');
#ini_set('error_reporting', '');
ob_clean();

if(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['REDIRECT_REMOTE_USER'])) {
  header('WWW-Authenticate: Basic realm="ownCloud"');
  header('HTTP/1.0 401 Unauthorized');
  die('401 Unauthorized');
}

$user=$_SERVER['PHP_AUTH_USER'];
$passwd=$_SERVER['PHP_AUTH_PW'];
if(OC_USER::login($user,$passwd)){
	$CONFIG_DATADIRECTORY=$CONFIG_DATADIRECTORY_ROOT.'/'.$user;
	if(!is_dir($CONFIG_DATADIRECTORY)){
		mkdir($CONFIG_DATADIRECTORY);
	}
	$rootStorage=new OC_FILESTORAGE_LOCAL(array('datadir'=>$CONFIG_DATADIRECTORY));
	if($CONFIG_ENABLEBACKUP){
		if(!is_dir($CONFIG_BACKUPDIRECTORY)){
			mkdir($CONFIG_BACKUPDIRECTORY);
		}
		if(!is_dir($CONFIG_BACKUPDIRECTORY.'/'.$user)){
			mkdir($CONFIG_BACKUPDIRECTORY.'/'.$user);
		}
		$backupStorage=new OC_FILESTORAGE_LOCAL(array('datadir'=>$CONFIG_BACKUPDIRECTORY.'/'.$user));
		$backup=new OC_FILEOBSERVER_BACKUP(array('storage'=>$backupStorage));
		$rootStorage->addObserver($backup);
	}
	OC_FILESYSTEM::mount($rootStorage,'/');
	$server = new HTTP_WebDAV_Server_Filesystem();
	$server->db_name = $CONFIG_DBNAME;
	$server->ServeRequest($CONFIG_DATADIRECTORY);
	
}else{
  header('WWW-Authenticate: Basic realm="ownCloud"');
  header('HTTP/1.0 401 Unauthorized');
  die('401 Unauthorized');
}



?>
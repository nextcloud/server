<?php

/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2010 Frank Karlitschek karlitschek@kde.org
 * @copyright 2011 Jakob Sack kde@jakobsack.de
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

// Do not load FS ...
$RUNTIME_NOSETUPFS = true;

require_once('../lib/base.php');
require_once('Sabre/autoload.php');
require_once('Sabre/DAV/FS/OwncloudNode.php');
require_once('Sabre/DAV/FS/OwncloudFile.php');
require_once('Sabre/DAV/FS/OwncloudDirectory.php');

ini_set('default_charset', 'UTF-8');
#ini_set('error_reporting', '');
@ob_clean();

if(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['REDIRECT_REMOTE_USER'])) {
	header('WWW-Authenticate: Basic realm="ownCloud"');
	header('HTTP/1.0 401 Unauthorized');
	die('401 Unauthorized');
}

$user=$_SERVER['PHP_AUTH_USER'];
$passwd=$_SERVER['PHP_AUTH_PW'];

if(OC_USER::login($user,$passwd)){
	OC_UTIL::setUpFS();
	
	// Make sure there is a directory in your current directory named 'public'. We will be exposing that directory to WebDAV
	$publicDir = new OC_Sabre_DAV_FS_OwncloudDirectory('');
	$server = new Sabre_DAV_Server($publicDir);

	// We're required to set the base uri, it is recommended to put your webdav server on a root of a domain
	$server->setBaseUri($WEBROOT.'/files/webdav.php');

	// And off we go!
	$server->exec();
}
else{
	header('WWW-Authenticate: Basic realm="ownCloud"');
	header('HTTP/1.0 401 Unauthorized');
	die('401 Unauthorized');
}

?>

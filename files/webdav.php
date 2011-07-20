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
require_once('Sabre/DAV/Auth/Backend/Owncloud.php');
require_once('Sabre/DAV/FS/OwncloudNode.php');
require_once('Sabre/DAV/FS/OwncloudFile.php');
require_once('Sabre/DAV/FS/OwncloudDirectory.php');

// Create ownCloud Dir
$publicDir = new OC_Sabre_DAV_FS_OwncloudDirectory('');
$server = new Sabre_DAV_Server($publicDir);

// Path to our script
$server->setBaseUri($WEBROOT.'/files/webdav.php');

// Auth backend
$authBackend = new OC_Sabre_DAV_Auth_Backend_Owncloud();
$authPlugin = new Sabre_DAV_Auth_Plugin($authBackend,'ownCloud');
$server->addPlugin($authPlugin);

// And off we go!
$server->exec();

?>

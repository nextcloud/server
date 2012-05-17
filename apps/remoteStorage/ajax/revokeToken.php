<?php

/**
* ownCloud
*
* Original:
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
* 
* Adapted:
* @author Michiel de Jong, 2012
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
 
OCP\App::checkAppEnabled('remoteStorage');
require_once('remoteStorage/lib_remoteStorage.php');

ini_set('default_charset', 'UTF-8');
#ini_set('error_reporting', '');
@ob_clean();

echo OC_remoteStorage::deleteToken(file_get_contents("php://input"));

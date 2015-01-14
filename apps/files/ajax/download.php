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

// Check if we are a user
OCP\User::checkLoggedIn();
\OC::$server->getSession()->close();

$files = isset($_GET['files']) ? $_GET['files'] : '';
$dir = isset($_GET['dir']) ? $_GET['dir'] : '';

$files_list = json_decode($files);
// in case we get only a single file
if (!is_array($files_list)) {
	$files_list = array($files);
}

OC_Files::get($dir, $files_list, $_SERVER['REQUEST_METHOD'] == 'HEAD');

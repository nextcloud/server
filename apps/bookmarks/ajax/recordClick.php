<?php

/**
* ownCloud - bookmarks plugin
*
* @author Arthur Schiwon
* @copyright 2011 Arthur Schiwon blizzz@arthur-schiwon.de
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

//no apps or filesystem
$RUNTIME_NOSETUPFS=true;

require_once('../../../lib/base.php');

// Check if we are a user
OC_JSON::checkLoggedIn();

$query = OC_DB::prepare("
	UPDATE *PREFIX*bookmarks
	SET clickcount = clickcount + 1
	WHERE user_id = ?
		AND url LIKE ?
	");
	
$params=array(OC_User::getUser(), htmlspecialchars_decode($_GET["url"]));
$bookmarks = $query->execute($params);

header( "HTTP/1.1 204 No Content" );


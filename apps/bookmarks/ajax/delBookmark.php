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

 

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('bookmarks');

$params=array(
	htmlspecialchars_decode($_GET["url"]),
	OC_User::getUser()
	);

$query = OC_DB::prepare("
	SELECT id FROM *PREFIX*bookmarks 
	WHERE url LIKE ?
		AND user_id = ?
	");

$id = $query->execute($params)->fetchOne();

$query = OC_DB::prepare("
	DELETE FROM *PREFIX*bookmarks
	WHERE id = $id
	");
	
$result = $query->execute();


$query = OC_DB::prepare("
	DELETE FROM *PREFIX*bookmarks_tags
	WHERE bookmark_id = $id
	");
	
$result = $query->execute();
// var_dump($params);

OC_JSON::success(array('data' => array()));

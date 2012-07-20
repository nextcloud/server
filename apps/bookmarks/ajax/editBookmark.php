<?php

/**
* ownCloud - bookmarks plugin - edit bookmark script
*
* @author Golnaz Nilieh
* @copyright 2011 Golnaz Nilieh <golnaz.nilieh@gmail.com>
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
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('bookmarks');
OCP\JSON::callCheck();

$CONFIG_DBTYPE = OCP\Config::getSystemValue( "dbtype", "sqlite" );
if( $CONFIG_DBTYPE == 'sqlite' or $CONFIG_DBTYPE == 'sqlite3' ){
	$_ut = "strftime('%s','now')";
} elseif($CONFIG_DBTYPE == 'pgsql') {
	$_ut = 'date_part(\'epoch\',now())::integer';
} else {
	$_ut = "UNIX_TIMESTAMP()";
}

$bookmark_id = (int)$_POST["id"];
$user_id = OCP\USER::getUser();

$query = OCP\DB::prepare("
	UPDATE *PREFIX*bookmarks
	SET url = ?, title =?, lastmodified = $_ut
	WHERE id = ?
	AND user_id = ?
	");

$params=array(
	htmlspecialchars_decode($_POST["url"]),
	htmlspecialchars_decode($_POST["title"]),
	$bookmark_id,
	$user_id,
	);

$result = $query->execute($params);

# Abort the operation if bookmark couldn't be set (probably because the user is not allowed to edit this bookmark)
if ($result->numRows() == 0) exit();

# Remove old tags and insert new ones.
$query = OCP\DB::prepare("
	DELETE FROM *PREFIX*bookmarks_tags
	WHERE bookmark_id = $bookmark_id
	");

$query->execute();

$query = OCP\DB::prepare("
	INSERT INTO *PREFIX*bookmarks_tags
	(bookmark_id, tag)
	VALUES (?, ?)
	");

$tags = explode(' ', urldecode($_POST["tags"]));
foreach ($tags as $tag) {
	if(empty($tag)) {
		//avoid saving blankspaces
		continue;
	}
	$params = array($bookmark_id, trim($tag));
	$query->execute($params);
}

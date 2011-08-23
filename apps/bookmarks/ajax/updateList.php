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

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

$params=array(OC_User::getUser());
$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );

//Filter for tag?
$filterTag = isset($_GET["tag"]) ? '%' . urldecode($_GET["tag"]) . '%' : false;
if($filterTag){
	$sqlFilterTag = 'HAVING tags LIKE ?';
	$params[] = $filterTag;
} else {
	$sqlFilterTag = '';
}

$offset = isset($_GET["page"]) ? intval($_GET["page"]) * 10 : 0;
$params[] = $offset;

if( $CONFIG_DBTYPE == 'sqlite' or $CONFIG_DBTYPE == 'sqlite3' ){
	$_gc_separator = ", ' '";
} else {
	$_gc_separator = "SEPARATOR ' '";
}

//FIXME: bookmarks without tags are not being retrieved
$query = OC_DB::prepare("
	SELECT url, title, description, GROUP_CONCAT( tag $_gc_separator ) AS tags
	FROM *PREFIX*bookmarks, *PREFIX*bookmarks_tags 
	WHERE *PREFIX*bookmarks.id = *PREFIX*bookmarks_tags.bookmark_id
		AND *PREFIX*bookmarks.user_id = ?
	GROUP BY url
	$sqlFilterTag
	ORDER BY *PREFIX*bookmarks.id DESC 
	LIMIT ?,  10");
	

$bookmarks = $query->execute($params)->fetchAll();

echo json_encode( array( "status" => "success", "data" => $bookmarks));

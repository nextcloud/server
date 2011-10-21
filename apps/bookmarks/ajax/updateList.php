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
OC_JSON::checkAppEnabled('bookmarks');

$params=array(OC_User::getUser());
$CONFIG_DBTYPE = OC_Config::getValue( 'dbtype', 'sqlite' );

//Filter for tag?
$filterTag = isset($_GET['tag']) ? '%' . htmlspecialchars_decode($_GET['tag']) . '%' : false;
if($filterTag){
	$sqlFilterTag = 'HAVING tags LIKE ?';
	$params[] = $filterTag;
	if($CONFIG_DBTYPE == 'pgsql' ) {
		$sqlFilterTag = 'HAVING array_to_string(array_agg(tag), \' \')  LIKE ?';
	}
} else {
	$sqlFilterTag = '';
}

$offset = isset($_GET['page']) ? intval($_GET['page']) * 10 : 0;

$sort = isset($_GET['sort']) ? ($_GET['sort']) : 'bookmarks_sorting_recent';
if($sort == 'bookmarks_sorting_clicks') {
	$sqlSort = 'clickcount DESC';
} else {
	$sqlSort = 'id DESC';
}

if( $CONFIG_DBTYPE == 'sqlite' or $CONFIG_DBTYPE == 'sqlite3' ){
	$_gc_separator = ', \' \'';
} else {
	$_gc_separator = 'SEPARATOR \' \'';
}

if($CONFIG_DBTYPE == 'pgsql' ){
	$params[] = $offset;
	$query = OC_DB::prepare('
		SELECT id, url, title, array_to_string(array_agg(tag), \' \') as tags
		FROM *PREFIX*bookmarks
		LEFT JOIN *PREFIX*bookmarks_tags ON *PREFIX*bookmarks.id = *PREFIX*bookmarks_tags.bookmark_id 
		WHERE 
			*PREFIX*bookmarks.user_id = ?
		GROUP BY id, url, title
		'.$sqlFilterTag.'
		ORDER BY *PREFIX*bookmarks.'.$sqlSort.' 
		LIMIT 10
		OFFSET ?');
} else {
	$query = OC_DB::prepare('
		SELECT id, url, title, 
		CASE WHEN *PREFIX*bookmarks.id = *PREFIX*bookmarks_tags.bookmark_id
				THEN GROUP_CONCAT( tag ' .$_gc_separator. ' )
				ELSE \' \'
			END
			AS tags
		FROM *PREFIX*bookmarks
		LEFT JOIN *PREFIX*bookmarks_tags ON 1=1
		WHERE (*PREFIX*bookmarks.id = *PREFIX*bookmarks_tags.bookmark_id 
				OR *PREFIX*bookmarks.id NOT IN (
					SELECT *PREFIX*bookmarks_tags.bookmark_id FROM *PREFIX*bookmarks_tags
				)
			)
			AND *PREFIX*bookmarks.user_id = ?
		GROUP BY url
		'.$sqlFilterTag.'
		ORDER BY *PREFIX*bookmarks.'.$sqlSort.' 
		LIMIT '.$offset.',  10');
}

$bookmarks = $query->execute($params)->fetchAll();

OC_JSON::success(array('data' => $bookmarks));

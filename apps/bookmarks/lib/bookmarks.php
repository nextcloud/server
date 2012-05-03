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
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This class manages bookmarks
 */
class OC_Bookmarks_Bookmarks{

	/**
	 * @brief Finds all bookmarks, matching the filter
	 * @param offset result offset
	 * @param sqlSortColumn sort result with this column
	 * @param filter can be: empty -> no filter, a string -> filter this, a string array -> filter for all strings
	 * @param filterTagOnly if true, filter affacts only tags, else filter affects url, title and tags
	 * @return void
	 */
	public static function findBookmarks($offset, $sqlSortColumn, $filter, $filterTagOnly){
		//OCP\Util::writeLog('bookmarks', 'findBookmarks ' .$offset. ' '.$sqlSortColumn.' '. $filter.' '. $filterTagOnly ,OCP\Util::DEBUG);
		$CONFIG_DBTYPE = OCP\Config::getSystemValue( 'dbtype', 'sqlite' );
	
		$params=array(OCP\USER::getUser());
	
		if( $CONFIG_DBTYPE == 'sqlite' or $CONFIG_DBTYPE == 'sqlite3' ){
			$_gc_separator = ', \' \'';
		} else {
			$_gc_separator = 'SEPARATOR \' \'';
		}

		if($filter){
			if($CONFIG_DBTYPE == 'pgsql' )
				$tagString = 'array_to_string(array_agg(tag), \' \')';
			else
				$tagString = 'tags';

			$sqlFilterTag = 'HAVING ';
			if(is_array($filter)){
				$first = true;
				$filterstring = '';
				foreach ($filter as $singleFilter){
					$filterstring = $filterstring . ($first?'':' AND ') . $tagString.' LIKE ? ';
					$params[] = '%'.$singleFilter.'%';
					$first=false;
				}
				$sqlFilterTag = $sqlFilterTag . $filterstring;
			} else{
				$sqlFilterTag = $sqlFilterTag .$tagString.' LIKE ? ';
				$params[] = '%'.$filter.'%';
			}
		} else {
			$sqlFilterTag = '';
		}

		if($CONFIG_DBTYPE == 'pgsql' ){
			$query = OCP\DB::prepare('
				SELECT id, url, title, '.($filterTagOnly?'':'url || title ||').' array_to_string(array_agg(tag), \' \') as tags
				FROM *PREFIX*bookmarks
				LEFT JOIN *PREFIX*bookmarks_tags ON *PREFIX*bookmarks.id = *PREFIX*bookmarks_tags.bookmark_id 
				WHERE 
					*PREFIX*bookmarks.user_id = ?
				GROUP BY id, url, title
				'.$sqlFilterTag.'
				ORDER BY *PREFIX*bookmarks.'.$sqlSortColumn.' DESC 
				LIMIT 10
				OFFSET '. $offset);
		} else {
			if( $CONFIG_DBTYPE == 'sqlite' or $CONFIG_DBTYPE == 'sqlite3' )
				$concatFunction = '(url || title || ';
			else
				$concatFunction = 'Concat(Concat( url, title), ';
		
			$query = OCP\DB::prepare('
				SELECT id, url, title, '
				.($filterTagOnly?'':$concatFunction).
				'CASE WHEN *PREFIX*bookmarks.id = *PREFIX*bookmarks_tags.bookmark_id
						THEN GROUP_CONCAT( tag ' .$_gc_separator. ' )
						ELSE \' \'
					END '
				.($filterTagOnly?'':')').'
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
				ORDER BY *PREFIX*bookmarks.'.$sqlSortColumn.' DESC
				LIMIT '.$offset.',  10');
		}

		$bookmarks = $query->execute($params)->fetchAll();
		return $bookmarks;
	}

	public static function deleteUrl($id)
	{
		$user = OCP\USER::getUser();

		$query = OCP\DB::prepare("
				SELECT id FROM *PREFIX*bookmarks
				WHERE id = ?
				AND user_id = ?
				");

		$result = $query->execute(array($id, $user));
		$id = $result->fetchOne();
		if ($id === false) {
			return false;
		}

		$query = OCP\DB::prepare("
			DELETE FROM *PREFIX*bookmarks
			WHERE id = $id
			");

		$result = $query->execute();

		$query = OCP\DB::prepare("
			DELETE FROM *PREFIX*bookmarks_tags
			WHERE bookmark_id = $id
			");

		$result = $query->execute();
		return true;
	}
}
?>

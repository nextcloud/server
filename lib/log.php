<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2010 Frank Karlitschek karlitschek@kde.org
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
/*
 *
 * The following SQL statement is just a help for developers and will not be
 * executed!
 *
 * CREATE TABLE `log` (
 * `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 * `timestamp` DATETIME NOT NULL ,
 * `appid` VARCHAR( 255 ) NOT NULL ,
 * `user` VARCHAR( 255 ) NOT NULL ,
 * `action` VARCHAR( 255 ) NOT NULL ,
 * `info` TEXT NOT NULL
 * ) ENGINE = MYISAM ;
 *
 */

/**
 * This class is for logging
 */
class OC_LOG {
	/**
	 * @brief adds an entry to the log
	 * @param $appid id of the app
	 * @param $subject username
	 * @param $predicate action
	 * @param $object = null; additional information
	 * @returns true/false
	 *
	 * This function adds another entry to the log database
	 */
	public static function add( $appid, $subject, $predicate, $object = ' ' ){
		$query=OC_DB::prepare("INSERT INTO *PREFIX*log(`timestamp`,appid,user,action,info) VALUES(NOW(),?,?,?,?)");
		$query->execute(array($appid,$subject,$predicate,$object));
		return true;
	}

	/**
	 * @brief Fetches log entries
	 * @param $filter = array(); array with filter options
	 * @returns array with entries
	 *
	 * This function fetches the log entries according to the filter options
	 * passed.
	 *
	 * $filter is an associative array.
	 * The following keys are optional:
	 *   - from: all entries after this date
	 *   - until: all entries until this date
	 *   - user: username (default: current user)
	 *   - app: only entries for this app
	 */
	public static function get( $filter = array()){
		$queryString='SELECT * FROM *PREFIX*log WHERE 1=1 ';
		$params=array();
		if(isset($filter['from'])){
			$queryString.='AND `timestamp`>? ';
			array_push($params,$filter('from'));
		}
		if(isset($filter['until'])){
			$queryString.='AND `timestamp`<? ';
			array_push($params,$filter('until'));
		}
		if(isset($filter['user'])){
			$queryString.='AND user=? ';
			array_push($params,$filter('user'));
		}
		if(isset($filter['app'])){
			$queryString.='AND appid=? ';
			array_push($params,$filter('app'));
		}
		$query=OC_DB::prepare($queryString);
		return $query->execute($params)->fetchAll();
		
	}

	/**
	 * @brief removes log entries
	 * @param $date delete entries older than this date
	 * @returns true/false
	 *
	 * This function deletes all entries that are older than $date.
	 */
	public static function deleteBefore( $date ){
		$query=OC_DB::prepare("DELETE FROM *PREFIX*log WHERE `timestamp`<?");
		$query->execute(array($date));
		return true;
	}
	
	/**
	 * @brief filter an array of log entries on action
	 * @param array $logs the log entries to filter
	 * @param array $actions an array of actions to filter for
	 * @returns array
	 */
	public static function filterAction($logs,$actions){
		$filteredLogs=array();
		foreach($logs as $log){
			if(array_search($log['action'],$actions)!==false){
				$filteredLogs[]=$log;
			}
		}
		return $filteredLogs;
	}
}



?>

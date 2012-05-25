<?php
/**
* ownCloud
*
* @author Frank Karlitschek
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

/**
 * Public interface of ownCloud for apps to use.
 * DB Class
 *
 */

// use OCP namespace for all classes that are considered public. 
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides access to the internal database system. Use this class exlusively if you want to access databases
 */
class DB {


	/**
	 * @brief Prepare a SQL query
	 * @param $query Query string
	 * @returns prepared SQL query
	 *
	 * SQL query via MDB2 prepare(), needs to be execute()'d!
	 */
	static public function prepare( $query ){
		return(\OC_DB::prepare($query));
	}


	/**
	 * @brief gets last value of autoincrement
	 * @param $table string The optional table name (will replace *PREFIX*) and add sequence suffix
	 * @returns id
	 *
	 * MDB2 lastInsertID()
	 *
	 * Call this method right after the insert command or other functions may
	 * cause trouble!
	 */
	public static function insertid($table=null){
		return(\OC_DB::insertid($table));
	}


	/**
	 * @brief Start a transaction
	 */
	public static function beginTransaction(){
		return(\OC_DB::beginTransaction());
	}


	/**
	 * @brief Commit the database changes done during a transaction that is in progress
	 */
	public static function commit(){
		return(\OC_DB::commit());
	}


	/**
	 * @brief check if a result is an error, works with MDB2 and PDOException
	 * @param mixed $result
	 * @return bool
	 */
	public static function isError($result){
		return(\OC_DB::isError($result));
	}



}

?>

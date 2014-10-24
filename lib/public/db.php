<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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
	 * Prepare a SQL query
	 * @param string $query Query string
	 * @param int $limit Limit of the SQL statement
	 * @param int $offset Offset of the SQL statement
	 * @return \OC_DB_StatementWrapper prepared SQL query
	 *
	 * SQL query via Doctrine prepare(), needs to be execute()'d!
	 */
	static public function prepare( $query, $limit=null, $offset=null ) {
		return(\OC_DB::prepare($query, $limit, $offset));
	}

	/**
	 * Insert a row if a matching row doesn't exists.
	 * @param string $table The optional table name (will replace *PREFIX*) and add sequence suffix
	 * @param array $input
	 *
	 * The input array if in the form:
	 *
	 * array ( 'id' => array ( 'value' => 6,
	 *	'key' => true
	 *	),
	 *	'name' => array ('value' => 'Stoyan'),
	 *	'family' => array ('value' => 'Stefanov'),
	 *	'birth_date' => array ('value' => '1975-06-20')
	 *	);
	 * @return bool
	 *
	 */
	public static function insertIfNotExist($table, $input) {
		return(\OC_DB::insertIfNotExist($table, $input));
	}

	/**
	 * Gets last value of autoincrement
	 * @param string $table The optional table name (will replace *PREFIX*) and add sequence suffix
	 * @return string
	 *
	 * \Doctrine\DBAL\Connection lastInsertID()
	 *
	 * Call this method right after the insert command or other functions may
	 * cause trouble!
	 */
	public static function insertid($table=null) {
		return(\OC_DB::insertid($table));
	}

	/**
	 * Start a transaction
	 */
	public static function beginTransaction() {
		\OC_DB::beginTransaction();
	}

	/**
	 * Commit the database changes done during a transaction that is in progress
	 */
	public static function commit() {
		\OC_DB::commit();
	}

	/**
	 * Rollback the database changes done during a transaction that is in progress
	 */
	public static function rollback() {
		\OC_DB::rollback();
	}

	/**
	 * Check if a result is an error, works with Doctrine
	 * @param mixed $result
	 * @return bool
	 */
	public static function isError($result) {
		return(\OC_DB::isError($result));
	}

	/**
	 * returns the error code and message as a string for logging
	 * works with DoctrineException
	 * @param mixed $error
	 * @return string
	 */
	public static function getErrorMessage($error) {
		return(\OC_DB::getErrorMessage($error));
	}

}

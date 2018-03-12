<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Dan Bartram <daneybartram@gmail.com>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
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
 * @deprecated 8.1.0 use methods of \OCP\IDBConnection - \OC::$server->getDatabaseConnection()
 * @since 4.5.0
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
	 * @deprecated 8.1.0 use prepare() of \OCP\IDBConnection - \OC::$server->getDatabaseConnection()
	 * @since 4.5.0
	 */
	static public function prepare( $query, $limit=null, $offset=null ) {
		return \OC_DB::prepare($query, $limit, $offset);
	}

	/**
	 * Start a transaction
	 * @deprecated 8.1.0 use beginTransaction() of \OCP\IDBConnection - \OC::$server->getDatabaseConnection()
	 * @since 4.5.0
	 */
	public static function beginTransaction() {
		\OC::$server->getDatabaseConnection()->beginTransaction();
	}

	/**
	 * Commit the database changes done during a transaction that is in progress
	 * @deprecated 8.1.0 use commit() of \OCP\IDBConnection - \OC::$server->getDatabaseConnection()
	 * @since 4.5.0
	 */
	public static function commit() {
		\OC::$server->getDatabaseConnection()->commit();
	}

	/**
	 * returns the error code and message as a string for logging
	 * works with DoctrineException
	 * @return string
	 * @deprecated 8.1.0 use getError() of \OCP\IDBConnection - \OC::$server->getDatabaseConnection()
	 * @since 6.0.0
	 */
	public static function getErrorMessage() {
		return \OC::$server->getDatabaseConnection()->getError();
	}

}

<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCP;


/**
 * Small Facade for being able to inject the database connection for tests
 * @since 7.0.0 - extends IDBConnection was added in 8.1.0
 */
interface IDb extends IDBConnection {


    /**
     * Used to abstract the owncloud database access away
     * @param string $sql the sql query with ? placeholder for params
     * @param int $limit the maximum number of rows
     * @param int $offset from which row we want to start
     * @return \OC_DB_StatementWrapper prepared SQL query
	 * @since 7.0.0
     */
    public function prepareQuery($sql, $limit=null, $offset=null);


    /**
     * Used to get the id of the just inserted element
     * @param string $tableName the name of the table where we inserted the item
     * @return int the id of the inserted element
	 * @since 7.0.0
     */
    public function getInsertId($tableName);


}

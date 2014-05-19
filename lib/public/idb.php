<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt dev@bernhard-posselt.com
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

namespace OCP;


/**
 * Small Facade for being able to inject the database connection for tests
 */
interface IDb {


    /**
     * Used to abstract the owncloud database access away
     * @param string $sql the sql query with ? placeholder for params
     * @param int $limit the maximum number of rows
     * @param int $offset from which row we want to start
     * @return \OC_DB_StatementWrapper prepared SQL query
     */
    public function prepareQuery($sql, $limit=null, $offset=null);


    /**
     * Used to get the id of the just inserted element
     * @param string $tableName the name of the table where we inserted the item
     * @return int the id of the inserted element
     */
    public function getInsertId($tableName);


}

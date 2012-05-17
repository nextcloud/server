<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2006 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith                                         |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// | MDB2 is a merge of PEAR DB and Metabases that provides a unified DB  |
// | API as well as database abstraction for PHP applications.            |
// | This LICENSE is in the BSD license style.                            |
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,    |
// | Lukas Smith nor the names of his contributors may be used to endorse |
// | or promote products derived from this software without specific prior|
// | written permission.                                                  |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS|
// |  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED  |
// | AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT          |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY|
// | WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE          |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Author: Lukas Smith <smith@pooteeweet.org>                           |
// +----------------------------------------------------------------------+
//
// $Id$

/**
 * @package  MDB2
 * @category Database
 * @author   Lukas Smith <smith@pooteeweet.org>
 */

/**
 * Used by autoPrepare()
 */
define('MDB2_AUTOQUERY_INSERT', 1);
define('MDB2_AUTOQUERY_UPDATE', 2);
define('MDB2_AUTOQUERY_DELETE', 3);
define('MDB2_AUTOQUERY_SELECT', 4);

/**
 * MDB2_Extended: class which adds several high level methods to MDB2
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Extended extends MDB2_Module_Common
{
    // {{{ autoPrepare()

    /**
     * Generate an insert, update or delete query and call prepare() on it
     *
     * @param string table
     * @param array the fields names
     * @param int type of query to build
     *                          MDB2_AUTOQUERY_INSERT
     *                          MDB2_AUTOQUERY_UPDATE
     *                          MDB2_AUTOQUERY_DELETE
     *                          MDB2_AUTOQUERY_SELECT
     * @param string (in case of update and delete queries, this string will be put after the sql WHERE statement)
     * @param array that contains the types of the placeholders
     * @param mixed array that contains the types of the columns in
     *                        the result set or MDB2_PREPARE_RESULT, if set to
     *                        MDB2_PREPARE_MANIP the query is handled as a manipulation query
     *
     * @return resource handle for the query
     * @see buildManipSQL
     * @access public
     */
    function autoPrepare($table, $table_fields, $mode = MDB2_AUTOQUERY_INSERT,
        $where = false, $types = null, $result_types = MDB2_PREPARE_MANIP)
    {
        $query = $this->buildManipSQL($table, $table_fields, $mode, $where);
        if (PEAR::isError($query)) {
            return $query;
        }
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $lobs = array();
        foreach ((array)$types as $param => $type) {
            if (($type == 'clob') || ($type == 'blob')) {
                $lobs[$param] = $table_fields[$param];
            }
        }
        return $db->prepare($query, $types, $result_types, $lobs);
    }

    // }}}
    // {{{ autoExecute()

    /**
     * Generate an insert, update or delete query and call prepare() and execute() on it
     *
     * @param string name of the table
     * @param array assoc ($key=>$value) where $key is a field name and $value its value
     * @param int type of query to build
     *                          MDB2_AUTOQUERY_INSERT
     *                          MDB2_AUTOQUERY_UPDATE
     *                          MDB2_AUTOQUERY_DELETE
     *                          MDB2_AUTOQUERY_SELECT
     * @param string (in case of update and delete queries, this string will be put after the sql WHERE statement)
     * @param array that contains the types of the placeholders
     * @param string which specifies which result class to use
     * @param mixed  array that contains the types of the columns in
     *                        the result set or MDB2_PREPARE_RESULT, if set to
     *                        MDB2_PREPARE_MANIP the query is handled as a manipulation query
     *
     * @return bool|MDB2_Error true on success, a MDB2 error on failure
     * @see buildManipSQL
     * @see autoPrepare
     * @access public
    */
    function autoExecute($table, $fields_values, $mode = MDB2_AUTOQUERY_INSERT,
        $where = false, $types = null, $result_class = true, $result_types = MDB2_PREPARE_MANIP)
    {
        $fields_values = (array)$fields_values;
        if ($mode == MDB2_AUTOQUERY_SELECT) {
            if (is_array($result_types)) {
                $keys = array_keys($result_types);
            } elseif (!empty($fields_values)) {
                $keys = $fields_values;
            } else {
                $keys = array();
            }
        } else {
            $keys = array_keys($fields_values);
        }
        $params = array_values($fields_values);
        if (empty($params)) {
            $query = $this->buildManipSQL($table, $keys, $mode, $where);

            $db = $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }
            if ($mode == MDB2_AUTOQUERY_SELECT) {
                $result = $db->query($query, $result_types, $result_class);
            } else {
                $result = $db->exec($query);
            }
        } else {
            $stmt = $this->autoPrepare($table, $keys, $mode, $where, $types, $result_types);
            if (PEAR::isError($stmt)) {
                return $stmt;
            }
            $result = $stmt->execute($params, $result_class);
            $stmt->free();
        }
        return $result;
    }

    // }}}
    // {{{ buildManipSQL()

    /**
     * Make automaticaly an sql query for prepare()
     *
     * Example : buildManipSQL('table_sql', array('field1', 'field2', 'field3'), MDB2_AUTOQUERY_INSERT)
     *           will return the string : INSERT INTO table_sql (field1,field2,field3) VALUES (?,?,?)
     * NB : - This belongs more to a SQL Builder class, but this is a simple facility
     *      - Be carefull ! If you don't give a $where param with an UPDATE/DELETE query, all
     *        the records of the table will be updated/deleted !
     *
     * @param string name of the table
     * @param ordered array containing the fields names
     * @param int type of query to build
     *                          MDB2_AUTOQUERY_INSERT
     *                          MDB2_AUTOQUERY_UPDATE
     *                          MDB2_AUTOQUERY_DELETE
     *                          MDB2_AUTOQUERY_SELECT
     * @param string (in case of update and delete queries, this string will be put after the sql WHERE statement)
     *
     * @return string sql query for prepare()
     * @access public
     */
    function buildManipSQL($table, $table_fields, $mode, $where = false)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if ($db->options['quote_identifier']) {
            $table = $db->quoteIdentifier($table);
        }

        if (!empty($table_fields) && $db->options['quote_identifier']) {
            foreach ($table_fields as $key => $field) {
                $table_fields[$key] = $db->quoteIdentifier($field);
            }
        }

        if ((false !== $where) && (null !== $where)) {
            if (is_array($where)) {
                $where = implode(' AND ', $where);
            }
            $where = ' WHERE '.$where;
        }

        switch ($mode) {
        case MDB2_AUTOQUERY_INSERT:
            if (empty($table_fields)) {
                return $db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'Insert requires table fields', __FUNCTION__);
            }
            $cols = implode(', ', $table_fields);
            $values = '?'.str_repeat(', ?', (count($table_fields) - 1));
            return 'INSERT INTO '.$table.' ('.$cols.') VALUES ('.$values.')';
            break;
        case MDB2_AUTOQUERY_UPDATE:
            if (empty($table_fields)) {
                return $db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'Update requires table fields', __FUNCTION__);
            }
            $set = implode(' = ?, ', $table_fields).' = ?';
            $sql = 'UPDATE '.$table.' SET '.$set.$where;
            return $sql;
            break;
        case MDB2_AUTOQUERY_DELETE:
            $sql = 'DELETE FROM '.$table.$where;
            return $sql;
            break;
        case MDB2_AUTOQUERY_SELECT:
            $cols = !empty($table_fields) ? implode(', ', $table_fields) : '*';
            $sql = 'SELECT '.$cols.' FROM '.$table.$where;
            return $sql;
            break;
        }
        return $db->raiseError(MDB2_ERROR_SYNTAX, null, null,
                'Non existant mode', __FUNCTION__);
    }

    // }}}
    // {{{ limitQuery()

    /**
     * Generates a limited query
     *
     * @param string query
     * @param array that contains the types of the columns in the result set
     * @param integer the numbers of rows to fetch
     * @param integer the row to start to fetching
     * @param string which specifies which result class to use
     * @param mixed   string which specifies which class to wrap results in
     *
     * @return MDB2_Result|MDB2_Error result set on success, a MDB2 error on failure
     * @access public
     */
    function limitQuery($query, $types, $limit, $offset = 0, $result_class = true,
        $result_wrap_class = false)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $result = $db->setLimit($limit, $offset);
        if (PEAR::isError($result)) {
            return $result;
        }
        return $db->query($query, $types, $result_class, $result_wrap_class);
    }

    // }}}
    // {{{ execParam()

    /**
     * Execute a parameterized DML statement.
     *
     * @param string the SQL query
     * @param array if supplied, prepare/execute will be used
     *       with this array as execute parameters
     * @param array that contains the types of the values defined in $params
     *
     * @return int|MDB2_Error affected rows on success, a MDB2 error on failure
     * @access public
     */
    function execParam($query, $params = array(), $param_types = null)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        settype($params, 'array');
        if (empty($params)) {
            return $db->exec($query);
        }

        $stmt = $db->prepare($query, $param_types, MDB2_PREPARE_MANIP);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }

        $result = $stmt->execute($params);
        if (PEAR::isError($result)) {
            return $result;
        }

        $stmt->free();
        return $result;
    }

    // }}}
    // {{{ getOne()

    /**
     * Fetch the first column of the first row of data returned from a query.
     * Takes care of doing the query and freeing the results when finished.
     *
     * @param string the SQL query
     * @param string that contains the type of the column in the result set
     * @param array if supplied, prepare/execute will be used
     *       with this array as execute parameters
     * @param array that contains the types of the values defined in $params
     * @param int|string which column to return
     *
     * @return scalar|MDB2_Error data on success, a MDB2 error on failure
     * @access public
     */
    function getOne($query, $type = null, $params = array(),
        $param_types = null, $colnum = 0)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        settype($params, 'array');
        settype($type, 'array');
        if (empty($params)) {
            return $db->queryOne($query, $type, $colnum);
        }

        $stmt = $db->prepare($query, $param_types, $type);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }

        $result = $stmt->execute($params);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $one = $result->fetchOne($colnum);
        $stmt->free();
        $result->free();
        return $one;
    }

    // }}}
    // {{{ getRow()

    /**
     * Fetch the first row of data returned from a query.  Takes care
     * of doing the query and freeing the results when finished.
     *
     * @param string the SQL query
     * @param array that contains the types of the columns in the result set
     * @param array if supplied, prepare/execute will be used
     *       with this array as execute parameters
     * @param array that contains the types of the values defined in $params
     * @param int the fetch mode to use
     *
     * @return array|MDB2_Error data on success, a MDB2 error on failure
     * @access public
     */
    function getRow($query, $types = null, $params = array(),
        $param_types = null, $fetchmode = MDB2_FETCHMODE_DEFAULT)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        settype($params, 'array');
        if (empty($params)) {
            return $db->queryRow($query, $types, $fetchmode);
        }

        $stmt = $db->prepare($query, $param_types, $types);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }

        $result = $stmt->execute($params);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $row = $result->fetchRow($fetchmode);
        $stmt->free();
        $result->free();
        return $row;
    }

    // }}}
    // {{{ getCol()

    /**
     * Fetch a single column from a result set and return it as an
     * indexed array.
     *
     * @param string the SQL query
     * @param string that contains the type of the column in the result set
     * @param array if supplied, prepare/execute will be used
     *       with this array as execute parameters
     * @param array that contains the types of the values defined in $params
     * @param int|string which column to return
     *
     * @return array|MDB2_Error data on success, a MDB2 error on failure
     * @access public
     */
    function getCol($query, $type = null, $params = array(),
        $param_types = null, $colnum = 0)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        settype($params, 'array');
        settype($type, 'array');
        if (empty($params)) {
            return $db->queryCol($query, $type, $colnum);
        }

        $stmt = $db->prepare($query, $param_types, $type);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }

        $result = $stmt->execute($params);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $col = $result->fetchCol($colnum);
        $stmt->free();
        $result->free();
        return $col;
    }

    // }}}
    // {{{ getAll()

    /**
     * Fetch all the rows returned from a query.
     *
     * @param string the SQL query
     * @param array that contains the types of the columns in the result set
     * @param array if supplied, prepare/execute will be used
     *       with this array as execute parameters
     * @param array that contains the types of the values defined in $params
     * @param int the fetch mode to use
     * @param bool if set to true, the $all will have the first
     *       column as its first dimension
     * @param bool $force_array used only when the query returns exactly
     *       two columns. If true, the values of the returned array will be
     *       one-element arrays instead of scalars.
     * @param bool $group if true, the values of the returned array is
     *       wrapped in another array.  If the same key value (in the first
     *       column) repeats itself, the values will be appended to this array
     *       instead of overwriting the existing values.
     *
     * @return array|MDB2_Error data on success, a MDB2 error on failure
     * @access public
     */
    function getAll($query, $types = null, $params = array(),
        $param_types = null, $fetchmode = MDB2_FETCHMODE_DEFAULT,
        $rekey = false, $force_array = false, $group = false)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        settype($params, 'array');
        if (empty($params)) {
            return $db->queryAll($query, $types, $fetchmode, $rekey, $force_array, $group);
        }

        $stmt = $db->prepare($query, $param_types, $types);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }

        $result = $stmt->execute($params);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $all = $result->fetchAll($fetchmode, $rekey, $force_array, $group);
        $stmt->free();
        $result->free();
        return $all;
    }

    // }}}
    // {{{ getAssoc()

    /**
     * Fetch the entire result set of a query and return it as an
     * associative array using the first column as the key.
     *
     * If the result set contains more than two columns, the value
     * will be an array of the values from column 2-n.  If the result
     * set contains only two columns, the returned value will be a
     * scalar with the value of the second column (unless forced to an
     * array with the $force_array parameter).  A MDB2 error code is
     * returned on errors.  If the result set contains fewer than two
     * columns, a MDB2_ERROR_TRUNCATED error is returned.
     *
     * For example, if the table 'mytable' contains:
     * <pre>
     *   ID      TEXT       DATE
     * --------------------------------
     *   1       'one'      944679408
     *   2       'two'      944679408
     *   3       'three'    944679408
     * </pre>
     * Then the call getAssoc('SELECT id,text FROM mytable') returns:
     * <pre>
     *    array(
     *      '1' => 'one',
     *      '2' => 'two',
     *      '3' => 'three',
     *    )
     * </pre>
     * ...while the call getAssoc('SELECT id,text,date FROM mytable') returns:
     * <pre>
     *    array(
     *      '1' => array('one', '944679408'),
     *      '2' => array('two', '944679408'),
     *      '3' => array('three', '944679408')
     *    )
     * </pre>
     *
     * If the more than one row occurs with the same value in the
     * first column, the last row overwrites all previous ones by
     * default.  Use the $group parameter if you don't want to
     * overwrite like this.  Example:
     * <pre>
     * getAssoc('SELECT category,id,name FROM mytable', null, null
     *           MDB2_FETCHMODE_ASSOC, false, true) returns:
     *    array(
     *      '1' => array(array('id' => '4', 'name' => 'number four'),
     *                   array('id' => '6', 'name' => 'number six')
     *             ),
     *      '9' => array(array('id' => '4', 'name' => 'number four'),
     *                   array('id' => '6', 'name' => 'number six')
     *             )
     *    )
     * </pre>
     *
     * Keep in mind that database functions in PHP usually return string
     * values for results regardless of the database's internal type.
     *
     * @param string the SQL query
     * @param array that contains the types of the columns in the result set
     * @param array if supplied, prepare/execute will be used
     *       with this array as execute parameters
     * @param array that contains the types of the values defined in $params
     * @param bool $force_array used only when the query returns
     * exactly two columns.  If TRUE, the values of the returned array
     * will be one-element arrays instead of scalars.
     * @param bool $group if TRUE, the values of the returned array
     *       is wrapped in another array.  If the same key value (in the first
     *       column) repeats itself, the values will be appended to this array
     *       instead of overwriting the existing values.
     *
     * @return array|MDB2_Error data on success, a MDB2 error on failure
     * @access public
     */
    function getAssoc($query, $types = null, $params = array(), $param_types = null,
        $fetchmode = MDB2_FETCHMODE_DEFAULT, $force_array = false, $group = false)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        settype($params, 'array');
        if (empty($params)) {
            return $db->queryAll($query, $types, $fetchmode, true, $force_array, $group);
        }

        $stmt = $db->prepare($query, $param_types, $types);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }

        $result = $stmt->execute($params);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $all = $result->fetchAll($fetchmode, true, $force_array, $group);
        $stmt->free();
        $result->free();
        return $all;
    }

    // }}}
    // {{{ executeMultiple()

    /**
     * This function does several execute() calls on the same statement handle.
     * $params must be an array indexed numerically from 0, one execute call is
     * done for every 'row' in the array.
     *
     * If an error occurs during execute(), executeMultiple() does not execute
     * the unfinished rows, but rather returns that error.
     *
     * @param resource query handle from prepare()
     * @param array numeric array containing the data to insert into the query
     *
     * @return bool|MDB2_Error true on success, a MDB2 error on failure
     * @access public
     * @see prepare(), execute()
     */
    function executeMultiple($stmt, $params = null)
    {
        if (MDB2::isError($stmt)) {
            return $stmt;
        }
        for ($i = 0, $j = count($params); $i < $j; $i++) {
            $result = $stmt->execute($params[$i]);
            if (PEAR::isError($result)) {
                return $result;
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ getBeforeID()

    /**
     * Returns the next free id of a sequence if the RDBMS
     * does not support auto increment
     *
     * @param string name of the table into which a new row was inserted
     * @param string name of the field into which a new row was inserted
     * @param bool when true the sequence is automatic created, if it not exists
     * @param bool if the returned value should be quoted
     *
     * @return int|MDB2_Error id on success, a MDB2 error on failure
     * @access public
     */
    function getBeforeID($table, $field = null, $ondemand = true, $quote = true)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if ($db->supports('auto_increment') !== true) {
            $seq = $table.(empty($field) ? '' : '_'.$field);
            $id = $db->nextID($seq, $ondemand);
            if (!$quote || PEAR::isError($id)) {
                return $id;
            }
            return $db->quote($id, 'integer');
        } elseif (!$quote) {
            return null;
        }
        return 'NULL';
    }

    // }}}
    // {{{ getAfterID()

    /**
     * Returns the autoincrement ID if supported or $id
     *
     * @param mixed value as returned by getBeforeId()
     * @param string name of the table into which a new row was inserted
     * @param string name of the field into which a new row was inserted
     *
     * @return int|MDB2_Error id on success, a MDB2 error on failure
     * @access public
     */
    function getAfterID($id, $table, $field = null)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if ($db->supports('auto_increment') !== true) {
            return $id;
        }
        return $db->lastInsertID($table, $field);
    }

    // }}}
}
?>
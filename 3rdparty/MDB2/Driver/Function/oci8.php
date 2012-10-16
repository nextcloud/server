<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2008 Manuel Lemos, Tomas V.V.Cox,                 |
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

// $Id: oci8.php 295587 2010-02-28 17:16:38Z quipo $

require_once 'MDB2/Driver/Function/Common.php';

/**
 * MDB2 oci8 driver for the function modules
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Function_oci8 extends MDB2_Driver_Function_Common
{
    // {{{ executeStoredProc()

    /**
     * Execute a stored procedure and return any results
     *
     * @param string $name string that identifies the function to execute
     * @param mixed  $params  array that contains the paramaters to pass the stored proc
     * @param mixed   $types  array that contains the types of the columns in
     *                        the result set
     * @param mixed $result_class string which specifies which result class to use
     * @param mixed $result_wrap_class string which specifies which class to wrap results in
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function executeStoredProc($name, $params = null, $types = null, $result_class = true, $result_wrap_class = false)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = 'EXEC '.$name;
        $query .= $params ? '('.implode(', ', $params).')' : '()';
        return $db->query($query, $types, $result_class, $result_wrap_class);
    }

    // }}}
    // {{{ functionTable()

    /**
     * return string for internal table used when calling only a function
     *
     * @return string for internal table used when calling only a function
     * @access public
     */
    function functionTable()
    {
        return ' FROM dual';
    }

    // }}}
    // {{{ now()

    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time:
     * - CURRENT_TIMESTAMP (date and time, TIMESTAMP type)
     * - CURRENT_DATE (date, DATE type)
     * - CURRENT_TIME (time, TIME type)
     *
     * @return string to call a variable with the current timestamp
     * @access public
     */
    function now($type = 'timestamp')
    {
        switch ($type) {
        case 'date':
        case 'time':
        case 'timestamp':
        default:
            return 'TO_CHAR(CURRENT_TIMESTAMP, \'YYYY-MM-DD HH24:MI:SS\')';
        }
    }

    // }}}
    // {{{ unixtimestamp()

    /**
     * return string to call a function to get the unix timestamp from a iso timestamp
     *
     * @param string $expression
     *
     * @return string to call a variable with the timestamp
     * @access public
     */
    function unixtimestamp($expression)
    {
        $utc_offset = 'CAST(SYS_EXTRACT_UTC(SYSTIMESTAMP) AS DATE) - CAST(SYSTIMESTAMP AS DATE)';
        $epoch_date = 'to_date(\'19700101\', \'YYYYMMDD\')';
        return '(CAST('.$expression.' AS DATE) - '.$epoch_date.' + '.$utc_offset.') * 86400 seconds';
    }

    // }}}
    // {{{ substring()

    /**
     * return string to call a function to get a substring inside an SQL statement
     *
     * @return string to call a function to get a substring
     * @access public
     */
    function substring($value, $position = 1, $length = null)
    {
        if (null !== $length) {
            return "SUBSTR($value, $position, $length)";
        }
        return "SUBSTR($value, $position)";
    }

    // }}}
    // {{{ random()

    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return return string to generate float between 0 and 1
     * @access public
     */
    function random()
    {
        return 'dbms_random.value';
    }

    // }}}}
    // {{{ guid()

    /**
     * Returns global unique identifier
     *
     * @return string to get global unique identifier
     * @access public
     */
    function guid()
    {
        return 'SYS_GUID()';
    }

    // }}}}
}
?>
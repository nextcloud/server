<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2011 Robin Appelman icewind1991@gmail.com
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

require_once('MDB2/Driver/Function/Common.php');

/**
 * MDB2 SQLite driver for the function modules
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Function_sqlite3 extends MDB2_Driver_Function_Common
{
    // {{{ constructor

    /**
     * Constructor
     */
    function __construct($db_index)
    {
        parent::__construct($db_index);
        // create all sorts of UDFs
    }

    // {{{ now()

    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time.
     *
     * @return string to call a variable with the current timestamp
     * @access public
     */
    function now($type = 'timestamp')
    {
        switch ($type) {
        case 'time':
            return 'time(\'now\')';
        case 'date':
            return 'date(\'now\')';
        case 'timestamp':
        default:
            return 'datetime(\'now\')';
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
        return 'strftime("%s",'. $expression.', "utc")';
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
        if (!is_null($length)) {
            return "substr($value,$position,$length)";
        }
        return "substr($value,$position,length($value))";
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
        return '((RANDOM()+2147483648)/4294967296)';
    }

    // }}}
    // {{{ replace()

    /**
     * return string to call a function to get a replacement inside an SQL statement.
     *
     * @return string to call a function to get a replace
     * @access public
     */
    function replace($str, $from_str, $to_str)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $error =& $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
        return $error;
    }

    // }}}
}
?>

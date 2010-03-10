<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Provides an object interface to a table row
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Database
 * @package    DB
 * @author     Stig Bakken <stig@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: storage.php,v 1.21 2005/02/02 02:54:51 danielc Exp $
 * @link       http://pear.php.net/package/DB
 */

/**
 * Obtain the DB class so it can be extended from
 */
require_once 'DB.php';

/**
 * Provides an object interface to a table row
 *
 * It lets you add, delete and change rows using objects rather than SQL
 * statements.
 *
 * @category   Database
 * @package    DB
 * @author     Stig Bakken <stig@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 */
class DB_storage extends PEAR
{
    // {{{ properties

    /** the name of the table (or view, if the backend database supports
        updates in views) we hold data from */
    var $_table = null;

    /** which column(s) in the table contains primary keys, can be a
        string for single-column primary keys, or an array of strings
        for multiple-column primary keys */
    var $_keycolumn = null;

    /** DB connection handle used for all transactions */
    var $_dbh = null;

    /** an assoc with the names of database fields stored as properties
        in this object */
    var $_properties = array();

    /** an assoc with the names of the properties in this object that
        have been changed since they were fetched from the database */
    var $_changes = array();

    /** flag that decides if data in this object can be changed.
        objects that don't have their table's key column in their
        property lists will be flagged as read-only. */
    var $_readonly = false;

    /** function or method that implements a validator for fields that
        are set, this validator function returns true if the field is
        valid, false if not */
    var $_validator = null;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     *
     * @param $table string the name of the database table
     *
     * @param $keycolumn mixed string with name of key column, or array of
     * strings if the table has a primary key of more than one column
     *
     * @param $dbh object database connection object
     *
     * @param $validator mixed function or method used to validate
     * each new value, called with three parameters: the name of the
     * field/column that is changing, a reference to the new value and
     * a reference to this object
     *
     */
    function DB_storage($table, $keycolumn, &$dbh, $validator = null)
    {
        $this->PEAR('DB_Error');
        $this->_table = $table;
        $this->_keycolumn = $keycolumn;
        $this->_dbh = $dbh;
        $this->_readonly = false;
        $this->_validator = $validator;
    }

    // }}}
    // {{{ _makeWhere()

    /**
     * Utility method to build a "WHERE" clause to locate ourselves in
     * the table.
     *
     * XXX future improvement: use rowids?
     *
     * @access private
     */
    function _makeWhere($keyval = null)
    {
        if (is_array($this->_keycolumn)) {
            if ($keyval === null) {
                for ($i = 0; $i < sizeof($this->_keycolumn); $i++) {
                    $keyval[] = $this->{$this->_keycolumn[$i]};
                }
            }
            $whereclause = '';
            for ($i = 0; $i < sizeof($this->_keycolumn); $i++) {
                if ($i > 0) {
                    $whereclause .= ' AND ';
                }
                $whereclause .= $this->_keycolumn[$i];
                if (is_null($keyval[$i])) {
                    // there's not much point in having a NULL key,
                    // but we support it anyway
                    $whereclause .= ' IS NULL';
                } else {
                    $whereclause .= ' = ' . $this->_dbh->quote($keyval[$i]);
                }
            }
        } else {
            if ($keyval === null) {
                $keyval = @$this->{$this->_keycolumn};
            }
            $whereclause = $this->_keycolumn;
            if (is_null($keyval)) {
                // there's not much point in having a NULL key,
                // but we support it anyway
                $whereclause .= ' IS NULL';
            } else {
                $whereclause .= ' = ' . $this->_dbh->quote($keyval);
            }
        }
        return $whereclause;
    }

    // }}}
    // {{{ setup()

    /**
     * Method used to initialize a DB_storage object from the
     * configured table.
     *
     * @param $keyval mixed the key[s] of the row to fetch (string or array)
     *
     * @return int DB_OK on success, a DB error if not
     */
    function setup($keyval)
    {
        $whereclause = $this->_makeWhere($keyval);
        $query = 'SELECT * FROM ' . $this->_table . ' WHERE ' . $whereclause;
        $sth = $this->_dbh->query($query);
        if (DB::isError($sth)) {
            return $sth;
        }
        $row = $sth->fetchRow(DB_FETCHMODE_ASSOC);
        if (DB::isError($row)) {
            return $row;
        }
        if (!$row) {
            return $this->raiseError(null, DB_ERROR_NOT_FOUND, null, null,
                                     $query, null, true);
        }
        foreach ($row as $key => $value) {
            $this->_properties[$key] = true;
            $this->$key = $value;
        }
        return DB_OK;
    }

    // }}}
    // {{{ insert()

    /**
     * Create a new (empty) row in the configured table for this
     * object.
     */
    function insert($newpk)
    {
        if (is_array($this->_keycolumn)) {
            $primarykey = $this->_keycolumn;
        } else {
            $primarykey = array($this->_keycolumn);
        }
        settype($newpk, "array");
        for ($i = 0; $i < sizeof($primarykey); $i++) {
            $pkvals[] = $this->_dbh->quote($newpk[$i]);
        }

        $sth = $this->_dbh->query("INSERT INTO $this->_table (" .
                                  implode(",", $primarykey) . ") VALUES(" .
                                  implode(",", $pkvals) . ")");
        if (DB::isError($sth)) {
            return $sth;
        }
        if (sizeof($newpk) == 1) {
            $newpk = $newpk[0];
        }
        $this->setup($newpk);
    }

    // }}}
    // {{{ toString()

    /**
     * Output a simple description of this DB_storage object.
     * @return string object description
     */
    function toString()
    {
        $info = strtolower(get_class($this));
        $info .= " (table=";
        $info .= $this->_table;
        $info .= ", keycolumn=";
        if (is_array($this->_keycolumn)) {
            $info .= "(" . implode(",", $this->_keycolumn) . ")";
        } else {
            $info .= $this->_keycolumn;
        }
        $info .= ", dbh=";
        if (is_object($this->_dbh)) {
            $info .= $this->_dbh->toString();
        } else {
            $info .= "null";
        }
        $info .= ")";
        if (sizeof($this->_properties)) {
            $info .= " [loaded, key=";
            $keyname = $this->_keycolumn;
            if (is_array($keyname)) {
                $info .= "(";
                for ($i = 0; $i < sizeof($keyname); $i++) {
                    if ($i > 0) {
                        $info .= ",";
                    }
                    $info .= $this->$keyname[$i];
                }
                $info .= ")";
            } else {
                $info .= $this->$keyname;
            }
            $info .= "]";
        }
        if (sizeof($this->_changes)) {
            $info .= " [modified]";
        }
        return $info;
    }

    // }}}
    // {{{ dump()

    /**
     * Dump the contents of this object to "standard output".
     */
    function dump()
    {
        foreach ($this->_properties as $prop => $foo) {
            print "$prop = ";
            print htmlentities($this->$prop);
            print "<br />\n";
        }
    }

    // }}}
    // {{{ &create()

    /**
     * Static method used to create new DB storage objects.
     * @param $data assoc. array where the keys are the names
     *              of properties/columns
     * @return object a new instance of DB_storage or a subclass of it
     */
    function &create($table, &$data)
    {
        $classname = strtolower(get_class($this));
        $obj =& new $classname($table);
        foreach ($data as $name => $value) {
            $obj->_properties[$name] = true;
            $obj->$name = &$value;
        }
        return $obj;
    }

    // }}}
    // {{{ loadFromQuery()

    /**
     * Loads data into this object from the given query.  If this
     * object already contains table data, changes will be saved and
     * the object re-initialized first.
     *
     * @param $query SQL query
     *
     * @param $params parameter list in case you want to use
     * prepare/execute mode
     *
     * @return int DB_OK on success, DB_WARNING_READ_ONLY if the
     * returned object is read-only (because the object's specified
     * key column was not found among the columns returned by $query),
     * or another DB error code in case of errors.
     */
// XXX commented out for now
/*
    function loadFromQuery($query, $params = null)
    {
        if (sizeof($this->_properties)) {
            if (sizeof($this->_changes)) {
                $this->store();
                $this->_changes = array();
            }
            $this->_properties = array();
        }
        $rowdata = $this->_dbh->getRow($query, DB_FETCHMODE_ASSOC, $params);
        if (DB::isError($rowdata)) {
            return $rowdata;
        }
        reset($rowdata);
        $found_keycolumn = false;
        while (list($key, $value) = each($rowdata)) {
            if ($key == $this->_keycolumn) {
                $found_keycolumn = true;
            }
            $this->_properties[$key] = true;
            $this->$key = &$value;
            unset($value); // have to unset, or all properties will
                           // refer to the same value
        }
        if (!$found_keycolumn) {
            $this->_readonly = true;
            return DB_WARNING_READ_ONLY;
        }
        return DB_OK;
    }
 */

    // }}}
    // {{{ set()

    /**
     * Modify an attriute value.
     */
    function set($property, $newvalue)
    {
        // only change if $property is known and object is not
        // read-only
        if ($this->_readonly) {
            return $this->raiseError(null, DB_WARNING_READ_ONLY, null,
                                     null, null, null, true);
        }
        if (@isset($this->_properties[$property])) {
            if (empty($this->_validator)) {
                $valid = true;
            } else {
                $valid = @call_user_func($this->_validator,
                                         $this->_table,
                                         $property,
                                         $newvalue,
                                         $this->$property,
                                         $this);
            }
            if ($valid) {
                $this->$property = $newvalue;
                if (empty($this->_changes[$property])) {
                    $this->_changes[$property] = 0;
                } else {
                    $this->_changes[$property]++;
                }
            } else {
                return $this->raiseError(null, DB_ERROR_INVALID, null,
                                         null, "invalid field: $property",
                                         null, true);
            }
            return true;
        }
        return $this->raiseError(null, DB_ERROR_NOSUCHFIELD, null,
                                 null, "unknown field: $property",
                                 null, true);
    }

    // }}}
    // {{{ &get()

    /**
     * Fetch an attribute value.
     *
     * @param string attribute name
     *
     * @return attribute contents, or null if the attribute name is
     * unknown
     */
    function &get($property)
    {
        // only return if $property is known
        if (isset($this->_properties[$property])) {
            return $this->$property;
        }
        $tmp = null;
        return $tmp;
    }

    // }}}
    // {{{ _DB_storage()

    /**
     * Destructor, calls DB_storage::store() if there are changes
     * that are to be kept.
     */
    function _DB_storage()
    {
        if (sizeof($this->_changes)) {
            $this->store();
        }
        $this->_properties = array();
        $this->_changes = array();
        $this->_table = null;
    }

    // }}}
    // {{{ store()

    /**
     * Stores changes to this object in the database.
     *
     * @return DB_OK or a DB error
     */
    function store()
    {
        foreach ($this->_changes as $name => $foo) {
            $params[] = &$this->$name;
            $vars[] = $name . ' = ?';
        }
        if ($vars) {
            $query = 'UPDATE ' . $this->_table . ' SET ' .
                implode(', ', $vars) . ' WHERE ' .
                $this->_makeWhere();
            $stmt = $this->_dbh->prepare($query);
            $res = $this->_dbh->execute($stmt, $params);
            if (DB::isError($res)) {
                return $res;
            }
            $this->_changes = array();
        }
        return DB_OK;
    }

    // }}}
    // {{{ remove()

    /**
     * Remove the row represented by this object from the database.
     *
     * @return mixed DB_OK or a DB error
     */
    function remove()
    {
        if ($this->_readonly) {
            return $this->raiseError(null, DB_WARNING_READ_ONLY, null,
                                     null, null, null, true);
        }
        $query = 'DELETE FROM ' . $this->_table .' WHERE '.
            $this->_makeWhere();
        $res = $this->_dbh->query($query);
        if (DB::isError($res)) {
            return $res;
        }
        foreach ($this->_properties as $prop => $foo) {
            unset($this->$prop);
        }
        $this->_properties = array();
        $this->_changes = array();
        return DB_OK;
    }

    // }}}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */

?>

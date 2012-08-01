<?php

/**
 * Storage container for the oauth credentials, both server and consumer side.
 * Based on MySQL
 * 
 * @version $Id: OAuthStoreMySQL.php 85 2010-02-19 14:56:40Z brunobg@corollarium.com $
 * @author Marc Worrell <marcw@pobox.com>
 * @date  Nov 16, 2007 4:03:30 PM
 * 
 * 
 * The MIT License
 * 
 * Copyright (c) 2007-2008 Mediamatic Lab
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */


require_once dirname(__FILE__) . '/OAuthStoreSQL.php';


class OAuthStoreMySQL extends OAuthStoreSQL
{
	/**
	 * The MySQL connection 
	 */
	protected $conn;

	/**
	 * Initialise the database
	 */
	public function install ()
	{
		require_once dirname(__FILE__) . '/mysql/install.php';
	}
	
	
	/* ** Some simple helper functions for querying the mysql db ** */

	/**
	 * Perform a query, ignore the results
	 * 
	 * @param string sql
	 * @param vararg arguments (for sprintf)
	 */
	protected function query ( $sql )
	{
		$sql = $this->sql_printf(func_get_args());
		if (!($res = mysql_query($sql, $this->conn)))
		{
			$this->sql_errcheck($sql);
		}
		if (is_resource($res))
		{
			mysql_free_result($res);
		}
	}
	

	/**
	 * Perform a query, ignore the results
	 * 
	 * @param string sql
	 * @param vararg arguments (for sprintf)
	 * @return array
	 */
	protected function query_all_assoc ( $sql )
	{
		$sql = $this->sql_printf(func_get_args());
		if (!($res = mysql_query($sql, $this->conn)))
		{
			$this->sql_errcheck($sql);
		}
		$rs = array();
		while ($row  = mysql_fetch_assoc($res))
		{
			$rs[] = $row;
		}
		mysql_free_result($res);
		return $rs;
	}
	
	
	/**
	 * Perform a query, return the first row
	 * 
	 * @param string sql
	 * @param vararg arguments (for sprintf)
	 * @return array
	 */
	protected function query_row_assoc ( $sql )
	{
		$sql = $this->sql_printf(func_get_args());
		if (!($res = mysql_query($sql, $this->conn)))
		{
			$this->sql_errcheck($sql);
		}
		if ($row = mysql_fetch_assoc($res))
		{
			$rs = $row;
		}
		else
		{
			$rs = false;
		}
		mysql_free_result($res);
		return $rs;
	}

	
	/**
	 * Perform a query, return the first row
	 * 
	 * @param string sql
	 * @param vararg arguments (for sprintf)
	 * @return array
	 */
	protected function query_row ( $sql )
	{
		$sql = $this->sql_printf(func_get_args());
		if (!($res = mysql_query($sql, $this->conn)))
		{
			$this->sql_errcheck($sql);
		}
		if ($row = mysql_fetch_array($res))
		{
			$rs = $row;
		}
		else
		{
			$rs = false;
		}
		mysql_free_result($res);
		return $rs;
	}
	
		
	/**
	 * Perform a query, return the first column of the first row
	 * 
	 * @param string sql
	 * @param vararg arguments (for sprintf)
	 * @return mixed
	 */
	protected function query_one ( $sql )
	{
		$sql = $this->sql_printf(func_get_args());
		if (!($res = mysql_query($sql, $this->conn)))
		{
			$this->sql_errcheck($sql);
		}
		$val = @mysql_result($res, 0, 0);
		mysql_free_result($res);
		return $val;
	}
	
	
	/**
	 * Return the number of rows affected in the last query
	 */
	protected function query_affected_rows ()
	{
		return mysql_affected_rows($this->conn);
	}


	/**
	 * Return the id of the last inserted row
	 * 
	 * @return int
	 */
	protected function query_insert_id ()
	{
		return mysql_insert_id($this->conn);
	}
	
	
	protected function sql_printf ( $args )
	{
		$sql  = array_shift($args);
		if (count($args) == 1 && is_array($args[0]))
		{
			$args = $args[0];
		}
		$args = array_map(array($this, 'sql_escape_string'), $args);
		return vsprintf($sql, $args);
	}
	
	
	protected function sql_escape_string ( $s )
	{
		if (is_string($s))
		{
			return mysql_real_escape_string($s, $this->conn);
		}
		else if (is_null($s))
		{
			return NULL;
		}
		else if (is_bool($s))
		{
			return intval($s);
		}
		else if (is_int($s) || is_float($s))
		{
			return $s;
		}
		else
		{
			return mysql_real_escape_string(strval($s), $this->conn);
		}
	}
	
	
	protected function sql_errcheck ( $sql )
	{
		if (mysql_errno($this->conn))
		{
			$msg =  "SQL Error in OAuthStoreMySQL: ".mysql_error($this->conn)."\n\n" . $sql;
			throw new OAuthException2($msg);
		}
	}
}


/* vi:set ts=4 sts=4 sw=4 binary noeol: */

?>
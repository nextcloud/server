<?php

/**
 * Storage container for the oauth credentials, both server and consumer side.
 * Based on MySQL
 * 
 * @version $Id: OAuthStoreMySQLi.php 64 2009-08-16 19:37:00Z marcw@pobox.com $
 * @author Bruno Barberi Gnecco <brunobg@users.sf.net> Based on code by Marc Worrell <marcw@pobox.com>
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

/*
 * Modified from OAuthStoreMySQL to support MySQLi
 */

require_once dirname(__FILE__) . '/OAuthStoreMySQL.php';


class OAuthStoreMySQLi extends OAuthStoreMySQL
{
	
	public function install() {
		$sql = file_get_contents(dirname(__FILE__) . '/mysql/mysql.sql');
		$ps  = explode('#--SPLIT--', $sql);
		
		foreach ($ps as $p)
		{
			$p = preg_replace('/^\s*#.*$/m', '', $p);
			
			$this->query($p);
			$this->sql_errcheck($p);
		}		
	}

	/**
	 * Construct the OAuthStoreMySQLi.
	 * In the options you have to supply either:
	 * - server, username, password and database (for a mysqli_connect)
	 * - conn (for the connection to be used)
	 * 
	 * @param array options
	 */
	function __construct ( $options = array() )
	{
		if (isset($options['conn']))
		{
			$this->conn = $options['conn'];
		}
		else
		{
			if (isset($options['server']))
			{
				$server   = $options['server'];
				$username = $options['username'];
				
				if (isset($options['password']))
				{
					$this->conn = ($GLOBALS["___mysqli_ston"] = mysqli_connect($server,  $username,  $options['password']));
				}
				else
				{
					$this->conn = ($GLOBALS["___mysqli_ston"] = mysqli_connect($server,  $username));
				}
			}
			else
			{
				// Try the default mysql connect
				$this->conn = ($GLOBALS["___mysqli_ston"] = mysqli_connect());
			}

			if ($this->conn === false)
			{
				throw new OAuthException2('Could not connect to MySQL database: ' . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
			}

			if (isset($options['database']))
			{
				/* TODO: security. mysqli_ doesn't seem to have an escape identifier function.
				$escapeddb = mysqli_real_escape_string($options['database']);
				if (!((bool)mysqli_query( $this->conn, "USE `$escapeddb`" )))
				{
					$this->sql_errcheck();
				}*/
			}
			$this->query('set character set utf8');
		}
	}

	/**
	 * Perform a query, ignore the results
	 * 
	 * @param string sql
	 * @param vararg arguments (for sprintf)
	 */
	protected function query ( $sql )
	{
		$sql = $this->sql_printf(func_get_args());
		if (!($res = mysqli_query( $this->conn, $sql)))
		{
			$this->sql_errcheck($sql);
		}
		if (!is_bool($res))
		{
			((mysqli_free_result($res) || (is_object($res) && (get_class($res) == "mysqli_result"))) ? true : false);
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
		if (!($res = mysqli_query( $this->conn, $sql)))
		{
			$this->sql_errcheck($sql);
		}
		$rs = array();
		while ($row  = mysqli_fetch_assoc($res))
		{
			$rs[] = $row;
		}
		((mysqli_free_result($res) || (is_object($res) && (get_class($res) == "mysqli_result"))) ? true : false);
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
		if (!($res = mysqli_query( $this->conn, $sql)))
		{
			$this->sql_errcheck($sql);
		}
		if ($row = mysqli_fetch_assoc($res))
		{
			$rs = $row;
		}
		else
		{
			$rs = false;
		}
		((mysqli_free_result($res) || (is_object($res) && (get_class($res) == "mysqli_result"))) ? true : false);
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
		if (!($res = mysqli_query( $this->conn, $sql)))
		{
			$this->sql_errcheck($sql);
		}
		if ($row = mysqli_fetch_array($res))
		{
			$rs = $row;
		}
		else
		{
			$rs = false;
		}
		((mysqli_free_result($res) || (is_object($res) && (get_class($res) == "mysqli_result"))) ? true : false);
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
		if (!($res = mysqli_query( $this->conn, $sql)))
		{
			$this->sql_errcheck($sql);
		}
		if ($row = mysqli_fetch_assoc($res))
		{
			$val = array_pop($row);
		}
		else
		{
			$val = false;
		}
		((mysqli_free_result($res) || (is_object($res) && (get_class($res) == "mysqli_result"))) ? true : false);
		return $val;
	}
	
	
	/**
	 * Return the number of rows affected in the last query
	 */
	protected function query_affected_rows ()
	{
		return mysqli_affected_rows($this->conn);
	}


	/**
	 * Return the id of the last inserted row
	 * 
	 * @return int
	 */
	protected function query_insert_id ()
	{
		return ((is_null($___mysqli_res = mysqli_insert_id($this->conn))) ? false : $___mysqli_res);
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
			return mysqli_real_escape_string( $this->conn, $s);
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
			return mysqli_real_escape_string( $this->conn, strval($s));
		}
	}
	
	
	protected function sql_errcheck ( $sql )
	{
		if (((is_object($this->conn)) ? mysqli_errno($this->conn) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false)))
		{
			$msg =  "SQL Error in OAuthStoreMySQL: ".((is_object($this->conn)) ? mysqli_error($this->conn) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false))."\n\n" . $sql;
			throw new OAuthException2($msg);
		}
	}
}


/* vi:set ts=4 sts=4 sw=4 binary noeol: */

?>
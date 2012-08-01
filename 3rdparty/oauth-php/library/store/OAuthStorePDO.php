<?php

/**
 * Storage container for the oauth credentials, both server and consumer side.
 * Based on MySQL
 * 
 * @version $Id: OAuthStorePDO.php 64 2009-08-16 19:37:00Z marcw@pobox.com $
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

require_once dirname(__FILE__) . '/OAuthStoreSQL.php';


class OAuthStorePDO extends OAuthStoreSQL
{
	private $conn; // PDO connection
	private $lastaffectedrows;

	/**
	 * Construct the OAuthStorePDO.
	 * In the options you have to supply either:
	 * - dsn, username, password and database (for a new PDO connection)
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
		else if (isset($options['dsn']))
		{
			try 
			{
				$this->conn = new PDO($options['dsn'], $options['username'], @$options['password']);
			}
			catch (PDOException $e) 
			{
				throw new OAuthException2('Could not connect to PDO database: ' . $e->getMessage());
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
		try
		{
			$this->lastaffectedrows = $this->conn->exec($sql);
			if ($this->lastaffectedrows === FALSE) {
				$this->sql_errcheck($sql);
			}
		}
		catch (PDOException $e) 
		{
			$this->sql_errcheck($sql);
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
		$result = array();

		try 
		{
			$stmt = $this->conn->query($sql);
			
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch (PDOException $e) 
		{
			$this->sql_errcheck($sql);
		}
		return $result;
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
		$result = $this->query_all_assoc($sql);
		$val = array_pop($result);
		return $val;
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
		try 
		{
			$all = $this->conn->query($sql, PDO::FETCH_NUM);
			$row = array();
			foreach ($all as $r) {
				$row = $r;
				break;
			}
		}
		catch (PDOException $e)
		{
			$this->sql_errcheck($sql);
		}
		return $row;
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
		$row = $this->query_row($sql); 
		$val = array_pop($row);
		return $val;
	}
	
	
	/**
	 * Return the number of rows affected in the last query
	 */
	protected function query_affected_rows ()
	{
		return $this->lastaffectedrows;
	}


	/**
	 * Return the id of the last inserted row
	 * 
	 * @return int
	 */
	protected function query_insert_id ()
	{
		return $this->conn->lastInsertId();
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
			$s = $this->conn->quote($s);
			// kludge. Quote already adds quotes, and this conflicts with OAuthStoreSQL.
			// so remove the quotes
			$len = mb_strlen($s);
			if ($len == 0)
				return $s;

			$startcut = 0;
			while (isset($s[$startcut]) && $s[$startcut] == '\'')
				$startcut++;

			$endcut = $len-1;
			while (isset($s[$endcut]) && $s[$endcut] == '\'')
				$endcut--;
				
			$s = mb_substr($s, $startcut, $endcut-$startcut+1);
			return $s;
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
			return $this->conn->quote(strval($s));
		}
	}
	
	
	protected function sql_errcheck ( $sql )
	{
		$msg =  "SQL Error in OAuthStoreMySQL: ". print_r($this->conn->errorInfo(), true) ."\n\n" . $sql;
		$backtrace = debug_backtrace();
		$msg .=  "\n\nAt file " . $backtrace[1]['file'] . ", line "  . $backtrace[1]['line']; 
		throw new OAuthException2($msg);
	}
	
	/**
	* Initialise the database
	*/
	public function install ()
	{
		// TODO: this depends on mysql extension
		require_once dirname(__FILE__) . '/mysql/install.php';
	}
	
}


/* vi:set ts=4 sts=4 sw=4 binary noeol: */

?>
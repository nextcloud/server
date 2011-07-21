<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// extension.cache.mysql.php - part of getID3()                //
// Please see readme.txt for more information                  //
//                                                            ///
/////////////////////////////////////////////////////////////////
//                                                             //
// This extension written by Allan Hansen <ahØartemis*dk>      //
//                                                            ///
/////////////////////////////////////////////////////////////////


/**
* This is a caching extension for getID3(). It works the exact same
* way as the getID3 class, but return cached information very fast
*
* Example:  (see also demo.cache.mysql.php in /demo/)
*
*    Normal getID3 usage (example):
*
*       require_once 'getid3/getid3.php';
*       $getID3 = new getID3;
*       $getID3->encoding = 'UTF-8';
*       $info1 = $getID3->analyze('file1.flac');
*       $info2 = $getID3->analyze('file2.wv');
*
*    getID3_cached usage:
*
*       require_once 'getid3/getid3.php';
*       require_once 'getid3/getid3/extension.cache.mysql.php';
*       $getID3 = new getID3_cached_mysql('localhost', 'database',
*                                         'username', 'password');
*       $getID3->encoding = 'UTF-8';
*       $info1 = $getID3->analyze('file1.flac');
*       $info2 = $getID3->analyze('file2.wv');
*
*
* Supported Cache Types    (this extension)
*
*   SQL Databases:
*
*   cache_type          cache_options
*   -------------------------------------------------------------------
*   mysql               host, database, username, password
*
*
*   DBM-Style Databases:    (use extension.cache.dbm)
*
*   cache_type          cache_options
*   -------------------------------------------------------------------
*   gdbm                dbm_filename, lock_filename
*   ndbm                dbm_filename, lock_filename
*   db2                 dbm_filename, lock_filename
*   db3                 dbm_filename, lock_filename
*   db4                 dbm_filename, lock_filename  (PHP5 required)
*
*   PHP must have write access to both dbm_filename and lock_filename.
*
*
* Recommended Cache Types
*
*   Infrequent updates, many reads      any DBM
*   Frequent updates                    mysql
*/


class getID3_cached_mysql extends getID3
{

	// private vars
	var $cursor;
	var $connection;


	// public: constructor - see top of this file for cache type and cache_options
	function getID3_cached_mysql($host, $database, $username, $password) {

		// Check for mysql support
		if (!function_exists('mysql_pconnect')) {
			die('PHP not compiled with mysql support.');
		}

		// Connect to database
		$this->connection = mysql_pconnect($host, $username, $password);
		if (!$this->connection) {
			die('mysql_pconnect() failed - check permissions and spelling.');
		}

		// Select database
		if (!mysql_select_db($database, $this->connection)) {
			die('Cannot use database '.$database);
		}

		// Create cache table if not exists
		$this->create_table();

		// Check version number and clear cache if changed
		$this->cursor = mysql_query("SELECT `value` FROM `getid3_cache` WHERE (`filename` = '".GETID3_VERSION."') AND (`filesize` = '-1') AND (`filetime` = '-1') AND (`analyzetime` = '-1')", $this->connection);
		list($version) = @mysql_fetch_array($this->cursor);
		if ($version != GETID3_VERSION) {
			$this->clear_cache();
		}

		parent::getID3();
	}



	// public: clear cache
	function clear_cache() {

		$this->cursor = mysql_query("DELETE FROM `getid3_cache`", $this->connection);
		$this->cursor = mysql_query("INSERT INTO `getid3_cache` VALUES ('".GETID3_VERSION."', -1, -1, -1, '".GETID3_VERSION."')", $this->connection);
	}



	// public: analyze file
	function analyze($filename) {

		if (file_exists($filename)) {

			// Short-hands
			$filetime = filemtime($filename);
			$filesize = filesize($filename);
			$filenam2 = mysql_escape_string($filename);

			// Loopup file
			$this->cursor = mysql_query("SELECT `value` FROM `getid3_cache` WHERE (`filename`='".$filenam2."') AND (`filesize`='".$filesize."') AND (`filetime`='".$filetime."')", $this->connection);
			list($result) = @mysql_fetch_array($this->cursor);

			// Hit
			if ($result) {
				return unserialize($result);
			}
		}

		// Miss
		$result = parent::analyze($filename);

		// Save result
		if (file_exists($filename)) {
			$res2 = mysql_escape_string(serialize($result));
			$this->cursor = mysql_query("INSERT INTO `getid3_cache` (`filename`, `filesize`, `filetime`, `analyzetime`, `value`) VALUES ('".$filenam2."', '".$filesize."', '".$filetime."', '".time()."', '".$res2."')", $this->connection);
		}
		return $result;
	}



	// private: (re)create sql table
	function create_table($drop = false) {

		$this->cursor = mysql_query("CREATE TABLE IF NOT EXISTS `getid3_cache` (
			`filename` VARCHAR(255) NOT NULL DEFAULT '',
			`filesize` INT(11) NOT NULL DEFAULT '0',
			`filetime` INT(11) NOT NULL DEFAULT '0',
			`analyzetime` INT(11) NOT NULL DEFAULT '0',
			`value` TEXT NOT NULL,
			PRIMARY KEY (`filename`,`filesize`,`filetime`)) TYPE=MyISAM", $this->connection);
		echo mysql_error($this->connection);
	}
}


?>
<?php
/**
 * Container for all PDO-based cache methods. Inherits additional methods from <CacheCore>. Adheres
 * to the ICacheCore interface.
 *
 * @version 2012.04.17
 * @copyright 2006-2012 Ryan Parman
 * @copyright 2006-2010 Foleeo, Inc.
 * @copyright 2012 Amazon.com, Inc. or its affiliates.
 * @copyright 2008-2010 Contributors
 * @license http://opensource.org/licenses/bsd-license.php Simplified BSD License
 * @link http://github.com/skyzyx/cachecore CacheCore
 * @link http://getcloudfusion.com CloudFusion
 * @link http://php.net/pdo PDO
 */
class CachePDO extends CacheCore implements ICacheCore
{
	/**
	 * Reference to the PDO connection object.
	 */
	var $pdo = null;

	/**
	 * Holds the parsed URL components.
	 */
	var $dsn = null;

	/**
	 * Holds the PDO-friendly version of the connection string.
	 */
	var $dsn_string = null;

	/**
	 * Holds the prepared statement for creating an entry.
	 */
	var $create = null;

	/**
	 * Holds the prepared statement for reading an entry.
	 */
	var $read = null;

	/**
	 * Holds the prepared statement for updating an entry.
	 */
	var $update = null;

	/**
	 * Holds the prepared statement for resetting the expiry of an entry.
	 */
	var $reset = null;

	/**
	 * Holds the prepared statement for deleting an entry.
	 */
	var $delete = null;

	/**
	 * Holds the response of the read so we only need to fetch it once instead of doing
	 * multiple queries.
	 */
	var $store_read = null;


	/*%******************************************************************************************%*/
	// CONSTRUCTOR

	/**
	 * Constructs a new instance of this class.
	 *
	 * Tested with [MySQL 5.0.x](http://mysql.com), [PostgreSQL](http://postgresql.com), and
	 * [SQLite 3.x](http://sqlite.org). SQLite 2.x is assumed to work. No other PDO-supported databases have
	 * been tested (e.g. Oracle, Microsoft SQL Server, IBM DB2, ODBC, Sybase, Firebird). Feel free to send
	 * patches for additional database support.
	 *
	 * See <http://php.net/pdo> for more information.
	 *
	 * @param string $name (Required) A name to uniquely identify the cache object.
	 * @param string $location (Optional) The location to store the cache object in. This may vary by cache method. The default value is NULL.
	 * @param integer $expires (Optional) The number of seconds until a cache object is considered stale. The default value is 0.
	 * @param boolean $gzip (Optional) Whether data should be gzipped before being stored. The default value is true.
	 * @return object Reference to the cache object.
	 */
	public function __construct($name, $location = null, $expires = 0, $gzip = true)
	{
		// Make sure the name is no longer than 40 characters.
		$name = sha1($name);

		// Call parent constructor and set id.
		parent::__construct($name, $location, $expires, $gzip);
		$this->id = $this->name;
		$options = array();

		// Check if the location contains :// (e.g. mysql://user:pass@hostname:port/table)
		if (stripos($location, '://') === false)
		{
			// No? Just pass it through.
			$this->dsn = parse_url($location);
			$this->dsn_string = $location;
		}
		else
		{
			// Yes? Parse and set the DSN
			$this->dsn = parse_url($location);
			$this->dsn_string = $this->dsn['scheme'] . ':host=' . $this->dsn['host'] . ((isset($this->dsn['port'])) ? ';port=' . $this->dsn['port'] : '') . ';dbname=' . substr($this->dsn['path'], 1);
		}

		// Make sure that user/pass are defined.
		$user = isset($this->dsn['user']) ? $this->dsn['user'] : null;
		$pass = isset($this->dsn['pass']) ? $this->dsn['pass'] : null;

		// Set persistence for databases that support it.
		switch ($this->dsn['scheme'])
		{
			case 'mysql': // MySQL
			case 'pgsql': // PostgreSQL
				$options[PDO::ATTR_PERSISTENT] = true;
				break;
		}

		// Instantiate a new PDO object with a persistent connection.
		$this->pdo = new PDO($this->dsn_string, $user, $pass, $options);

		// Define prepared statements for improved performance.
		$this->create = $this->pdo->prepare("INSERT INTO cache (id, expires, data) VALUES (:id, :expires, :data)");
		$this->read = $this->pdo->prepare("SELECT id, expires, data FROM cache WHERE id = :id");
		$this->reset = $this->pdo->prepare("UPDATE cache SET expires = :expires WHERE id = :id");
		$this->delete = $this->pdo->prepare("DELETE FROM cache WHERE id = :id");
	}

	/**
	 * Creates a new cache.
	 *
	 * @param mixed $data (Required) The data to cache.
	 * @return boolean Whether the operation was successful.
	 */
	public function create($data)
	{
		$data = serialize($data);
		$data = $this->gzip ? gzcompress($data) : $data;

		$this->create->bindParam(':id', $this->id);
		$this->create->bindParam(':data', $data);
		$this->create->bindParam(':expires', $this->generate_timestamp());

		return (bool) $this->create->execute();
	}

	/**
	 * Reads a cache.
	 *
	 * @return mixed Either the content of the cache object, or boolean `false`.
	 */
	public function read()
	{
		if (!$this->store_read)
		{
			$this->read->bindParam(':id', $this->id);
			$this->read->execute();
			$this->store_read = $this->read->fetch(PDO::FETCH_ASSOC);
		}

		if ($this->store_read)
		{
			$data = $this->store_read['data'];
			$data = $this->gzip ? gzuncompress($data) : $data;

			return unserialize($data);
		}

		return false;
	}

	/**
	 * Updates an existing cache.
	 *
	 * @param mixed $data (Required) The data to cache.
	 * @return boolean Whether the operation was successful.
	 */
	public function update($data)
	{
		$this->delete();
		return $this->create($data);
	}

	/**
	 * Deletes a cache.
	 *
	 * @return boolean Whether the operation was successful.
	 */
	public function delete()
	{
		$this->delete->bindParam(':id', $this->id);
		return $this->delete->execute();
	}

	/**
	 * Checks whether the cache object is expired or not.
	 *
	 * @return boolean Whether the cache is expired or not.
	 */
	public function is_expired()
	{
		if ($this->timestamp() + $this->expires < time())
		{
			return true;
		}

		return false;
	}

	/**
	 * Retrieves the timestamp of the cache.
	 *
	 * @return mixed Either the Unix time stamp of the cache creation, or boolean `false`.
	 */
	public function timestamp()
	{
		if (!$this->store_read)
		{
			$this->read->bindParam(':id', $this->id);
			$this->read->execute();
			$this->store_read = $this->read->fetch(PDO::FETCH_ASSOC);
		}

		if ($this->store_read)
		{
			$value = $this->store_read['expires'];

			// If 'expires' isn't yet an integer, convert it into one.
			if (!is_numeric($value))
			{
				$value = strtotime($value);
			}

			$this->timestamp = date('U', $value);
			return $this->timestamp;
		}

		return false;
	}

	/**
	 * Resets the freshness of the cache.
	 *
	 * @return boolean Whether the operation was successful.
	 */
	public function reset()
	{
		$this->reset->bindParam(':id', $this->id);
		$this->reset->bindParam(':expires', $this->generate_timestamp());
		return (bool) $this->reset->execute();
	}

	/**
	 * Returns a list of supported PDO database drivers. Identical to <PDO::getAvailableDrivers()>.
	 *
	 * @return array The list of supported database drivers.
	 * @link http://php.net/pdo.getavailabledrivers PHP Method
	 */
	public function get_drivers()
	{
		return PDO::getAvailableDrivers();
	}

	/**
	 * Returns a timestamp value apropriate to the current database type.
	 *
	 * @return mixed Timestamp for MySQL and PostgreSQL, integer value for SQLite.
	 */
	protected function generate_timestamp()
	{
		// Define 'expires' settings differently.
		switch ($this->dsn['scheme'])
		{
			// These support timestamps.
			case 'mysql': // MySQL
			case 'pgsql': // PostgreSQL
				$expires = date(DATE_FORMAT_MYSQL, time());
				break;

			// These support integers.
			case 'sqlite': // SQLite 3
			case 'sqlite2': // SQLite 2
				$expires = time();
				break;
		}

		return $expires;
	}
}


/*%******************************************************************************************%*/
// EXCEPTIONS

class CachePDO_Exception extends CacheCore_Exception {}

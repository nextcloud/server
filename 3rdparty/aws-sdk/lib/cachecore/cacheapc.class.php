<?php
/**
 * Container for all APC-based cache methods. Inherits additional methods from <CacheCore>. Adheres
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
 * @link http://php.net/apc APC
 */
class CacheAPC extends CacheCore implements ICacheCore
{

	/*%******************************************************************************************%*/
	// CONSTRUCTOR

	/**
	 * Constructs a new instance of this class.
	 *
	 * @param string $name (Required) A name to uniquely identify the cache object.
	 * @param string $location (Optional) The location to store the cache object in. This may vary by cache method. The default value is NULL.
	 * @param integer $expires (Optional) The number of seconds until a cache object is considered stale. The default value is 0.
	 * @param boolean $gzip (Optional) Whether data should be gzipped before being stored. The default value is true.
	 * @return object Reference to the cache object.
	 */
	public function __construct($name, $location = null, $expires = 0, $gzip = true)
	{
		parent::__construct($name, null, $expires, $gzip);
		$this->id = $this->name;
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

		return apc_add($this->id, $data, $this->expires);
	}

	/**
	 * Reads a cache.
	 *
	 * @return mixed Either the content of the cache object, or boolean `false`.
	 */
	public function read()
	{
		if ($data = apc_fetch($this->id))
		{
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
		$data = serialize($data);
		$data = $this->gzip ? gzcompress($data) : $data;

		return apc_store($this->id, $data, $this->expires);
	}

	/**
	 * Deletes a cache.
	 *
	 * @return boolean Whether the operation was successful.
	 */
	public function delete()
	{
		return apc_delete($this->id);
	}

	/**
	 * Implemented here, but always returns `false`. APC manages its own expirations.
	 *
	 * @return boolean Whether the cache is expired or not.
	 */
	public function is_expired()
	{
		return false;
	}

	/**
	 * Implemented here, but always returns `false`. APC manages its own expirations.
	 *
	 * @return mixed Either the Unix time stamp of the cache creation, or boolean `false`.
	 */
	public function timestamp()
	{
		return false;
	}

	/**
	 * Implemented here, but always returns `false`. APC manages its own expirations.
	 *
	 * @return boolean Whether the operation was successful.
	 */
	public function reset()
	{
		return false;
	}
}


/*%******************************************************************************************%*/
// EXCEPTIONS

class CacheAPC_Exception extends CacheCore_Exception {}

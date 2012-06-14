<?php
/**
 * Container for all Memcache-based cache methods. Inherits additional methods from <CacheCore>. Adheres
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
 * @link http://php.net/memcache Memcache
 * @link http://php.net/memcached Memcached
 */
class CacheMC extends CacheCore implements ICacheCore
{
	/**
	 * Holds the Memcache object.
	 */
	var $memcache = null;

	/**
	 * Whether the Memcached extension is being used (as opposed to Memcache).
	 */
	var $is_memcached = false;


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

		// Prefer Memcached over Memcache.
		if (class_exists('Memcached'))
		{
			$this->memcache = new Memcached();
			$this->is_memcached = true;
		}
		elseif (class_exists('Memcache'))
		{
			$this->memcache = new Memcache();
		}
		else
		{
			return false;
		}

		// Enable compression, if available
		if ($this->gzip)
		{
			if ($this->is_memcached)
			{
				$this->memcache->setOption(Memcached::OPT_COMPRESSION, true);
			}
			else
			{
				$this->gzip = MEMCACHE_COMPRESSED;
			}
		}

		// Process Memcached servers.
		if (isset($location) && sizeof($location) > 0)
		{
			foreach ($location as $loc)
			{
				if (isset($loc['port']) && !empty($loc['port']))
				{
					$this->memcache->addServer($loc['host'], $loc['port']);
				}
				else
				{
					$this->memcache->addServer($loc['host'], 11211);
				}
			}
		}

		return $this;
	}

	/**
	 * Creates a new cache.
	 *
	 * @param mixed $data (Required) The data to cache.
	 * @return boolean Whether the operation was successful.
	 */
	public function create($data)
	{
		if ($this->is_memcached)
		{
			return $this->memcache->set($this->id, $data, $this->expires);
		}
		return $this->memcache->set($this->id, $data, $this->gzip, $this->expires);
	}

	/**
	 * Reads a cache.
	 *
	 * @return mixed Either the content of the cache object, or boolean `false`.
	 */
	public function read()
	{
		if ($this->is_memcached)
		{
			return $this->memcache->get($this->id);
		}
		return $this->memcache->get($this->id, $this->gzip);
	}

	/**
	 * Updates an existing cache.
	 *
	 * @param mixed $data (Required) The data to cache.
	 * @return boolean Whether the operation was successful.
	 */
	public function update($data)
	{
		if ($this->is_memcached)
		{
			return $this->memcache->replace($this->id, $data, $this->expires);
		}
		return $this->memcache->replace($this->id, $data, $this->gzip, $this->expires);
	}

	/**
	 * Deletes a cache.
	 *
	 * @return boolean Whether the operation was successful.
	 */
	public function delete()
	{
		return $this->memcache->delete($this->id);
	}

	/**
	 * Implemented here, but always returns `false`. Memcache manages its own expirations.
	 *
	 * @return boolean Whether the cache is expired or not.
	 */
	public function is_expired()
	{
		return false;
	}

	/**
	 * Implemented here, but always returns `false`. Memcache manages its own expirations.
	 *
	 * @return mixed Either the Unix time stamp of the cache creation, or boolean `false`.
	 */
	public function timestamp()
	{
		return false;
	}

	/**
	 * Implemented here, but always returns `false`. Memcache manages its own expirations.
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

class CacheMC_Exception extends CacheCore_Exception {}

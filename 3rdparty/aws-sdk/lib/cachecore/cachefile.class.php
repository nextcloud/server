<?php
/**
 * Container for all file-based cache methods. Inherits additional methods from <CacheCore>. Adheres
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
 */
class CacheFile extends CacheCore implements ICacheCore
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
		parent::__construct($name, $location, $expires, $gzip);
		$this->id = $this->location . '/' . $this->name . '.cache';
	}

	/**
	 * Creates a new cache.
	 *
	 * @param mixed $data (Required) The data to cache.
	 * @return boolean Whether the operation was successful.
	 */
	public function create($data)
	{
		if (file_exists($this->id))
		{
			return false;
		}
		elseif (realpath($this->location) && file_exists($this->location) && is_writeable($this->location))
		{
			$data = serialize($data);
			$data = $this->gzip ? gzcompress($data) : $data;

			return (bool) file_put_contents($this->id, $data);
		}
		elseif (realpath($this->location) && file_exists($this->location))
		{
			throw new CacheFile_Exception('The file system location "' . $this->location . '" is not writable. Check the file system permissions for this directory.');
		}
		else
		{
			throw new CacheFile_Exception('The file system location "' . $this->location . '" does not exist. Create the directory, or double-check any relative paths that may have been set.');
		}

		return false;
	}

	/**
	 * Reads a cache.
	 *
	 * @return mixed Either the content of the cache object, or boolean `false`.
	 */
	public function read()
	{
		if (file_exists($this->id) && is_readable($this->id))
		{
			$data = file_get_contents($this->id);
			$data = $this->gzip ? gzuncompress($data) : $data;
			$data = unserialize($data);

			if ($data === false)
			{
				/*
					This should only happen when someone changes the gzip settings and there is
					existing data or someone has been mucking about in the cache folder manually.
					Delete the bad entry since the file cache doesn't clean up after itself and
					then return false so fresh data will be retrieved.
				 */
				$this->delete();
				return false;
			}

			return $data;
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
		if (file_exists($this->id) && is_writeable($this->id))
		{
			$data = serialize($data);
			$data = $this->gzip ? gzcompress($data) : $data;

			return (bool) file_put_contents($this->id, $data);
		}
		else
		{
			throw new CacheFile_Exception('The file system location is not writeable. Check your file system permissions and ensure that the cache directory exists.');
		}

		return false;
	}

	/**
	 * Deletes a cache.
	 *
	 * @return boolean Whether the operation was successful.
	 */
	public function delete()
	{
		if (file_exists($this->id))
		{
			return unlink($this->id);
		}

		return false;
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
		clearstatcache();

		if (file_exists($this->id))
		{
			$this->timestamp = filemtime($this->id);
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
		if (file_exists($this->id))
		{
			return touch($this->id);
		}

		return false;
	}
}


/*%******************************************************************************************%*/
// EXCEPTIONS

class CacheFile_Exception extends CacheCore_Exception {}

<?php
/**
 * Container for all shared caching methods. This is not intended to be instantiated directly, but is
 * extended by the cache-specific classes.
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
class CacheCore
{
	/**
	 * A name to uniquely identify the cache object by.
	 */
	var $name;

	/**
	 * Where to store the cache.
	 */
	var $location;

	/**
	 * The number of seconds before a cache object is considered stale.
	 */
	var $expires;

	/**
	 * Used internally to uniquely identify the location + name of the cache object.
	 */
	var $id;

	/**
	 * Stores the time when the cache object was created.
	 */
	var $timestamp;

	/**
	 * Stores whether or not the content should be gzipped when stored
	 */
	var $gzip;


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
		if (!extension_loaded('zlib'))
		{
			$gzip = false;
		}

		$this->name = $name;
		$this->location = $location;
		$this->expires = $expires;
		$this->gzip = $gzip;

		return $this;
	}

	/**
	 * Allows for chaining from the constructor. Requires PHP 5.3 or newer.
	 *
	 * @param string $name (Required) A name to uniquely identify the cache object.
	 * @param string $location (Optional) The location to store the cache object in. This may vary by cache method. The default value is NULL.
	 * @param integer $expires (Optional) The number of seconds until a cache object is considered stale. The default value is 0.
	 * @param boolean $gzip (Optional) Whether data should be gzipped before being stored. The default value is true.
	 * @return object Reference to the cache object.
	 */
	public static function init($name, $location = null, $expires = 0, $gzip = true)
	{
		if (version_compare(PHP_VERSION, '5.3.0', '<'))
		{
			throw new Exception('PHP 5.3 or newer is required to use CacheCore::init().');
		}

		$self = get_called_class();
		return new $self($name, $location, $expires, $gzip);
	}

	/**
	 * Set the number of seconds until a cache expires.
	 *
	 * @param integer $expires (Optional) The number of seconds until a cache object is considered stale. The default value is 0.
	 * @return $this
	 */
	public function expire_in($seconds)
	{
		$this->expires = $seconds;
		return $this;
	}

	/**
	 * Provides a simple, straightforward cache-logic mechanism. Useful for non-complex response caches.
	 *
	 * @param string|function $callback (Required) The name of the function to fire when we need to fetch new data to cache.
	 * @param array params (Optional) Parameters to pass into the callback function, as an array.
	 * @return array The cached data being requested.
	 */
	public function response_manager($callback, $params = null)
	{
		// Automatically handle $params values.
		$params = is_array($params) ? $params : array($params);

		if ($data = $this->read())
		{
			if ($this->is_expired())
			{
				if ($data = call_user_func_array($callback, $params))
				{
					$this->update($data);
				}
				else
				{
					$this->reset();
					$data = $this->read();
				}
			}
		}
		else
		{
			if ($data = call_user_func_array($callback, $params))
			{
				$this->create($data);
			}
		}

		return $data;
	}
}


/*%******************************************************************************************%*/
// CORE DEPENDENCIES

// Include the ICacheCore interface.
if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'icachecore.interface.php'))
{
	include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'icachecore.interface.php';
}


/*%******************************************************************************************%*/
// EXCEPTIONS

class CacheCore_Exception extends Exception {}

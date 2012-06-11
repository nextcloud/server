<?php
/**
 * Defines the methods that all implementing classes MUST have. Covers CRUD (create, read, update,
 * delete) methods, as well as others that are used in the base <CacheCore> class.
 *
 * @version 2009.03.22
 * @copyright 2006-2010 Ryan Parman
 * @copyright 2006-2010 Foleeo, Inc.
 * @copyright 2008-2010 Contributors
 * @license http://opensource.org/licenses/bsd-license.php Simplified BSD License
 * @link http://github.com/skyzyx/cachecore CacheCore
 * @link http://getcloudfusion.com CloudFusion
 */
interface ICacheCore
{
	/**
	 * Creates a new cache.
	 *
	 * @param mixed $data (Required) The data to cache.
	 * @return boolean Whether the operation was successful.
	 */
	public function create($data);

	/**
	 * Reads a cache.
	 *
	 * @return mixed Either the content of the cache object, or boolean `false`.
	 */
	public function read();

	/**
	 * Updates an existing cache.
	 *
	 * @param mixed $data (Required) The data to cache.
	 * @return boolean Whether the operation was successful.
	 */
	public function update($data);

	/**
	 * Deletes a cache.
	 *
	 * @return boolean Whether the operation was successful.
	 */
	public function delete();

	/**
	 * Checks whether the cache object is expired or not.
	 *
	 * @return boolean Whether the cache is expired or not.
	 */
	public function is_expired();

	/**
	 * Retrieves the timestamp of the cache.
	 *
	 * @return mixed Either the Unix time stamp of the cache creation, or boolean `false`.
	 */
	public function timestamp();

	/**
	 * Resets the freshness of the cache.
	 *
	 * @return boolean Whether the operation was successful.
	 */
	public function reset();
}

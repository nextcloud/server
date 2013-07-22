<?php

namespace Guzzle\Cache;

/**
 * Interface for cache adapters.
 *
 * Cache adapters allow Guzzle to utilize various frameworks for caching HTTP responses.
 *
 * @link http://www.doctrine-project.org/ Inspired by Doctrine 2
 */
interface CacheAdapterInterface
{
    /**
     * Test if an entry exists in the cache.
     *
     * @param string $id      cache id The cache id of the entry to check for.
     * @param array  $options Array of cache adapter options
     *
     * @return bool Returns TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    public function contains($id, array $options = null);

    /**
     * Deletes a cache entry.
     *
     * @param string $id      cache id
     * @param array  $options Array of cache adapter options
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function delete($id, array $options = null);

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id      cache id The id of the cache entry to fetch.
     * @param array  $options Array of cache adapter options
     *
     * @return string The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function fetch($id, array $options = null);

    /**
     * Puts data into the cache.
     *
     * @param string   $id       The cache id
     * @param string   $data     The cache entry/data
     * @param int|bool $lifeTime The lifetime. If != false, sets a specific lifetime for this cache entry
     * @param array    $options  Array of cache adapter options
     *
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function save($id, $data, $lifeTime = false, array $options = null);
}

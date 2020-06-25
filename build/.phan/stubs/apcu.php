<?php

/**
 * Stubs for APCu 5.0.0
 */

/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_LIST_ACTIVE', 1);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_LIST_DELETED', 2);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_TYPE', 1);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_KEY', 2);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_FILENAME', 4);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_DEVICE', 8);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_INODE', 16);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_VALUE', 32);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_MD5', 64);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_NUM_HITS', 128);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_MTIME', 256);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_CTIME', 512);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_DTIME', 1024);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_ATIME', 2048);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_REFCOUNT', 4096);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_MEM_SIZE', 8192);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_TTL', 16384);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_NONE', 0);
/**
 * @link http://php.net/manual/en/apcu.constants.php
 */
define('APC_ITER_ALL', -1);


/**
 * Clears the APCu cache
 * @link http://php.net/manual/en/function.apcu-clear-cache.php
 *
 * @return bool Returns TRUE always.
 */
function apcu_clear_cache(){}

/**
 * Retrieves APCu Shared Memory Allocation information
 * @link http://php.net/manual/en/function.apcu-sma-info.php
 * @param bool $limited When set to FALSE (default) apcu_sma_info() will
 * return a detailed information about each segment.
 *
 * @return array|bool Array of Shared Memory Allocation data; FALSE on failure.
 */
function apcu_sma_info($limited = false){}

/**
 * Cache a variable in the data store
 * @link http://php.net/manual/en/function.apcu-store.php
 * @param string|array $key String: Store the variable using this name. Keys are cache-unique,
 * so storing a second value with the same key will overwrite the original value.
 * Array: Names in key, variables in value.
 * @param mixed $var [optional] The variable to store
 * @param int $ttl [optional]  Time To Live; store var in the cache for ttl seconds. After the ttl has passed,
 * the stored variable will be expunged from the cache (on the next request). If no ttl is supplied
 * (or if the ttl is 0), the value will persist until it is removed from the cache manually,
 * or otherwise fails to exist in the cache (clear, restart, etc.).
 * @return bool|array Returns TRUE on success or FALSE on failure | array with error keys.
 */
function apcu_store($key, $var, $ttl = 0){}

/**
 * Fetch a stored variable from the cache
 * @link http://php.net/manual/en/function.apcu-fetch.php
 * @param string|string[] $key The key used to store the value (with apcu_store()).
 * If an array is passed then each element is fetched and returned.
 * @param bool $success Set to TRUE in success and FALSE in failure.
 * @return mixed The stored variable or array of variables on success; FALSE on failure.
 */
function apcu_fetch($key, &$success = null){}

/**
 * Removes a stored variable from the cache
 * @link http://php.net/manual/en/function.apcu-delete.php
 * @param string|string[]|APCUIterator $key The key used to store the value (with apcu_store()).
 * @return bool|string[] Returns TRUE on success or FALSE on failure. For array of keys returns list of failed keys.
 */
function apcu_delete($key){}

/**
 * Caches a variable in the data store, only if it's not already stored
 * @link http://php.net/manual/en/function.apcu-add.php
 * @param string $key Store the variable using this name. Keys are cache-unique,
 * so attempting to use apcu_add() to store data with a key that already exists will not
 * overwrite the existing data, and will instead return FALSE. (This is the only difference
 * between apcu_add() and apcu_store().)
 * @param mixed $var The variable to store
 * @param int $ttl Time To Live; store var in the cache for ttl seconds. After the ttl has passed,
 * the stored variable will be expunged from the cache (on the next request). If no ttl is supplied
 * (or if the ttl is 0), the value will persist until it is removed from the cache manually,
 * or otherwise fails to exist in the cache (clear, restart, etc.).
 * @return bool
 */
function apcu_add($key, $var, $ttl = 0){}

/**
 * Checks if APCu key exists
 * @link http://php.net/manual/en/function.apcu-exists.php
 * @param string|string[] $keys A string, or an array of strings, that contain keys.
 * @return bool|string[] Returns TRUE if the key exists, otherwise FALSE
 * Or if an array was passed to keys, then an array is returned that
 * contains all existing keys, or an empty array if none exist.
 */
function apcu_exists($keys){}

/**
 * Increase a stored number
 * @link http://php.net/manual/en/function.apcu-inc.php
 * @param string $key The key of the value being increased.
 * @param int $step The step, or value to increase.
 * @param bool $success Optionally pass the success or fail boolean value to this referenced variable.
 * @return int|bool Returns the current value of key's value on success, or FALSE on failure.
 */
function apcu_inc($key, $step = 1, &$success = null){}

/**
 * Decrease a stored number
 * @link http://php.net/manual/en/function.apcu-dec.php
 * @param string $key The key of the value being decreased.
 * @param int $step The step, or value to decrease.
 * @param bool $success Optionally pass the success or fail boolean value to this referenced variable.
 * @return int|bool Returns the current value of key's value on success, or FALSE on failure.
 */
function apcu_dec($key, $step = 1, &$success = null){}

/**
 * Updates an old value with a new value
 *
 * apcu_cas() updates an already existing integer value if the old parameter matches the currently stored value
 * with the value of the new parameter.
 *
 * @link http://php.net/manual/en/function.apcu-cas.php
 * @param string $key The key of the value being updated.
 * @param int $old The old value (the value currently stored).
 * @param int $new The new value to update to.
 * @return bool Returns TRUE on success or FALSE on failure.
 */
function apcu_cas($key, $old, $new){}

/**
 * Atomically fetch or generate a cache entry
 *
 * <p>Atomically attempts to find key in the cache, if it cannot be found generator is called,
 * passing key as the only argument. The return value of the call is then cached with the optionally
 * specified ttl, and returned.
 * </p>
 *
 * <p>Note: When control enters <i>apcu_entry()</i> the lock for the cache is acquired exclusively, it is released when
 * control leaves apcu_entry(): In effect, this turns the body of generator into a critical section,
 * disallowing two processes from executing the same code paths concurrently.
 * In addition, it prohibits the concurrent execution of any other APCu functions,
 * since they will acquire the same lock.
 * </p>
 *
 * @link http://php.net/manual/en/function.apcu-entry.php
 *
 * @param string $key Identity of cache entry
 * @param callable $generator A callable that accepts key as the only argument and returns the value to cache.
 * <p>Warning
 * The only APCu function that can be called safely by generator is apcu_entry().</p>
 * @param int $ttl [optional] Time To Live; store var in the cache for ttl seconds.
 * After the ttl has passed, the stored variable will be expunged from the cache (on the next request).
 * If no ttl is supplied (or if the ttl is 0), the value will persist until it is removed from the cache manually,
 * or otherwise fails to exist in the cache (clear, restart, etc.).
 * @return mixed Returns the cached value
 * @since APCu 5.1.0
 */
function apcu_entry($key, callable $generator, $ttl = 0){}

/**
 * Retrieves cached information from APCu's data store
 *
 * @link http://php.net/manual/en/function.apcu-cache-info.php
 *
 * @param bool $limited If limited is TRUE, the return value will exclude the individual list of cache entries.
 * This is useful when trying to optimize calls for statistics gathering.
 * @return array|bool Array of cached data (and meta-data) or FALSE on failure
 */
function apcu_cache_info($limited = false){}

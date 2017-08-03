<?

/**
 * Stubs for APC 3.1.4
 *
 * Author: Johnny Woo
 * Date: Aug 9, 2010
 * Time: 12:19:14 PM
 */

/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_LIST_ACTIVE', 1);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_LIST_DELETED', 2);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_TYPE', 1);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_KEY', 2);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_FILENAME', 4);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_DEVICE', 8);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_INODE', 16);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_VALUE', 32);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_MD5', 64);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_NUM_HITS', 128);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_MTIME', 256);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_CTIME', 512);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_DTIME', 1024);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_ATIME', 2048);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_REFCOUNT', 4096);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_MEM_SIZE', 8192);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_TTL', 16384);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_NONE', 0);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_ITER_ALL', -1);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_BIN_VERIFY_MD5', 1);
/**
 * @link http://php.net/manual/en/apc.constants.php
 */
define('APC_BIN_VERIFY_CRC32', 2);

/**
 * Retrieves cached information and meta-data from APC's data store
 * @link http://php.net/manual/en/function.apc-cache-info.php
 * @param string $type If cache_type is "user", information about the user cache will be returned.
 * If cache_type is "filehits", information about which files have been served from the bytecode
 * cache for the current request will be returned. This feature must be enabled at compile time
 * using --enable-filehits. If an invalid or no cache_type is specified, information about the
 * system cache (cached files) will be returned.
 * @param bool $limited If limited is TRUE, the return value will exclude the individual list
 * of cache entries. This is useful when trying to optimize calls for statistics gathering.
 * @return array|bool Array of cached data (and meta-data) or FALSE on failure.
 */
function apc_cache_info($type = '', $limited = false){}

/**
 * Clears the APC cache
 * @link http://php.net/manual/en/function.apc-clear-cache.php
 * @param string $cache_type If cache_type is "user", the user cache will be cleared;
 * otherwise, the system cache (cached files) will be cleared.
 * @return bool Returns TRUE on success or FALSE on failure.
 */
function apc_clear_cache($cache_type = ''){}

/**
 * Retrieves APC's Shared Memory Allocation information
 * @link http://php.net/manual/en/function.apc-sma-info.php
 * @param bool $limited When set to FALSE (default) apc_sma_info() will
 * return a detailed information about each segment.
 * @return array|bool Array of Shared Memory Allocation data; FALSE on failure.
 */
function apc_sma_info($limited = false){}

/**
 * Cache a variable in the data store
 * @link http://php.net/manual/en/function.apc-store.php
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
function apc_store($key, $var, $ttl = 0){}

/**
 * Fetch a stored variable from the cache
 * @link http://php.net/manual/en/function.apc-fetch.php
 * @param string|string[] $key The key used to store the value (with apc_store()).
 * If an array is passed then each element is fetched and returned.
 * @param bool $success Set to TRUE in success and FALSE in failure.
 * @return mixed The stored variable or array of variables on success; FALSE on failure.
 */
function apc_fetch($key, &$success = null){}

/**
 * Removes a stored variable from the cache
 * @link http://php.net/manual/en/function.apc-delete.php
 * @param string|string[]|APCIterator $key The key used to store the value (with apc_store()).
 * @return bool|string[] Returns TRUE on success or FALSE on failure. For array of keys returns list of failed keys.
 */
function apc_delete($key){}

/**
 * Defines a set of constants for retrieval and mass-definition
 *
 * define() is notoriously slow. Since the main benefit of APC is to increase
 * the performance of scripts/applications, this mechanism is provided to streamline
 * the process of mass constant definition. However, this function does not perform
 * as well as anticipated. For a better-performing solution, try the hidef extension from PECL.
 *
 * @link http://php.net/manual/en/function.apc-define-constants.php
 * @param string $key The key serves as the name of the constant set being stored.
 * This key is used to retrieve the stored constants in apc_load_constants().
 * @param array $constants An associative array of constant_name => value pairs.
 * The constant_name must follow the normal constant naming rules. Value must evaluate to a scalar value.
 * @param bool $case_sensitive The default behaviour for constants is to be declared case-sensitive;
 * i.e. CONSTANT and Constant represent different values. If this parameter evaluates to FALSE
 * the constants will be declared as case-insensitive symbols.
 * @return bool Returns TRUE on success or FALSE on failure.
 */
function apc_define_constants($key, array $constants, $case_sensitive = true){}

/**
 * Caches a variable in the data store, only if it's not already stored
 * @link http://php.net/manual/en/function.apc-add.php
 * @param string $key Store the variable using this name. Keys are cache-unique,
 * so attempting to use apc_add() to store data with a key that already exists will not
 * overwrite the existing data, and will instead return FALSE. (This is the only difference
 * between apc_add() and apc_store().)
 * @param mixed $var The variable to store
 * @param int $ttl Time To Live; store var in the cache for ttl seconds. After the ttl has passed,
 * the stored variable will be expunged from the cache (on the next request). If no ttl is supplied
 * (or if the ttl is 0), the value will persist until it is removed from the cache manually,
 * or otherwise fails to exist in the cache (clear, restart, etc.).
 * @return bool
 */
function apc_add($key, $var, $ttl = 0){}

/**
 * Stores a file in the bytecode cache, bypassing all filters
 * @link http://php.net/manual/en/function.apc-compile-file.php
 * @param string|string[] $filename Full or relative path to a PHP file that will be
 * compiled and stored in the bytecode cache.
 * @param bool $atomic
 * @return bool Returns TRUE on success or FALSE on failure.
 */
function apc_compile_file($filename, $atomic = true){}

/**
 * Loads a set of constants from the cache
 * @link http://php.net/manual/en/function.apc-load-constants.php
 * @param string $key The name of the constant set (that was stored
 * with apc_define_constants()) to be retrieved.
 * @param bool $case_sensitive The default behaviour for constants is to be declared case-sensitive;
 * i.e. CONSTANT and Constant represent different values. If this parameter evaluates to FALSE
 * the constants will be declared as case-insensitive symbols.
 * @return bool Returns TRUE on success or FALSE on failure.
 */
function apc_load_constants($key, $case_sensitive = true){}

/**
 * Checks if APC key exists
 * @link http://php.net/manual/en/function.apc-exists.php
 * @param bool|string[] $keys A string, or an array of strings, that contain keys.
 * @return bool|string[] Returns TRUE if the key exists, otherwise FALSE
 * Or if an array was passed to keys, then an array is returned that
 * contains all existing keys, or an empty array if none exist.
 */
function apc_exists($keys){}

/**
 * Deletes the given files from the opcode cache
 *
 * Accepts a string, array of strings, or APCIterator object.
 * Returns True/False, or for an Array an Array of failed files.
 *
 * @link http://php.net/manual/en/function.apc-delete-file.php
 * @param string|string[]|APCIterator $keys
 * @return bool|string[]
 */
function apc_delete_file($keys){}

/**
 * Increase a stored number
 * @link http://php.net/manual/en/function.apc-inc.php
 * @param string $key The key of the value being increased.
 * @param int $step The step, or value to increase.
 * @param bool $success Optionally pass the success or fail boolean value to this referenced variable.
 * @return int|bool Returns the current value of key's value on success, or FALSE on failure.
 */
function apc_inc($key, $step = 1, &$success = null){}

/**
 * Decrease a stored number
 * @link http://php.net/manual/en/function.apc-dec.php
 * @param string $key The key of the value being decreased.
 * @param int $step The step, or value to decrease.
 * @param bool $success Optionally pass the success or fail boolean value to this referenced variable.
 * @return int|bool Returns the current value of key's value on success, or FALSE on failure.
 */
function apc_dec($key, $step = 1, &$success = null){}

/**
 * @link http://php.net/manual/en/function.apc-cas.php
 * @param string $key
 * @param int $old
 * @param int $new
 * @return bool
 */
function apc_cas($key, $old, $new){}

/**
 * Returns a binary dump of the given files and user variables from the APC cache
 *
 * A NULL for files or user_vars signals a dump of every entry, while array() will dump nothing.
 *
 * @link http://php.net/manual/en/function.apc-bin-dump.php
 * @param string[]|null $files The files. Passing in NULL signals a dump of every entry, while passing in array() will dump nothing.
 * @param string[]|null $user_vars The user vars. Passing in NULL signals a dump of every entry, while passing in array() will dump nothing.
 * @return string|bool|null Returns a binary dump of the given files and user variables from the APC cache, FALSE if APC is not enabled, or NULL if an unknown error is encountered.
 */
function apc_bin_dump($files = null, $user_vars = null){}

/**
 * Output a binary dump of the given files and user variables from the APC cache to the named file
 * @link http://php.net/manual/en/function.apc-bin-dumpfile.php
 * @param string[]|null $files The file names being dumped.
 * @param string[]|null $user_vars The user variables being dumped.
 * @param string $filename The filename where the dump is being saved.
 * @param int $flags Flags passed to the filename stream. See the file_put_contents() documentation for details.
 * @param resource $context The context passed to the filename stream. See the file_put_contents() documentation for details.
 * @return int|bool The number of bytes written to the file, otherwise FALSE if APC
 * is not enabled, filename is an invalid file name, filename can't be opened,
 * the file dump can't be completed (e.g., the hard drive is out of disk space),
 * or an unknown error was encountered.
 */
function apc_bin_dumpfile($files, $user_vars, $filename, $flags = 0, $context = null){}

/**
 * Load the given binary dump into the APC file/user cache
 * @link http://php.net/manual/en/function.apc-bin-load.php
 * @param string $data The binary dump being loaded, likely from apc_bin_dump().
 * @param int $flags Either APC_BIN_VERIFY_CRC32, APC_BIN_VERIFY_MD5, or both.
 * @return bool Returns TRUE if the binary dump data was loaded with success, otherwise FALSE is returned.
 * FALSE is returned if APC is not enabled, or if the data is not a valid APC binary dump (e.g., unexpected size).
 */
function apc_bin_load($data, $flags = 0){}

/**
 * Load the given binary dump from the named file into the APC file/user cache
 * @link http://php.net/manual/en/function.apc-bin-loadfile.php
 * @param string $filename The file name containing the dump, likely from apc_bin_dumpfile().
 * @param resource $context The files context.
 * @param int $flags Either APC_BIN_VERIFY_CRC32, APC_BIN_VERIFY_MD5, or both.
 * @return bool Returns TRUE on success, otherwise FALSE Reasons it may return FALSE include APC
 * is not enabled, filename is an invalid file name or empty, filename can't be opened,
 * the file dump can't be completed, or if the data is not a valid APC binary dump (e.g., unexpected size).
 */
function apc_bin_loadfile($filename, $context = null, $flags = 0){}


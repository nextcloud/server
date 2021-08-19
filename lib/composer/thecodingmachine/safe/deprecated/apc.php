<?php

namespace Safe;

use Safe\Exceptions\ApcException;

/**
 * Retrieves cached information and meta-data from APC's data store.
 *
 * @param string $cache_type If cache_type is "user",
 * information about the user cache will be returned.
 *
 * If cache_type is "filehits",
 * information about which files have been served from the bytecode cache
 * for the current request will be returned. This feature must be enabled at
 * compile time using --enable-filehits.
 *
 * If an invalid or no cache_type is specified, information about
 * the system cache (cached files) will be returned.
 * @param bool $limited If limited is TRUE, the
 * return value will exclude the individual list of cache entries.  This
 * is useful when trying to optimize calls for statistics gathering.
 * @return array Array of cached data (and meta-data)
 * @throws ApcException
 *
 */
function apc_cache_info(string $cache_type = '', bool $limited = false): array
{
    error_clear_last();
    $result = \apc_cache_info($cache_type, $limited);
    if ($result === false) {
        throw ApcException::createFromPhpError();
    }
    return $result;
}


/**
 * apc_cas updates an already existing integer value if the
 * old parameter matches the currently stored value
 * with the value of the new parameter.
 *
 * @param string $key The key of the value being updated.
 * @param int $old The old value (the value currently stored).
 * @param int $new The new value to update to.
 * @throws ApcException
 *
 */
function apc_cas(string $key, int $old, int $new): void
{
    error_clear_last();
    $result = \apc_cas($key, $old, $new);
    if ($result === false) {
        throw ApcException::createFromPhpError();
    }
}


/**
 * Stores a file in the bytecode cache, bypassing all filters.
 *
 * @param string $filename Full or relative path to a PHP file that will be compiled and stored in
 * the bytecode cache.
 * @param bool $atomic
 * @return mixed Returns TRUE on success.
 * @throws ApcException
 *
 */
function apc_compile_file(string $filename, bool $atomic = true)
{
    error_clear_last();
    $result = \apc_compile_file($filename, $atomic);
    if ($result === false) {
        throw ApcException::createFromPhpError();
    }
    return $result;
}


/**
 * Decreases a stored integer value.
 *
 * @param string $key The key of the value being decreased.
 * @param int $step The step, or value to decrease.
 * @param bool|null $success Optionally pass the success or fail boolean value to
 * this referenced variable.
 * @return int Returns the current value of key's value on success
 * @throws ApcException
 *
 */
function apc_dec(string $key, int $step = 1, ?bool &$success = null): int
{
    error_clear_last();
    $result = \apc_dec($key, $step, $success);
    if ($result === false) {
        throw ApcException::createFromPhpError();
    }
    return $result;
}


/**
 * define is notoriously slow. Since the main benefit of
 * APC is to increase the performance of scripts/applications, this mechanism
 * is provided to streamline the process of mass constant definition. However,
 * this function does not perform as well as anticipated.
 *
 * For a better-performing solution, try the
 * hidef extension from PECL.
 *
 * @param string $key The key serves as the name of the constant set
 * being stored. This key is used to retrieve the
 * stored constants in apc_load_constants.
 * @param array $constants An associative array of constant_name =&gt; value
 * pairs. The constant_name must follow the normal
 * constant naming rules.
 * value must evaluate to a scalar value.
 * @param bool $case_sensitive The default behaviour for constants is to be declared case-sensitive;
 * i.e. CONSTANT and Constant
 * represent different values. If this parameter evaluates to FALSE the
 * constants will be declared as case-insensitive symbols.
 * @throws ApcException
 *
 */
function apc_define_constants(string $key, array $constants, bool $case_sensitive = true): void
{
    error_clear_last();
    $result = \apc_define_constants($key, $constants, $case_sensitive);
    if ($result === false) {
        throw ApcException::createFromPhpError();
    }
}


/**
 * Deletes the given files from the opcode cache.
 *
 * @param mixed $keys The files to be deleted. Accepts a string,
 * array of strings, or an APCIterator
 * object.
 * @return mixed Returns TRUE on success.
 * Or if keys is an array, then
 * an empty array is returned on success, or an array of failed files
 * is returned.
 * @throws ApcException
 *
 */
function apc_delete_file($keys)
{
    error_clear_last();
    $result = \apc_delete_file($keys);
    if ($result === false) {
        throw ApcException::createFromPhpError();
    }
    return $result;
}


/**
 * Removes a stored variable from the cache.
 *
 * @param string|string[]|\APCIterator $key The key used to store the value (with
 * apc_store).
 * @throws ApcException
 *
 */
function apc_delete($key): void
{
    error_clear_last();
    $result = \apc_delete($key);
    if ($result === false) {
        throw ApcException::createFromPhpError();
    }
}


/**
 * Increases a stored number.
 *
 * @param string $key The key of the value being increased.
 * @param int $step The step, or value to increase.
 * @param bool|null $success Optionally pass the success or fail boolean value to
 * this referenced variable.
 * @return int Returns the current value of key's value on success
 * @throws ApcException
 *
 */
function apc_inc(string $key, int $step = 1, ?bool &$success = null): int
{
    error_clear_last();
    $result = \apc_inc($key, $step, $success);
    if ($result === false) {
        throw ApcException::createFromPhpError();
    }
    return $result;
}


/**
 * Loads a set of constants from the cache.
 *
 * @param string $key The name of the constant set (that was stored with
 * apc_define_constants) to be retrieved.
 * @param bool $case_sensitive The default behaviour for constants is to be declared case-sensitive;
 * i.e. CONSTANT and Constant
 * represent different values. If this parameter evaluates to FALSE the
 * constants will be declared as case-insensitive symbols.
 * @throws ApcException
 *
 */
function apc_load_constants(string $key, bool $case_sensitive = true): void
{
    error_clear_last();
    $result = \apc_load_constants($key, $case_sensitive);
    if ($result === false) {
        throw ApcException::createFromPhpError();
    }
}


/**
 * Retrieves APC's Shared Memory Allocation information.
 *
 * @param bool $limited When set to FALSE (default) apc_sma_info will
 * return a detailed information about each segment.
 * @return array Array of Shared Memory Allocation data; FALSE on failure.
 * @throws ApcException
 *
 */
function apc_sma_info(bool $limited = false): array
{
    error_clear_last();
    $result = \apc_sma_info($limited);
    if ($result === false) {
        throw ApcException::createFromPhpError();
    }
    return $result;
}

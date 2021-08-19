<?php

namespace Safe;

use Safe\Exceptions\ApcuException;

/**
 * Retrieves cached information and meta-data from APC's data store.
 *
 * @param bool $limited If limited is TRUE, the
 * return value will exclude the individual list of cache entries.  This
 * is useful when trying to optimize calls for statistics gathering.
 * @return array Array of cached data (and meta-data)
 * @throws ApcuException
 *
 */
function apcu_cache_info(bool $limited = false): array
{
    error_clear_last();
    $result = \apcu_cache_info($limited);
    if ($result === false) {
        throw ApcuException::createFromPhpError();
    }
    return $result;
}


/**
 * apcu_cas updates an already existing integer value if the
 * old parameter matches the currently stored value
 * with the value of the new parameter.
 *
 * @param string $key The key of the value being updated.
 * @param int $old The old value (the value currently stored).
 * @param int $new The new value to update to.
 * @throws ApcuException
 *
 */
function apcu_cas(string $key, int $old, int $new): void
{
    error_clear_last();
    $result = \apcu_cas($key, $old, $new);
    if ($result === false) {
        throw ApcuException::createFromPhpError();
    }
}


/**
 * Decreases a stored integer value.
 *
 * @param string $key The key of the value being decreased.
 * @param int $step The step, or value to decrease.
 * @param bool|null $success Optionally pass the success or fail boolean value to
 * this referenced variable.
 * @param int $ttl TTL to use if the operation inserts a new value (rather than decrementing an existing one).
 * @return int Returns the current value of key's value on success
 * @throws ApcuException
 *
 */
function apcu_dec(string $key, int $step = 1, ?bool &$success = null, int $ttl = 0): int
{
    error_clear_last();
    $result = \apcu_dec($key, $step, $success, $ttl);
    if ($result === false) {
        throw ApcuException::createFromPhpError();
    }
    return $result;
}


/**
 * Increases a stored number.
 *
 * @param string $key The key of the value being increased.
 * @param int $step The step, or value to increase.
 * @param bool|null $success Optionally pass the success or fail boolean value to
 * this referenced variable.
 * @param int $ttl TTL to use if the operation inserts a new value (rather than incrementing an existing one).
 * @return int Returns the current value of key's value on success
 * @throws ApcuException
 *
 */
function apcu_inc(string $key, int $step = 1, ?bool &$success = null, int $ttl = 0): int
{
    error_clear_last();
    $result = \apcu_inc($key, $step, $success, $ttl);
    if ($result === false) {
        throw ApcuException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves APCu Shared Memory Allocation information.
 *
 * @param bool $limited When set to FALSE (default) apcu_sma_info will
 * return a detailed information about each segment.
 * @return array Array of Shared Memory Allocation data; FALSE on failure.
 * @throws ApcuException
 *
 */
function apcu_sma_info(bool $limited = false): array
{
    error_clear_last();
    $result = \apcu_sma_info($limited);
    if ($result === false) {
        throw ApcuException::createFromPhpError();
    }
    return $result;
}

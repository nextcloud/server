<?php

namespace Safe;

use Safe\Exceptions\OpcacheException;

/**
 * This function compiles a PHP script and adds it to the opcode cache without
 * executing it. This can be used to prime the cache after a Web server
 * restart by pre-caching files that will be included in later requests.
 *
 * @param string $file The path to the PHP script to be compiled.
 * @throws OpcacheException
 *
 */
function opcache_compile_file(string $file): void
{
    error_clear_last();
    $result = \opcache_compile_file($file);
    if ($result === false) {
        throw OpcacheException::createFromPhpError();
    }
}


/**
 * This function returns state information about the cache instance
 *
 * @param bool $get_scripts Include script specific state information
 * @return array Returns an array of information, optionally containing script specific state information.
 * @throws OpcacheException
 *
 */
function opcache_get_status(bool $get_scripts = true): array
{
    error_clear_last();
    $result = \opcache_get_status($get_scripts);
    if ($result === false) {
        throw OpcacheException::createFromPhpError();
    }
    return $result;
}

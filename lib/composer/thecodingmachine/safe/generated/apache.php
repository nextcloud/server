<?php

namespace Safe;

use Safe\Exceptions\ApacheException;

/**
 * Fetch the Apache version.
 *
 * @return string Returns the Apache version on success.
 * @throws ApacheException
 *
 */
function apache_get_version(): string
{
    error_clear_last();
    $result = \apache_get_version();
    if ($result === false) {
        throw ApacheException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieve an Apache environment variable specified by
 * variable.
 *
 * This function requires Apache 2 otherwise it's undefined.
 *
 * @param string $variable The Apache environment variable
 * @param bool $walk_to_top Whether to get the top-level variable available to all Apache layers.
 * @return string The value of the Apache environment variable on success
 * @throws ApacheException
 *
 */
function apache_getenv(string $variable, bool $walk_to_top = false): string
{
    error_clear_last();
    $result = \apache_getenv($variable, $walk_to_top);
    if ($result === false) {
        throw ApacheException::createFromPhpError();
    }
    return $result;
}


/**
 * Fetches all HTTP request headers from the current request. Works in the
 * Apache, FastCGI, CLI, FPM and NSAPI server module
 * in Netscape/iPlanet/SunONE webservers.
 *
 * @return array An associative array of all the HTTP headers in the current request.
 * @throws ApacheException
 *
 */
function apache_request_headers(): array
{
    error_clear_last();
    $result = \apache_request_headers();
    if ($result === false) {
        throw ApacheException::createFromPhpError();
    }
    return $result;
}


/**
 * apache_reset_timeout resets the Apache write timer,
 * which defaults to 300 seconds. With set_time_limit(0);
 * ignore_user_abort(true) and periodic
 * apache_reset_timeout calls, Apache can theoretically
 * run forever.
 *
 * This function requires Apache 1.
 *
 * @throws ApacheException
 *
 */
function apache_reset_timeout(): void
{
    error_clear_last();
    $result = \apache_reset_timeout();
    if ($result === false) {
        throw ApacheException::createFromPhpError();
    }
}


/**
 * Fetch all HTTP response headers.  Works in the
 * Apache, FastCGI, CLI, FPM and NSAPI server module
 * in Netscape/iPlanet/SunONE webservers.
 *
 * @return array An array of all Apache response headers on success.
 * @throws ApacheException
 *
 */
function apache_response_headers(): array
{
    error_clear_last();
    $result = \apache_response_headers();
    if ($result === false) {
        throw ApacheException::createFromPhpError();
    }
    return $result;
}


/**
 * apache_setenv sets the value of the Apache
 * environment variable specified by
 * variable.
 *
 * @param string $variable The environment variable that's being set.
 * @param string $value The new variable value.
 * @param bool $walk_to_top Whether to set the top-level variable available to all Apache layers.
 * @throws ApacheException
 *
 */
function apache_setenv(string $variable, string $value, bool $walk_to_top = false): void
{
    error_clear_last();
    $result = \apache_setenv($variable, $value, $walk_to_top);
    if ($result === false) {
        throw ApacheException::createFromPhpError();
    }
}


/**
 * Fetches all HTTP headers from the current request.
 *
 * This function is an alias for apache_request_headers.
 * Please read the apache_request_headers
 * documentation for more information on how this function works.
 *
 * @return array An associative array of all the HTTP headers in the current request.
 * @throws ApacheException
 *
 */
function getallheaders(): array
{
    error_clear_last();
    $result = \getallheaders();
    if ($result === false) {
        throw ApacheException::createFromPhpError();
    }
    return $result;
}


/**
 * virtual is an Apache-specific function which
 * is similar to &lt;!--#include virtual...--&gt; in
 * mod_include.
 * It performs an Apache sub-request.  It is useful for including
 * CGI scripts or .shtml files, or anything else that you would
 * parse through Apache. Note that for a CGI script, the script
 * must generate valid CGI headers.  At the minimum that means it
 * must generate a Content-Type header.
 *
 * To run the sub-request, all buffers are terminated and flushed to the
 * browser, pending headers are sent too.
 *
 * @param string $filename The file that the virtual command will be performed on.
 * @throws ApacheException
 *
 */
function virtual(string $filename): void
{
    error_clear_last();
    $result = \virtual($filename);
    if ($result === false) {
        throw ApacheException::createFromPhpError();
    }
}

<?php

namespace Safe;

use Safe\Exceptions\StreamException;

/**
 * Sets parameters on the specified context.
 *
 * @param resource $stream_or_context The stream or context to apply the parameters too.
 * @param array $params An array of parameters to set.
 *
 * params should be an associative array of the structure:
 * $params['paramname'] = "paramvalue";.
 * @throws StreamException
 *
 */
function stream_context_set_params($stream_or_context, array $params): void
{
    error_clear_last();
    $result = \stream_context_set_params($stream_or_context, $params);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
}


/**
 * Makes a copy of up to maxlength bytes
 * of data from the current position (or from the
 * offset position, if specified) in
 * source to dest. If
 * maxlength is not specified, all remaining content in
 * source will be copied.
 *
 * @param resource $source The source stream
 * @param resource $dest The destination stream
 * @param int $maxlength Maximum bytes to copy
 * @param int $offset The offset where to start to copy data
 * @return int Returns the total count of bytes copied.
 * @throws StreamException
 *
 */
function stream_copy_to_stream($source, $dest, int $maxlength = -1, int $offset = 0): int
{
    error_clear_last();
    $result = \stream_copy_to_stream($source, $dest, $maxlength, $offset);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
    return $result;
}


/**
 * Adds filtername to the list of filters
 * attached to stream.
 *
 * @param resource $stream The target stream.
 * @param string $filtername The filter name.
 * @param int $read_write By default, stream_filter_append will
 * attach the filter to the read filter chain
 * if the file was opened for reading (i.e. File Mode:
 * r, and/or +).  The filter
 * will also be attached to the write filter chain
 * if the file was opened for writing (i.e. File Mode:
 * w, a, and/or +).
 * STREAM_FILTER_READ,
 * STREAM_FILTER_WRITE, and/or
 * STREAM_FILTER_ALL can also be passed to the
 * read_write parameter to override this behavior.
 * @param mixed $params This filter will be added with the specified
 * params to the end of
 * the list and will therefore be called last during stream operations.
 * To add a filter to the beginning of the list, use
 * stream_filter_prepend.
 * @return resource Returns a resource on success. The resource can be
 * used to refer to this filter instance during a call to
 * stream_filter_remove.
 *
 * FALSE is returned if stream is not a resource or
 * if filtername cannot be located.
 * @throws StreamException
 *
 */
function stream_filter_append($stream, string $filtername, int $read_write = null, $params = null)
{
    error_clear_last();
    if ($params !== null) {
        $result = \stream_filter_append($stream, $filtername, $read_write, $params);
    } elseif ($read_write !== null) {
        $result = \stream_filter_append($stream, $filtername, $read_write);
    } else {
        $result = \stream_filter_append($stream, $filtername);
    }
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
    return $result;
}


/**
 * Adds filtername to the list of filters
 * attached to stream.
 *
 * @param resource $stream The target stream.
 * @param string $filtername The filter name.
 * @param int $read_write By default, stream_filter_prepend will
 * attach the filter to the read filter chain
 * if the file was opened for reading (i.e. File Mode:
 * r, and/or +).  The filter
 * will also be attached to the write filter chain
 * if the file was opened for writing (i.e. File Mode:
 * w, a, and/or +).
 * STREAM_FILTER_READ,
 * STREAM_FILTER_WRITE, and/or
 * STREAM_FILTER_ALL can also be passed to the
 * read_write parameter to override this behavior.
 * See stream_filter_append for an example of
 * using this parameter.
 * @param mixed $params This filter will be added with the specified params
 * to the beginning of the list and will therefore be
 * called first during stream operations.  To add a filter to the end of the
 * list, use stream_filter_append.
 * @return resource Returns a resource on success. The resource can be
 * used to refer to this filter instance during a call to
 * stream_filter_remove.
 *
 * FALSE is returned if stream is not a resource or
 * if filtername cannot be located.
 * @throws StreamException
 *
 */
function stream_filter_prepend($stream, string $filtername, int $read_write = null, $params = null)
{
    error_clear_last();
    if ($params !== null) {
        $result = \stream_filter_prepend($stream, $filtername, $read_write, $params);
    } elseif ($read_write !== null) {
        $result = \stream_filter_prepend($stream, $filtername, $read_write);
    } else {
        $result = \stream_filter_prepend($stream, $filtername);
    }
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
    return $result;
}


/**
 * stream_filter_register allows you to implement
 * your own filter on any registered stream used with all the other
 * filesystem functions (such as fopen,
 * fread etc.).
 *
 * @param string $filtername The filter name to be registered.
 * @param string $classname To implement a filter, you need to define a class as an extension of
 * php_user_filter with a number of member
 * functions. When performing read/write operations on the stream
 * to which your filter is attached, PHP will pass the data through your
 * filter (and any other filters attached to that stream) so that the
 * data may be modified as desired. You must implement the methods
 * exactly as described in php_user_filter - doing
 * otherwise will lead to undefined behaviour.
 * @throws StreamException
 *
 */
function stream_filter_register(string $filtername, string $classname): void
{
    error_clear_last();
    $result = \stream_filter_register($filtername, $classname);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
}


/**
 * Removes a stream filter previously added to a stream with
 * stream_filter_prepend or
 * stream_filter_append.  Any data remaining in the
 * filter's internal buffer will be flushed through to the next filter before
 * removing it.
 *
 * @param resource $stream_filter The stream filter to be removed.
 * @throws StreamException
 *
 */
function stream_filter_remove($stream_filter): void
{
    error_clear_last();
    $result = \stream_filter_remove($stream_filter);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
}


/**
 * Identical to file_get_contents, except that
 * stream_get_contents operates on an already open
 * stream resource and returns the remaining contents in a string, up to
 * maxlength bytes and starting at the specified
 * offset.
 *
 * @param resource $handle A stream resource (e.g. returned from fopen)
 * @param int $maxlength The maximum bytes to read. Defaults to -1 (read all the remaining
 * buffer).
 * @param int $offset Seek to the specified offset before reading. If this number is negative,
 * no seeking will occur and reading will start from the current position.
 * @return string Returns a string.
 * @throws StreamException
 *
 */
function stream_get_contents($handle, int $maxlength = -1, int $offset = -1): string
{
    error_clear_last();
    $result = \stream_get_contents($handle, $maxlength, $offset);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
    return $result;
}


/**
 * Determines if stream stream refers to a valid terminal type device.
 * This is a more portable version of posix_isatty, since it works on Windows systems too.
 *
 * @param resource $stream
 * @throws StreamException
 *
 */
function stream_isatty($stream): void
{
    error_clear_last();
    $result = \stream_isatty($stream);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
}


/**
 * Resolve filename against the include path according to the same rules as fopen/include.
 *
 * @param string $filename The filename to resolve.
 * @return string Returns a string containing the resolved absolute filename.
 * @throws StreamException
 *
 */
function stream_resolve_include_path(string $filename): string
{
    error_clear_last();
    $result = \stream_resolve_include_path($filename);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
    return $result;
}


/**
 * Sets blocking or non-blocking mode on a stream.
 *
 * This function works for any stream that supports non-blocking mode
 * (currently, regular files and socket streams).
 *
 * @param resource $stream The stream.
 * @param bool $mode If mode is FALSE, the given stream
 * will be switched to non-blocking mode, and if TRUE, it
 * will be switched to blocking mode.  This affects calls like
 * fgets and fread
 * that read from the stream.  In non-blocking mode an
 * fgets call will always return right away
 * while in blocking mode it will wait for data to become available
 * on the stream.
 * @throws StreamException
 *
 */
function stream_set_blocking($stream, bool $mode): void
{
    error_clear_last();
    $result = \stream_set_blocking($stream, $mode);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
}


/**
 * Sets the timeout value on stream,
 * expressed in the sum of seconds and
 * microseconds.
 *
 * When the stream times out, the 'timed_out' key of the array returned by
 * stream_get_meta_data is set to TRUE, although no
 * error/warning is generated.
 *
 * @param resource $stream The target stream.
 * @param int $seconds The seconds part of the timeout to be set.
 * @param int $microseconds The microseconds part of the timeout to be set.
 * @throws StreamException
 *
 */
function stream_set_timeout($stream, int $seconds, int $microseconds = 0): void
{
    error_clear_last();
    $result = \stream_set_timeout($stream, $seconds, $microseconds);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
}


/**
 * Accept a connection on a socket previously created by
 * stream_socket_server.
 *
 * @param resource $server_socket The server socket to accept a connection from.
 * @param float $timeout Override the default socket accept timeout. Time should be given in
 * seconds.
 * @param string|null $peername Will be set to the name (address) of the client which connected, if
 * included and available from the selected transport.
 *
 * Can also be determined later using
 * stream_socket_get_name.
 * @return resource Returns a stream to the accepted socket connection.
 * @throws StreamException
 *
 */
function stream_socket_accept($server_socket, float $timeout = null, ?string &$peername = null)
{
    error_clear_last();
    if ($peername !== null) {
        $result = \stream_socket_accept($server_socket, $timeout, $peername);
    } elseif ($timeout !== null) {
        $result = \stream_socket_accept($server_socket, $timeout);
    } else {
        $result = \stream_socket_accept($server_socket);
    }
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
    return $result;
}


/**
 * Initiates a stream or datagram connection to the destination specified
 * by remote_socket.  The type of socket created
 * is determined by the transport specified using standard URL formatting:
 * transport://target.  For Internet Domain sockets
 * (AF_INET) such as TCP and UDP, the target portion
 * of the remote_socket parameter should consist of
 * a hostname or IP address followed by a colon and a port number.  For Unix
 * domain sockets, the target portion should point
 * to the socket file on the filesystem.
 *
 * @param string $remote_socket Address to the socket to connect to.
 * @param int $errno Will be set to the system level error number if connection fails.
 * @param string $errstr Will be set to the system level error message if the connection fails.
 * @param float $timeout Number of seconds until the connect() system call
 * should timeout.
 *
 *
 * This parameter only applies when not making asynchronous
 * connection attempts.
 *
 *
 *
 *
 * To set a timeout for reading/writing data over the socket, use the
 * stream_set_timeout, as the
 * timeout only applies while making connecting
 * the socket.
 *
 *
 *
 * To set a timeout for reading/writing data over the socket, use the
 * stream_set_timeout, as the
 * timeout only applies while making connecting
 * the socket.
 * @param int $flags Bitmask field which may be set to any combination of connection flags.
 * Currently the select of connection flags is limited to
 * STREAM_CLIENT_CONNECT (default),
 * STREAM_CLIENT_ASYNC_CONNECT and
 * STREAM_CLIENT_PERSISTENT.
 * @param resource $context A valid context resource created with stream_context_create.
 * @return resource On success a stream resource is returned which may
 * be used together with the other file functions (such as
 * fgets, fgetss,
 * fwrite, fclose, and
 * feof), FALSE on failure.
 * @throws StreamException
 *
 */
function stream_socket_client(string $remote_socket, int &$errno = null, string &$errstr = null, float $timeout = null, int $flags = STREAM_CLIENT_CONNECT, $context = null)
{
    error_clear_last();
    if ($context !== null) {
        $result = \stream_socket_client($remote_socket, $errno, $errstr, $timeout, $flags, $context);
    } elseif ($flags !== STREAM_CLIENT_CONNECT) {
        $result = \stream_socket_client($remote_socket, $errno, $errstr, $timeout, $flags);
    } elseif ($timeout !== null) {
        $result = \stream_socket_client($remote_socket, $errno, $errstr, $timeout);
    } else {
        $result = \stream_socket_client($remote_socket, $errno, $errstr);
    }
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
    return $result;
}


/**
 * stream_socket_pair creates a pair of connected,
 * indistinguishable socket streams. This function is commonly used in IPC
 * (Inter-Process Communication).
 *
 * @param int $domain The protocol family to be used: STREAM_PF_INET,
 * STREAM_PF_INET6 or
 * STREAM_PF_UNIX
 * @param int $type The type of communication to be used:
 * STREAM_SOCK_DGRAM,
 * STREAM_SOCK_RAW,
 * STREAM_SOCK_RDM,
 * STREAM_SOCK_SEQPACKET or
 * STREAM_SOCK_STREAM
 * @param int $protocol The protocol to be used: STREAM_IPPROTO_ICMP,
 * STREAM_IPPROTO_IP,
 * STREAM_IPPROTO_RAW,
 * STREAM_IPPROTO_TCP or
 * STREAM_IPPROTO_UDP
 * @return resource[] Returns an array with the two socket resources on success.
 * @throws StreamException
 *
 */
function stream_socket_pair(int $domain, int $type, int $protocol): iterable
{
    error_clear_last();
    $result = \stream_socket_pair($domain, $type, $protocol);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
    return $result;
}


/**
 * Creates a stream or datagram socket on the specified
 * local_socket.
 *
 * This function only creates a socket, to begin accepting connections
 * use stream_socket_accept.
 *
 * @param string $local_socket The type of socket created is determined by the transport specified
 * using standard URL formatting: transport://target.
 *
 * For Internet Domain sockets (AF_INET) such as TCP and UDP, the
 * target portion of the
 * remote_socket parameter should consist of a
 * hostname or IP address followed by a colon and a port number.  For
 * Unix domain sockets, the target portion should
 * point to the socket file on the filesystem.
 *
 * Depending on the environment, Unix domain sockets may not be available.
 * A list of available transports can be retrieved using
 * stream_get_transports. See
 * for a list of bulitin transports.
 * @param int $errno If the optional errno and errstr
 * arguments are present they will be set to indicate the actual system
 * level error that occurred in the system-level socket(),
 * bind(), and listen() calls. If
 * the value returned in errno is
 * 0 and the function returned FALSE, it is an
 * indication that the error occurred before the bind()
 * call. This is most likely due to a problem initializing the socket.
 * Note that the errno and
 * errstr arguments will always be passed by reference.
 * @param string $errstr See errno description.
 * @param int $flags A bitmask field which may be set to any combination of socket creation
 * flags.
 *
 * For UDP sockets, you must use STREAM_SERVER_BIND as
 * the flags parameter.
 * @param resource $context
 * @return resource Returns the created stream.
 * @throws StreamException
 *
 */
function stream_socket_server(string $local_socket, int &$errno = null, string &$errstr = null, int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context = null)
{
    error_clear_last();
    if ($context !== null) {
        $result = \stream_socket_server($local_socket, $errno, $errstr, $flags, $context);
    } else {
        $result = \stream_socket_server($local_socket, $errno, $errstr, $flags);
    }
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
    return $result;
}


/**
 * Shutdowns (partially or not) a full-duplex connection.
 *
 * @param resource $stream An open stream (opened with stream_socket_client,
 * for example)
 * @param int $how One of the following constants: STREAM_SHUT_RD
 * (disable further receptions), STREAM_SHUT_WR
 * (disable further transmissions) or
 * STREAM_SHUT_RDWR (disable further receptions and
 * transmissions).
 * @throws StreamException
 *
 */
function stream_socket_shutdown($stream, int $how): void
{
    error_clear_last();
    $result = \stream_socket_shutdown($stream, $how);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
}


/**
 * Tells whether the stream supports locking through
 * flock.
 *
 * @param resource $stream The stream to check.
 * @throws StreamException
 *
 */
function stream_supports_lock($stream): void
{
    error_clear_last();
    $result = \stream_supports_lock($stream);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
}


/**
 * Allows you to implement your own protocol handlers and streams for use
 * with all the other filesystem functions (such as fopen,
 * fread etc.).
 *
 * @param string $protocol The wrapper name to be registered.
 * @param string $classname The classname which implements the protocol.
 * @param int $flags Should be set to STREAM_IS_URL if
 * protocol is a URL protocol. Default is 0, local
 * stream.
 * @throws StreamException
 *
 */
function stream_wrapper_register(string $protocol, string $classname, int $flags = 0): void
{
    error_clear_last();
    $result = \stream_wrapper_register($protocol, $classname, $flags);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
}


/**
 * Restores a built-in wrapper previously unregistered with
 * stream_wrapper_unregister.
 *
 * @param string $protocol
 * @throws StreamException
 *
 */
function stream_wrapper_restore(string $protocol): void
{
    error_clear_last();
    $result = \stream_wrapper_restore($protocol);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
}


/**
 * Allows you to disable an already defined stream wrapper. Once the wrapper
 * has been disabled you may override it with a user-defined wrapper using
 * stream_wrapper_register or reenable it later on with
 * stream_wrapper_restore.
 *
 * @param string $protocol
 * @throws StreamException
 *
 */
function stream_wrapper_unregister(string $protocol): void
{
    error_clear_last();
    $result = \stream_wrapper_unregister($protocol);
    if ($result === false) {
        throw StreamException::createFromPhpError();
    }
}

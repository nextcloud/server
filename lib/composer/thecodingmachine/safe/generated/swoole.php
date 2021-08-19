<?php

namespace Safe;

use Safe\Exceptions\SwooleException;

/**
 *
 *
 * @param string $filename The filename being written.
 * @param string $content The content writing to the file.
 * @param int $offset The offset.
 * @param callable $callback
 * @throws SwooleException
 *
 */
function swoole_async_write(string $filename, string $content, int $offset = null, callable $callback = null): void
{
    error_clear_last();
    if ($callback !== null) {
        $result = \swoole_async_write($filename, $content, $offset, $callback);
    } elseif ($offset !== null) {
        $result = \swoole_async_write($filename, $content, $offset);
    } else {
        $result = \swoole_async_write($filename, $content);
    }
    if ($result === false) {
        throw SwooleException::createFromPhpError();
    }
}


/**
 *
 *
 * @param string $filename The filename being written.
 * @param string $content The content writing to the file.
 * @param callable $callback
 * @param int $flags
 * @throws SwooleException
 *
 */
function swoole_async_writefile(string $filename, string $content, callable $callback = null, int $flags = 0): void
{
    error_clear_last();
    if ($flags !== 0) {
        $result = \swoole_async_writefile($filename, $content, $callback, $flags);
    } elseif ($callback !== null) {
        $result = \swoole_async_writefile($filename, $content, $callback);
    } else {
        $result = \swoole_async_writefile($filename, $content);
    }
    if ($result === false) {
        throw SwooleException::createFromPhpError();
    }
}


/**
 *
 *
 * @param callable $callback
 * @throws SwooleException
 *
 */
function swoole_event_defer(callable $callback): void
{
    error_clear_last();
    $result = \swoole_event_defer($callback);
    if ($result === false) {
        throw SwooleException::createFromPhpError();
    }
}


/**
 *
 *
 * @param int $fd
 * @throws SwooleException
 *
 */
function swoole_event_del(int $fd): void
{
    error_clear_last();
    $result = \swoole_event_del($fd);
    if ($result === false) {
        throw SwooleException::createFromPhpError();
    }
}


/**
 *
 *
 * @param int $fd
 * @param string $data
 * @throws SwooleException
 *
 */
function swoole_event_write(int $fd, string $data): void
{
    error_clear_last();
    $result = \swoole_event_write($fd, $data);
    if ($result === false) {
        throw SwooleException::createFromPhpError();
    }
}

<?php

namespace Safe;

use Safe\Exceptions\ShmopException;

/**
 * shmop_delete is used to delete a shared memory block.
 *
 * @param resource $shmid The shared memory block resource created by
 * shmop_open
 * @throws ShmopException
 *
 */
function shmop_delete($shmid): void
{
    error_clear_last();
    $result = \shmop_delete($shmid);
    if ($result === false) {
        throw ShmopException::createFromPhpError();
    }
}


/**
 * shmop_read will read a string from shared memory block.
 *
 * @param resource $shmid The shared memory block identifier created by
 * shmop_open
 * @param int $start Offset from which to start reading
 * @param int $count The number of bytes to read.
 * 0 reads shmop_size($shmid) - $start bytes.
 * @return string Returns the data.
 * @throws ShmopException
 *
 */
function shmop_read($shmid, int $start, int $count): string
{
    error_clear_last();
    $result = \shmop_read($shmid, $start, $count);
    if ($result === false) {
        throw ShmopException::createFromPhpError();
    }
    return $result;
}


/**
 * shmop_write will write a string into shared memory block.
 *
 * @param resource $shmid The shared memory block identifier created by
 * shmop_open
 * @param string $data A string to write into shared memory block
 * @param int $offset Specifies where to start writing data inside the shared memory
 * segment.
 * @return int The size of the written data.
 * @throws ShmopException
 *
 */
function shmop_write($shmid, string $data, int $offset): int
{
    error_clear_last();
    $result = \shmop_write($shmid, $data, $offset);
    if ($result === false) {
        throw ShmopException::createFromPhpError();
    }
    return $result;
}

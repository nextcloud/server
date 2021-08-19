<?php

namespace Safe;

use Safe\Exceptions\ZipException;

/**
 * Closes the specified directory entry.
 *
 * @param resource $zip_entry A directory entry previously opened zip_entry_open.
 * @throws ZipException
 *
 */
function zip_entry_close($zip_entry): void
{
    error_clear_last();
    $result = \zip_entry_close($zip_entry);
    if ($result === false) {
        throw ZipException::createFromPhpError();
    }
}


/**
 * Opens a directory entry in a zip file for reading.
 *
 * @param resource $zip A valid resource handle returned by zip_open.
 * @param resource $zip_entry A directory entry returned by zip_read.
 * @param string $mode Any of the modes specified in the documentation of
 * fopen.
 *
 * Currently, mode is ignored and is always
 * "rb". This is due to the fact that zip support
 * in PHP is read only access.
 * @throws ZipException
 *
 */
function zip_entry_open($zip, $zip_entry, string $mode = null): void
{
    error_clear_last();
    if ($mode !== null) {
        $result = \zip_entry_open($zip, $zip_entry, $mode);
    } else {
        $result = \zip_entry_open($zip, $zip_entry);
    }
    if ($result === false) {
        throw ZipException::createFromPhpError();
    }
}


/**
 * Reads from an open directory entry.
 *
 * @param resource $zip_entry A directory entry returned by zip_read.
 * @param int $length The number of bytes to return.
 *
 * This should be the uncompressed length you wish to read.
 * @return string Returns the data read, empty string on end of a file.
 * @throws ZipException
 *
 */
function zip_entry_read($zip_entry, int $length = 1024): string
{
    error_clear_last();
    $result = \zip_entry_read($zip_entry, $length);
    if ($result === false) {
        throw ZipException::createFromPhpError();
    }
    return $result;
}

<?php

namespace Safe;

use Safe\Exceptions\DirException;

/**
 * Changes PHP's current directory to
 * directory.
 *
 * @param string $directory The new current directory
 * @throws DirException
 *
 */
function chdir(string $directory): void
{
    error_clear_last();
    $result = \chdir($directory);
    if ($result === false) {
        throw DirException::createFromPhpError();
    }
}


/**
 * Changes the root directory of the current process to
 * directory, and changes the current
 * working directory to "/".
 *
 * This function is only available to GNU and BSD systems, and
 * only when using the CLI, CGI or Embed SAPI. Also, this function
 * requires root privileges.
 *
 * @param string $directory The path to change the root directory to.
 * @throws DirException
 *
 */
function chroot(string $directory): void
{
    error_clear_last();
    $result = \chroot($directory);
    if ($result === false) {
        throw DirException::createFromPhpError();
    }
}


/**
 * Gets the current working directory.
 *
 * @return string Returns the current working directory on success.
 *
 * On some Unix variants, getcwd will return
 * FALSE if any one of the parent directories does not have the
 * readable or search mode set, even if the current directory
 * does. See chmod for more information on
 * modes and permissions.
 * @throws DirException
 *
 */
function getcwd(): string
{
    error_clear_last();
    $result = \getcwd();
    if ($result === false) {
        throw DirException::createFromPhpError();
    }
    return $result;
}


/**
 * Opens up a directory handle to be used in subsequent
 * closedir, readdir, and
 * rewinddir calls.
 *
 * @param string $path The directory path that is to be opened
 * @param resource $context For a description of the context parameter,
 * refer to the streams section of
 * the manual.
 * @return resource Returns a directory handle resource on success
 * @throws DirException
 *
 */
function opendir(string $path, $context = null)
{
    error_clear_last();
    if ($context !== null) {
        $result = \opendir($path, $context);
    } else {
        $result = \opendir($path);
    }
    if ($result === false) {
        throw DirException::createFromPhpError();
    }
    return $result;
}


/**
 * Resets the directory stream indicated by
 * dir_handle to the beginning of the
 * directory.
 *
 * @param resource $dir_handle The directory handle resource previously opened
 * with opendir. If the directory handle is
 * not specified, the last link opened by opendir
 * is assumed.
 * @throws DirException
 *
 */
function rewinddir($dir_handle = null): void
{
    error_clear_last();
    if ($dir_handle !== null) {
        $result = \rewinddir($dir_handle);
    } else {
        $result = \rewinddir();
    }
    if ($result === false) {
        throw DirException::createFromPhpError();
    }
}


/**
 * Returns an array of files and directories from the
 * directory.
 *
 * @param string $directory The directory that will be scanned.
 * @param int $sorting_order By default, the sorted order is alphabetical in ascending order.  If
 * the optional sorting_order is set to
 * SCANDIR_SORT_DESCENDING, then the sort order is
 * alphabetical in descending order. If it is set to
 * SCANDIR_SORT_NONE then the result is unsorted.
 * @param resource $context For a description of the context parameter,
 * refer to the streams section of
 * the manual.
 * @return array Returns an array of filenames on success. If directory is not a directory, then
 * boolean FALSE is returned, and an error of level
 * E_WARNING is generated.
 * @throws DirException
 *
 */
function scandir(string $directory, int $sorting_order = SCANDIR_SORT_ASCENDING, $context = null): array
{
    error_clear_last();
    if ($context !== null) {
        $result = \scandir($directory, $sorting_order, $context);
    } else {
        $result = \scandir($directory, $sorting_order);
    }
    if ($result === false) {
        throw DirException::createFromPhpError();
    }
    return $result;
}

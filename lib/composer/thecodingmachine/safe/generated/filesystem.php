<?php

namespace Safe;

use Safe\Exceptions\FilesystemException;

/**
 * Attempts to change the group of the file filename
 * to group.
 *
 * Only the superuser may change the group of a file arbitrarily; other users
 * may change the group of a file to any group of which that user is a member.
 *
 * @param string $filename Path to the file.
 * @param string|int $group A group name or number.
 * @throws FilesystemException
 *
 */
function chgrp(string $filename, $group): void
{
    error_clear_last();
    $result = \chgrp($filename, $group);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * Attempts to change the mode of the specified file to that given in
 * mode.
 *
 * @param string $filename Path to the file.
 * @param int $mode Note that mode is not automatically
 * assumed to be an octal value, so to ensure the expected operation,
 * you need to prefix mode with a zero (0).
 * Strings such as "g+w" will not work properly.
 *
 *
 *
 *
 * ]]>
 *
 *
 *
 * The mode parameter consists of three octal
 * number components specifying access restrictions for the owner,
 * the user group in which the owner is in, and to everybody else in
 * this order. One component can be computed by adding up the needed
 * permissions for that target user base. Number 1 means that you
 * grant execute rights, number 2 means that you make the file
 * writeable, number 4 means that you make the file readable. Add
 * up these numbers to specify needed rights. You can also read more
 * about modes on Unix systems with 'man 1 chmod'
 * and 'man 2 chmod'.
 *
 *
 *
 *
 */
function chmod(string $filename, int $mode): void
{
    error_clear_last();
    $result = \chmod($filename, $mode);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * Attempts to change the owner of the file filename
 * to user user. Only the superuser may change the
 * owner of a file.
 *
 * @param string $filename Path to the file.
 * @param string|int $user A user name or number.
 * @throws FilesystemException
 *
 */
function chown(string $filename, $user): void
{
    error_clear_last();
    $result = \chown($filename, $user);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * Makes a copy of the file source to
 * dest.
 *
 * If you wish to move a file, use the rename function.
 *
 * @param string $source Path to the source file.
 * @param string $dest The destination path. If dest is a URL, the
 * copy operation may fail if the wrapper does not support overwriting of
 * existing files.
 *
 * If the destination file already exists, it will be overwritten.
 * @param resource $context A valid context resource created with
 * stream_context_create.
 * @throws FilesystemException
 *
 */
function copy(string $source, string $dest, $context = null): void
{
    error_clear_last();
    if ($context !== null) {
        $result = \copy($source, $dest, $context);
    } else {
        $result = \copy($source, $dest);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * Given a string containing a directory, this function will return the
 * number of bytes available on the corresponding filesystem or disk
 * partition.
 *
 * @param string $directory A directory of the filesystem or disk partition.
 *
 * Given a file name instead of a directory, the behaviour of the
 * function is unspecified and may differ between operating systems and
 * PHP versions.
 * @return float Returns the number of available bytes as a float.
 * @throws FilesystemException
 *
 */
function disk_free_space(string $directory): float
{
    error_clear_last();
    $result = \disk_free_space($directory);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Given a string containing a directory, this function will return the total
 * number of bytes on the corresponding filesystem or disk partition.
 *
 * @param string $directory A directory of the filesystem or disk partition.
 * @return float Returns the total number of bytes as a float.
 * @throws FilesystemException
 *
 */
function disk_total_space(string $directory): float
{
    error_clear_last();
    $result = \disk_total_space($directory);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * The file pointed to by handle is closed.
 *
 * @param resource $handle The file pointer must be valid, and must point to a file successfully
 * opened by fopen or fsockopen.
 * @throws FilesystemException
 *
 */
function fclose($handle): void
{
    error_clear_last();
    $result = \fclose($handle);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * This function forces a write of all buffered output to the resource
 * pointed to by the file handle.
 *
 * @param resource $handle The file pointer must be valid, and must point to
 * a file successfully opened by fopen or
 * fsockopen (and not yet closed by
 * fclose).
 * @throws FilesystemException
 *
 */
function fflush($handle): void
{
    error_clear_last();
    $result = \fflush($handle);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * This function is similar to file, except that
 * file_get_contents returns the file in a
 * string, starting at the specified offset
 * up to maxlen bytes. On failure,
 * file_get_contents will return FALSE.
 *
 * file_get_contents is the preferred way to read the
 * contents of a file into a string.  It will use memory mapping techniques if
 * supported by your OS to enhance performance.
 *
 * @param string $filename Name of the file to read.
 * @param bool $use_include_path The FILE_USE_INCLUDE_PATH constant can be used
 * to trigger include path
 * search.
 * This is not possible if strict typing
 * is enabled, since FILE_USE_INCLUDE_PATH is an
 * int. Use TRUE instead.
 * @param resource|null $context A valid context resource created with
 * stream_context_create. If you don't need to use a
 * custom context, you can skip this parameter by NULL.
 * @param int $offset The offset where the reading starts on the original stream.
 * Negative offsets count from the end of the stream.
 *
 * Seeking (offset) is not supported with remote files.
 * Attempting to seek on non-local files may work with small offsets, but this
 * is unpredictable because it works on the buffered stream.
 * @param int $maxlen Maximum length of data read. The default is to read until end
 * of file is reached. Note that this parameter is applied to the
 * stream processed by the filters.
 * @return string The function returns the read data.
 * @throws FilesystemException
 *
 */
function file_get_contents(string $filename, bool $use_include_path = false, $context = null, int $offset = 0, int $maxlen = null): string
{
    error_clear_last();
    if ($maxlen !== null) {
        $result = \file_get_contents($filename, $use_include_path, $context, $offset, $maxlen);
    } elseif ($offset !== 0) {
        $result = \file_get_contents($filename, $use_include_path, $context, $offset);
    } elseif ($context !== null) {
        $result = \file_get_contents($filename, $use_include_path, $context);
    } else {
        $result = \file_get_contents($filename, $use_include_path);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * This function is identical to calling fopen,
 * fwrite and fclose successively
 * to write data to a file.
 *
 * If filename does not exist, the file is created.
 * Otherwise, the existing file is overwritten, unless the
 * FILE_APPEND flag is set.
 *
 * @param string $filename Path to the file where to write the data.
 * @param mixed $data The data to write. Can be either a string, an
 * array or a stream resource.
 *
 * If data is a stream resource, the
 * remaining buffer of that stream will be copied to the specified file.
 * This is similar with using stream_copy_to_stream.
 *
 * You can also specify the data parameter as a single
 * dimension array. This is equivalent to
 * file_put_contents($filename, implode('', $array)).
 * @param int $flags The value of flags can be any combination of
 * the following flags, joined with the binary OR (|)
 * operator.
 *
 *
 * Available flags
 *
 *
 *
 * Flag
 * Description
 *
 *
 *
 *
 *
 * FILE_USE_INCLUDE_PATH
 *
 *
 * Search for filename in the include directory.
 * See include_path for more
 * information.
 *
 *
 *
 *
 * FILE_APPEND
 *
 *
 * If file filename already exists, append
 * the data to the file instead of overwriting it.
 *
 *
 *
 *
 * LOCK_EX
 *
 *
 * Acquire an exclusive lock on the file while proceeding to the
 * writing. In other words, a flock call happens
 * between the fopen call and the
 * fwrite call. This is not identical to an
 * fopen call with mode "x".
 *
 *
 *
 *
 *
 * @param resource $context A valid context resource created with
 * stream_context_create.
 * @return int This function returns the number of bytes that were written to the file.
 * @throws FilesystemException
 *
 */
function file_put_contents(string $filename, $data, int $flags = 0, $context = null): int
{
    error_clear_last();
    if ($context !== null) {
        $result = \file_put_contents($filename, $data, $flags, $context);
    } else {
        $result = \file_put_contents($filename, $data, $flags);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Reads an entire file into an array.
 *
 * @param string $filename Path to the file.
 * @param int $flags The optional parameter flags can be one, or
 * more, of the following constants:
 *
 *
 *
 * FILE_USE_INCLUDE_PATH
 *
 *
 *
 * Search for the file in the include_path.
 *
 *
 *
 *
 *
 * FILE_IGNORE_NEW_LINES
 *
 *
 *
 * Omit newline at the end of each array element
 *
 *
 *
 *
 *
 * FILE_SKIP_EMPTY_LINES
 *
 *
 *
 * Skip empty lines
 *
 *
 *
 *
 * @param resource $context
 * @return array Returns the file in an array. Each element of the array corresponds to a
 * line in the file, with the newline still attached. Upon failure,
 * file returns FALSE.
 * @throws FilesystemException
 *
 */
function file(string $filename, int $flags = 0, $context = null): array
{
    error_clear_last();
    if ($context !== null) {
        $result = \file($filename, $flags, $context);
    } else {
        $result = \file($filename, $flags);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param string $filename Path to the file.
 * @return int Returns the time the file was last accessed.
 * The time is returned as a Unix timestamp.
 * @throws FilesystemException
 *
 */
function fileatime(string $filename): int
{
    error_clear_last();
    $result = \fileatime($filename);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Gets the inode change time of a file.
 *
 * @param string $filename Path to the file.
 * @return int Returns the time the file was last changed.
 * The time is returned as a Unix timestamp.
 * @throws FilesystemException
 *
 */
function filectime(string $filename): int
{
    error_clear_last();
    $result = \filectime($filename);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Gets the file inode.
 *
 * @param string $filename Path to the file.
 * @return int Returns the inode number of the file.
 * @throws FilesystemException
 *
 */
function fileinode(string $filename): int
{
    error_clear_last();
    $result = \fileinode($filename);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * This function returns the time when the data blocks of a file were being
 * written to, that is, the time when the content of the file was changed.
 *
 * @param string $filename Path to the file.
 * @return int Returns the time the file was last modified.
 * The time is returned as a Unix timestamp, which is
 * suitable for the date function.
 * @throws FilesystemException
 *
 */
function filemtime(string $filename): int
{
    error_clear_last();
    $result = \filemtime($filename);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Gets the file owner.
 *
 * @param string $filename Path to the file.
 * @return int Returns the user ID of the owner of the file.
 * The user ID is returned in numerical format, use
 * posix_getpwuid to resolve it to a username.
 * @throws FilesystemException
 *
 */
function fileowner(string $filename): int
{
    error_clear_last();
    $result = \fileowner($filename);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Gets the size for the given file.
 *
 * @param string $filename Path to the file.
 * @return int Returns the size of the file in bytes, or FALSE (and generates an error
 * of level E_WARNING) in case of an error.
 * @throws FilesystemException
 *
 */
function filesize(string $filename): int
{
    error_clear_last();
    $result = \filesize($filename);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * flock allows you to perform a simple reader/writer
 * model which can be used on virtually every platform (including most Unix
 * derivatives and even Windows).
 *
 * On versions of PHP before 5.3.2, the lock is released also by
 * fclose (which is also called automatically when script
 * finished).
 *
 * PHP supports a portable way of locking complete files in an advisory way
 * (which means all accessing programs have to use the same way of locking
 * or it will not work). By default, this function will block until the
 * requested lock is acquired; this may be controlled with the LOCK_NB option documented below.
 *
 * @param resource $handle A file system pointer resource
 * that is typically created using fopen.
 * @param int $operation operation is one of the following:
 *
 *
 *
 * LOCK_SH to acquire a shared lock (reader).
 *
 *
 *
 *
 * LOCK_EX to acquire an exclusive lock (writer).
 *
 *
 *
 *
 * LOCK_UN to release a lock (shared or exclusive).
 *
 *
 *
 *
 * It is also possible to add LOCK_NB as a bitmask to one
 * of the above operations, if flock should not
 * block during the locking attempt.
 * @param int|null $wouldblock The optional third argument is set to 1 if the lock would block
 * (EWOULDBLOCK errno condition).
 * @throws FilesystemException
 *
 */
function flock($handle, int $operation, ?int &$wouldblock = null): void
{
    error_clear_last();
    $result = \flock($handle, $operation, $wouldblock);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * fopen binds a named resource, specified by
 * filename, to a stream.
 *
 * @param string $filename If filename is of the form "scheme://...", it
 * is assumed to be a URL and PHP will search for a protocol handler
 * (also known as a wrapper) for that scheme. If no wrappers for that
 * protocol are registered, PHP will emit a notice to help you track
 * potential problems in your script and then continue as though
 * filename specifies a regular file.
 *
 * If PHP has decided that filename specifies
 * a local file, then it will try to open a stream on that file.
 * The file must be accessible to PHP, so you need to ensure that
 * the file access permissions allow this access.
 * If you have enabled
 * open_basedir further
 * restrictions may apply.
 *
 * If PHP has decided that filename specifies
 * a registered protocol, and that protocol is registered as a
 * network URL, PHP will check to make sure that
 * allow_url_fopen is
 * enabled. If it is switched off, PHP will emit a warning and
 * the fopen call will fail.
 *
 * The list of supported protocols can be found in . Some protocols (also referred to as
 * wrappers) support context
 * and/or php.ini options. Refer to the specific page for the
 * protocol in use for a list of options which can be set. (e.g.
 * php.ini value user_agent used by the
 * http wrapper).
 *
 * On the Windows platform, be careful to escape any backslashes
 * used in the path to the file, or use forward slashes.
 *
 *
 *
 * ]]>
 *
 *
 * @param string $mode The mode parameter specifies the type of access
 * you require to the stream.  It may be any of the following:
 *
 *
 * A list of possible modes for fopen
 * using mode
 *
 *
 *
 *
 * mode
 * Description
 *
 *
 *
 *
 * 'r'
 *
 * Open for reading only; place the file pointer at the
 * beginning of the file.
 *
 *
 *
 * 'r+'
 *
 * Open for reading and writing; place the file pointer at
 * the beginning of the file.
 *
 *
 *
 * 'w'
 *
 * Open for writing only; place the file pointer at the
 * beginning of the file and truncate the file to zero length.
 * If the file does not exist, attempt to create it.
 *
 *
 *
 * 'w+'
 *
 * Open for reading and writing; place the file pointer at
 * the beginning of the file and truncate the file to zero
 * length.  If the file does not exist, attempt to create it.
 *
 *
 *
 * 'a'
 *
 * Open for writing only; place the file pointer at the end of
 * the file. If the file does not exist, attempt to create it.
 * In this mode, fseek has no effect, writes are always appended.
 *
 *
 *
 * 'a+'
 *
 * Open for reading and writing; place the file pointer at
 * the end of the file. If the file does not exist, attempt to
 * create it. In this mode, fseek only affects
 * the reading position, writes are always appended.
 *
 *
 *
 * 'x'
 *
 * Create and open for writing only; place the file pointer at the
 * beginning of the file.  If the file already exists, the
 * fopen call will fail by returning FALSE and
 * generating an error of level E_WARNING.  If
 * the file does not exist, attempt to create it.  This is equivalent
 * to specifying O_EXCL|O_CREAT flags for the
 * underlying open(2) system call.
 *
 *
 *
 * 'x+'
 *
 * Create and open for reading and writing; otherwise it has the
 * same behavior as 'x'.
 *
 *
 *
 * 'c'
 *
 * Open the file for writing only. If the file does not exist, it is
 * created. If it exists, it is neither truncated (as opposed to
 * 'w'), nor the call to this function fails (as is
 * the case with 'x'). The file pointer is
 * positioned on the beginning of the file. This may be useful if it's
 * desired to get an advisory lock (see flock)
 * before attempting to modify the file, as using
 * 'w' could truncate the file before the lock
 * was obtained (if truncation is desired,
 * ftruncate can be used after the lock is
 * requested).
 *
 *
 *
 * 'c+'
 *
 * Open the file for reading and writing; otherwise it has the same
 * behavior as 'c'.
 *
 *
 *
 * 'e'
 *
 * Set close-on-exec flag on the opened file descriptor. Only
 * available in PHP compiled on POSIX.1-2008 conform systems.
 *
 *
 *
 *
 *
 *
 * Different operating system families have different line-ending
 * conventions.  When you write a text file and want to insert a line
 * break, you need to use the correct line-ending character(s) for your
 * operating system.  Unix based systems use \n as the
 * line ending character, Windows based systems use \r\n
 * as the line ending characters and Macintosh based systems (Mac OS Classic) used
 * \r as the line ending character.
 *
 * If you use the wrong line ending characters when writing your files, you
 * might find that other applications that open those files will "look
 * funny".
 *
 * Windows offers a text-mode translation flag ('t')
 * which will transparently translate \n to
 * \r\n when working with the file.  In contrast, you
 * can also use 'b' to force binary mode, which will not
 * translate your data.  To use these flags, specify either
 * 'b' or 't' as the last character
 * of the mode parameter.
 *
 * The default translation mode is 'b'.
 * You can use the 't'
 * mode if you are working with plain-text files and you use
 * \n to delimit your line endings in your script, but
 * expect your files to be readable with applications such as old versions of notepad.  You
 * should use the 'b' in all other cases.
 *
 * If you specify the 't' flag when working with binary files, you
 * may experience strange problems with your data, including broken image
 * files and strange problems with \r\n characters.
 *
 * For portability, it is also strongly recommended that
 * you re-write code that uses or relies upon the 't'
 * mode so that it uses the correct line endings and
 * 'b' mode instead.
 * @param bool $use_include_path The optional third use_include_path parameter
 * can be set to '1' or TRUE if you want to search for the file in the
 * include_path, too.
 * @param resource $context
 * @return resource Returns a file pointer resource on success
 * @throws FilesystemException
 *
 */
function fopen(string $filename, string $mode, bool $use_include_path = false, $context = null)
{
    error_clear_last();
    if ($context !== null) {
        $result = \fopen($filename, $mode, $use_include_path, $context);
    } else {
        $result = \fopen($filename, $mode, $use_include_path);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * fputcsv formats a line (passed as a
 * fields array) as CSV and writes it (terminated by a
 * newline) to the specified file handle.
 *
 * @param resource $handle The file pointer must be valid, and must point to
 * a file successfully opened by fopen or
 * fsockopen (and not yet closed by
 * fclose).
 * @param array $fields An array of strings.
 * @param string $delimiter The optional delimiter parameter sets the field
 * delimiter (one character only).
 * @param string $enclosure The optional enclosure parameter sets the field
 * enclosure (one character only).
 * @param string $escape_char The optional escape_char parameter sets the
 * escape character (at most one character).
 * An empty string ("") disables the proprietary escape mechanism.
 * @return int Returns the length of the written string.
 * @throws FilesystemException
 *
 */
function fputcsv($handle, array $fields, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): int
{
    error_clear_last();
    $result = \fputcsv($handle, $fields, $delimiter, $enclosure, $escape_char);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * fread reads up to
 * length bytes from the file pointer
 * referenced by handle. Reading stops as soon as one
 * of the following conditions is met:
 *
 *
 *
 * length bytes have been read
 *
 *
 *
 *
 * EOF (end of file) is reached
 *
 *
 *
 *
 * a packet becomes available or the
 * socket timeout occurs (for network streams)
 *
 *
 *
 *
 * if the stream is read buffered and it does not represent a plain file, at
 * most one read of up to a number of bytes equal to the chunk size (usually
 * 8192) is made; depending on the previously buffered data, the size of the
 * returned data may be larger than the chunk size.
 *
 *
 *
 *
 * @param resource $handle A file system pointer resource
 * that is typically created using fopen.
 * @param int $length Up to length number of bytes read.
 * @return string Returns the read string.
 * @throws FilesystemException
 *
 */
function fread($handle, int $length): string
{
    error_clear_last();
    $result = \fread($handle, $length);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Takes the filepointer, handle, and truncates the file to
 * length, size.
 *
 * @param resource $handle The file pointer.
 *
 * The handle must be open for writing.
 * @param int $size The size to truncate to.
 *
 * If size is larger than the file then the file
 * is extended with null bytes.
 *
 * If size is smaller than the file then the file
 * is truncated to that size.
 * @throws FilesystemException
 *
 */
function ftruncate($handle, int $size): void
{
    error_clear_last();
    $result = \ftruncate($handle, $size);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 *
 *
 * @param resource $handle A file system pointer resource
 * that is typically created using fopen.
 * @param string $string The string that is to be written.
 * @param int $length If the length argument is given, writing will
 * stop after length bytes have been written or
 * the end of string is reached, whichever comes
 * first.
 *
 * Note that if the length argument is given,
 * then the magic_quotes_runtime
 * configuration option will be ignored and no slashes will be
 * stripped from string.
 * @return int
 * @throws FilesystemException
 *
 */
function fwrite($handle, string $string, int $length = null): int
{
    error_clear_last();
    if ($length !== null) {
        $result = \fwrite($handle, $string, $length);
    } else {
        $result = \fwrite($handle, $string);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * The glob function searches for all the pathnames
 * matching pattern according to the rules used by
 * the libc glob() function, which is similar to the rules used by common
 * shells.
 *
 * @param string $pattern The pattern. No tilde expansion or parameter substitution is done.
 *
 * Special characters:
 *
 *
 *
 * * - Matches zero or more characters.
 *
 *
 *
 *
 * ? - Matches exactly one character (any character).
 *
 *
 *
 *
 * [...] - Matches one character from a group of
 * characters. If the first character is !,
 * matches any character not in the group.
 *
 *
 *
 *
 * \ - Escapes the following character,
 * except when the GLOB_NOESCAPE flag is used.
 *
 *
 *
 * @param int $flags Valid flags:
 *
 *
 *
 * GLOB_MARK - Adds a slash (a backslash on Windows) to each directory returned
 *
 *
 *
 *
 * GLOB_NOSORT - Return files as they appear in the
 * directory (no sorting). When this flag is not used, the pathnames are
 * sorted alphabetically
 *
 *
 *
 *
 * GLOB_NOCHECK - Return the search pattern if no
 * files matching it were found
 *
 *
 *
 *
 * GLOB_NOESCAPE - Backslashes do not quote
 * metacharacters
 *
 *
 *
 *
 * GLOB_BRACE - Expands {a,b,c} to match 'a', 'b',
 * or 'c'
 *
 *
 *
 *
 * GLOB_ONLYDIR - Return only directory entries
 * which match the pattern
 *
 *
 *
 *
 * GLOB_ERR - Stop on read errors (like unreadable
 * directories), by default errors are ignored.
 *
 *
 *
 * @return array Returns an array containing the matched files/directories, an empty array
 * if no file matched.
 * @throws FilesystemException
 *
 */
function glob(string $pattern, int $flags = 0): array
{
    error_clear_last();
    $result = \glob($pattern, $flags);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Attempts to change the group of the symlink filename
 * to group.
 *
 * Only the superuser may change the group of a symlink arbitrarily; other
 * users may change the group of a symlink to any group of which that user is
 * a member.
 *
 * @param string $filename Path to the symlink.
 * @param string|int $group The group specified by name or number.
 * @throws FilesystemException
 *
 */
function lchgrp(string $filename, $group): void
{
    error_clear_last();
    $result = \lchgrp($filename, $group);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * Attempts to change the owner of the symlink filename
 * to user user.
 *
 * Only the superuser may change the owner of a symlink.
 *
 * @param string $filename Path to the file.
 * @param string|int $user User name or number.
 * @throws FilesystemException
 *
 */
function lchown(string $filename, $user): void
{
    error_clear_last();
    $result = \lchown($filename, $user);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * link creates a hard link.
 *
 * @param string $target Target of the link.
 * @param string $link The link name.
 * @throws FilesystemException
 *
 */
function link(string $target, string $link): void
{
    error_clear_last();
    $result = \link($target, $link);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * Attempts to create the directory specified by pathname.
 *
 * @param string $pathname The directory path.
 * @param int $mode The mode is 0777 by default, which means the widest possible
 * access. For more information on modes, read the details
 * on the chmod page.
 *
 * mode is ignored on Windows.
 *
 * Note that you probably want to specify the mode as an octal number,
 * which means it should have a leading zero. The mode is also modified
 * by the current umask, which you can change using
 * umask.
 * @param bool $recursive Allows the creation of nested directories specified in the
 * pathname.
 * @param resource $context
 * @throws FilesystemException
 *
 */
function mkdir(string $pathname, int $mode = 0777, bool $recursive = false, $context = null): void
{
    error_clear_last();
    if ($context !== null) {
        $result = \mkdir($pathname, $mode, $recursive, $context);
    } else {
        $result = \mkdir($pathname, $mode, $recursive);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * parse_ini_file loads in the
 * ini file specified in filename,
 * and returns the settings in it in an associative array.
 *
 * The structure of the ini file is the same as the php.ini's.
 *
 * @param string $filename The filename of the ini file being parsed. If a relative path is used,
 * it is evaluated relative to the current working directory, then the
 * include_path.
 * @param bool $process_sections By setting the process_sections
 * parameter to TRUE, you get a multidimensional array, with
 * the section names and settings included. The default
 * for process_sections is FALSE
 * @param int $scanner_mode Can either be INI_SCANNER_NORMAL (default) or
 * INI_SCANNER_RAW. If INI_SCANNER_RAW
 * is supplied, then option values will not be parsed.
 *
 *
 * As of PHP 5.6.1 can also be specified as INI_SCANNER_TYPED.
 * In this mode boolean, null and integer types are preserved when possible.
 * String values "true", "on" and "yes"
 * are converted to TRUE. "false", "off", "no"
 * and "none" are considered FALSE. "null" is converted to NULL
 * in typed mode. Also, all numeric strings are converted to integer type if it is possible.
 * @return array The settings are returned as an associative array on success.
 * @throws FilesystemException
 *
 */
function parse_ini_file(string $filename, bool $process_sections = false, int $scanner_mode = INI_SCANNER_NORMAL): array
{
    error_clear_last();
    $result = \parse_ini_file($filename, $process_sections, $scanner_mode);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * parse_ini_string returns the settings in string
 * ini in an associative array.
 *
 * The structure of the ini string is the same as the php.ini's.
 *
 * @param string $ini The contents of the ini file being parsed.
 * @param bool $process_sections By setting the process_sections
 * parameter to TRUE, you get a multidimensional array, with
 * the section names and settings included. The default
 * for process_sections is FALSE
 * @param int $scanner_mode Can either be INI_SCANNER_NORMAL (default) or
 * INI_SCANNER_RAW. If INI_SCANNER_RAW
 * is supplied, then option values will not be parsed.
 *
 *
 * As of PHP 5.6.1 can also be specified as INI_SCANNER_TYPED.
 * In this mode boolean, null and integer types are preserved when possible.
 * String values "true", "on" and "yes"
 * are converted to TRUE. "false", "off", "no"
 * and "none" are considered FALSE. "null" is converted to NULL
 * in typed mode. Also, all numeric strings are converted to integer type if it is possible.
 * @return array The settings are returned as an associative array on success.
 * @throws FilesystemException
 *
 */
function parse_ini_string(string $ini, bool $process_sections = false, int $scanner_mode = INI_SCANNER_NORMAL): array
{
    error_clear_last();
    $result = \parse_ini_string($ini, $process_sections, $scanner_mode);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Reads a file and writes it to the output buffer.
 *
 * @param string $filename The filename being read.
 * @param bool $use_include_path You can use the optional second parameter and set it to TRUE, if
 * you want to search for the file in the include_path, too.
 * @param resource $context A context stream resource.
 * @return int Returns the number of bytes read from the file on success
 * @throws FilesystemException
 *
 */
function readfile(string $filename, bool $use_include_path = false, $context = null): int
{
    error_clear_last();
    if ($context !== null) {
        $result = \readfile($filename, $use_include_path, $context);
    } else {
        $result = \readfile($filename, $use_include_path);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * readlink does the same as the readlink C function.
 *
 * @param string $path The symbolic link path.
 * @return string Returns the contents of the symbolic link path.
 * @throws FilesystemException
 *
 */
function readlink(string $path): string
{
    error_clear_last();
    $result = \readlink($path);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * realpath expands all symbolic links and
 * resolves references to /./, /../ and extra / characters in
 * the input path and returns the canonicalized
 * absolute pathname.
 *
 * @param string $path The path being checked.
 *
 *
 * Whilst a path must be supplied, the value can be an empty string.
 * In this case, the value is interpreted as the current directory.
 *
 *
 *
 * Whilst a path must be supplied, the value can be an empty string.
 * In this case, the value is interpreted as the current directory.
 * @return string Returns the canonicalized absolute pathname on success. The resulting path
 * will have no symbolic link, /./ or /../ components. Trailing delimiters,
 * such as \ and /, are also removed.
 *
 * realpath returns FALSE on failure, e.g. if
 * the file does not exist.
 * @throws FilesystemException
 *
 */
function realpath(string $path): string
{
    error_clear_last();
    $result = \realpath($path);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Attempts to rename oldname to
 * newname, moving it between directories if necessary.
 * If renaming a file and newname exists,
 * it will be overwritten. If renaming a directory and
 * newname exists,
 * this function will emit a warning.
 *
 * @param string $oldname The old name.
 *
 * The wrapper used in oldname
 * must match the wrapper used in
 * newname.
 * @param string $newname The new name.
 * @param resource $context
 * @throws FilesystemException
 *
 */
function rename(string $oldname, string $newname, $context = null): void
{
    error_clear_last();
    if ($context !== null) {
        $result = \rename($oldname, $newname, $context);
    } else {
        $result = \rename($oldname, $newname);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * Sets the file position indicator for handle
 * to the beginning of the file stream.
 *
 * @param resource $handle The file pointer must be valid, and must point to a file
 * successfully opened by fopen.
 * @throws FilesystemException
 *
 */
function rewind($handle): void
{
    error_clear_last();
    $result = \rewind($handle);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * Attempts to remove the directory named by dirname.
 * The directory must be empty, and the relevant permissions must permit this.
 * A E_WARNING level error will be generated on failure.
 *
 * @param string $dirname Path to the directory.
 * @param resource $context
 * @throws FilesystemException
 *
 */
function rmdir(string $dirname, $context = null): void
{
    error_clear_last();
    if ($context !== null) {
        $result = \rmdir($dirname, $context);
    } else {
        $result = \rmdir($dirname);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * symlink creates a symbolic link to the existing
 * target with the specified name
 * link.
 *
 * @param string $target Target of the link.
 * @param string $link The link name.
 * @throws FilesystemException
 *
 */
function symlink(string $target, string $link): void
{
    error_clear_last();
    $result = \symlink($target, $link);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * Creates a file with a unique filename, with access permission set to 0600, in the specified directory.
 * If the directory does not exist or is not writable, tempnam may
 * generate a file in the system's temporary directory, and return
 * the full path to that file, including its name.
 *
 * @param string $dir The directory where the temporary filename will be created.
 * @param string $prefix The prefix of the generated temporary filename.
 * @return string Returns the new temporary filename (with path).
 * @throws FilesystemException
 *
 */
function tempnam(string $dir, string $prefix): string
{
    error_clear_last();
    $result = \tempnam($dir, $prefix);
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Creates a temporary file with a unique name in read-write (w+) mode and
 * returns a file handle.
 *
 * The file is automatically removed when closed (for example, by calling
 * fclose, or when there are no remaining references to
 * the file handle returned by tmpfile), or when the
 * script ends.
 *
 * @return resource Returns a file handle, similar to the one returned by
 * fopen, for the new file.
 * @throws FilesystemException
 *
 */
function tmpfile()
{
    error_clear_last();
    $result = \tmpfile();
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
    return $result;
}


/**
 * Attempts to set the access and modification times of the file named in the
 * filename parameter to the value given in
 * time.
 * Note that the access time is always modified, regardless of the number
 * of parameters.
 *
 * If the file does not exist, it will be created.
 *
 * @param string $filename The name of the file being touched.
 * @param int $time The touch time. If time is not supplied,
 * the current system time is used.
 * @param int $atime If present, the access time of the given filename is set to
 * the value of atime. Otherwise, it is set to
 * the value passed to the time parameter.
 * If neither are present, the current system time is used.
 * @throws FilesystemException
 *
 */
function touch(string $filename, int $time = null, int $atime = null): void
{
    error_clear_last();
    if ($atime !== null) {
        $result = \touch($filename, $time, $atime);
    } elseif ($time !== null) {
        $result = \touch($filename, $time);
    } else {
        $result = \touch($filename);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}


/**
 * Deletes filename.  Similar to the Unix C unlink()
 * function. An E_WARNING level error will be generated on
 * failure.
 *
 * @param string $filename Path to the file.
 * @param resource $context
 * @throws FilesystemException
 *
 */
function unlink(string $filename, $context = null): void
{
    error_clear_last();
    if ($context !== null) {
        $result = \unlink($filename, $context);
    } else {
        $result = \unlink($filename);
    }
    if ($result === false) {
        throw FilesystemException::createFromPhpError();
    }
}

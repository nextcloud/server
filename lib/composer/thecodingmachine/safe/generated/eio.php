<?php

namespace Safe;

use Safe\Exceptions\EioException;

/**
 * eio_busy artificially increases load taking
 * delay seconds to execute. May be used for debugging,
 * or benchmarking.
 *
 * @param int $delay Delay in seconds
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback This callback is called when all the group requests are done.
 * @param mixed $data Arbitrary variable passed to callback.
 * @return resource eio_busy returns request resource on success.
 * @throws EioException
 *
 */
function eio_busy(int $delay, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_busy($delay, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_chmod changes file, or directory permissions. The
 * new permissions are specified by mode.
 *
 * @param string $path Path to the target file or directory
 * Avoid relative
 * paths
 * @param int $mode The new permissions. E.g. 0644.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_chmod returns request resource on success.
 * @throws EioException
 *
 */
function eio_chmod(string $path, int $mode, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_chmod($path, $mode, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * Changes file, or directory permissions.
 *
 * @param string $path Path to file or directory.
 * Avoid relative
 * paths
 * @param int $uid User ID. Is ignored when equal to -1.
 * @param int $gid Group ID. Is ignored when equal to -1.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_chown returns request resource on success.
 * @throws EioException
 *
 */
function eio_chown(string $path, int $uid, int $gid = -1, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_chown($path, $uid, $gid, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_close closes file specified by
 * fd.
 *
 * @param mixed $fd Stream, Socket resource, or numeric file descriptor
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_close returns request resource on success.
 * @throws EioException
 *
 */
function eio_close($fd, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_close($fd, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_custom executes custom function specified by
 * execute processing it just like any other eio_* call.
 *
 * @param callable $execute Specifies the request function that should match the following prototype:
 *
 *
 * callback is event completion callback that should match the following
 * prototype:
 *
 *
 * data is the data passed to
 * execute via data argument
 * without modifications
 * result value returned by execute
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_custom returns request resource on success.
 * @throws EioException
 *
 */
function eio_custom(callable $execute, int $pri, callable $callback, $data = null)
{
    error_clear_last();
    $result = \eio_custom($execute, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_dup2 duplicates file descriptor.
 *
 * @param mixed $fd Source stream, Socket resource, or numeric file descriptor
 * @param mixed $fd2 Target stream, Socket resource, or numeric file descriptor
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_dup2 returns request resource on success.
 * @throws EioException
 *
 */
function eio_dup2($fd, $fd2, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_dup2($fd, $fd2, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_event_loop polls libeio until all requests proceeded.
 *
 * @throws EioException
 *
 */
function eio_event_loop(): void
{
    error_clear_last();
    $result = \eio_event_loop();
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
}


/**
 * eio_fallocate allows the caller to directly manipulate the allocated disk space for the
 * file specified by fd file descriptor for the byte
 * range starting at offset and continuing for
 * length bytes.
 *
 * @param mixed $fd Stream, Socket resource, or numeric file descriptor, e.g. returned by eio_open.
 * @param int $mode Currently only one flag is supported for mode:
 * EIO_FALLOC_FL_KEEP_SIZE (the same as POSIX constant
 * FALLOC_FL_KEEP_SIZE).
 * @param int $offset Specifies start of the byte range.
 * @param int $length Specifies length the byte range.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_fallocate returns request resource on success.
 * @throws EioException
 *
 */
function eio_fallocate($fd, int $mode, int $offset, int $length, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_fallocate($fd, $mode, $offset, $length, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_fchmod changes permissions for the file specified
 * by fd file descriptor.
 *
 * @param mixed $fd Stream, Socket resource, or numeric file descriptor, e.g. returned by eio_open.
 * @param int $mode The new permissions. E.g. 0644.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_fchmod returns request resource on success.
 * @throws EioException
 *
 */
function eio_fchmod($fd, int $mode, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_fchmod($fd, $mode, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_fdatasync synchronizes a file's in-core state with storage device.
 *
 * @param mixed $fd Stream, Socket resource, or numeric file descriptor, e.g. returned by eio_open.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_fdatasync returns request resource on success.
 * @throws EioException
 *
 */
function eio_fdatasync($fd, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_fdatasync($fd, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_fstat returns file status information in
 * result argument of callback
 *
 * @param mixed $fd Stream, Socket resource, or numeric file descriptor.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_busy returns request resource on success.
 * @throws EioException
 *
 */
function eio_fstat($fd, int $pri, callable $callback, $data = null)
{
    error_clear_last();
    if ($data !== null) {
        $result = \eio_fstat($fd, $pri, $callback, $data);
    } else {
        $result = \eio_fstat($fd, $pri, $callback);
    }
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_fstatvfs returns file system statistics in
 * result of callback.
 *
 * @param mixed $fd A file descriptor of a file within the mounted file system.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_fstatvfs returns request resource on success.
 * @throws EioException
 *
 */
function eio_fstatvfs($fd, int $pri, callable $callback, $data = null)
{
    error_clear_last();
    if ($data !== null) {
        $result = \eio_fstatvfs($fd, $pri, $callback, $data);
    } else {
        $result = \eio_fstatvfs($fd, $pri, $callback);
    }
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * Synchronize a file's in-core state with storage device
 *
 * @param mixed $fd Stream, Socket resource, or numeric file descriptor.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_fsync returns request resource on success.
 * @throws EioException
 *
 */
function eio_fsync($fd, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_fsync($fd, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_ftruncate causes a regular file referenced by
 * fd file descriptor to be truncated to precisely
 * length bytes.
 *
 * @param mixed $fd Stream, Socket resource, or numeric file descriptor.
 * @param int $offset Offset from beginning of the file
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_ftruncate returns request resource on success.
 * @throws EioException
 *
 */
function eio_ftruncate($fd, int $offset = 0, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_ftruncate($fd, $offset, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_futime changes file last access and modification
 * times.
 *
 * @param mixed $fd Stream, Socket resource, or numeric file descriptor, e.g. returned by eio_open
 * @param float $atime Access time
 * @param float $mtime Modification time
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_futime returns request resource on success.
 * @throws EioException
 *
 */
function eio_futime($fd, float $atime, float $mtime, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_futime($fd, $atime, $mtime, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_grp creates a request group.
 *
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param string $data is custom data passed to the request.
 * @return resource eio_grp returns request group resource on success.
 * @throws EioException
 *
 */
function eio_grp(callable $callback, string $data = null)
{
    error_clear_last();
    $result = \eio_grp($callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_lstat returns file status information in
 * result argument of callback
 *
 * @param string $path The file path
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_lstat returns request resource on success.
 * @throws EioException
 *
 */
function eio_lstat(string $path, int $pri, callable $callback, $data = null)
{
    error_clear_last();
    $result = \eio_lstat($path, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_mkdir creates directory with specified access
 * mode.
 *
 * @param string $path Path for the new directory.
 * @param int $mode Access mode, e.g. 0755
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_mkdir returns request resource on success.
 * @throws EioException
 *
 */
function eio_mkdir(string $path, int $mode, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_mkdir($path, $mode, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_mknod creates ordinary or special(often) file.
 *
 * @param string $path Path for the new node(file).
 * @param int $mode Specifies both the permissions to use and the type of node to be
 * created. It should be a combination (using bitwise OR) of one of the
 * file types listed below and the permissions for the new node(e.g. 0640).
 *
 * Possible file types are: EIO_S_IFREG(regular file),
 * EIO_S_IFCHR(character file),
 * EIO_S_IFBLK(block special file),
 * EIO_S_IFIFO(FIFO - named pipe) and
 * EIO_S_IFSOCK(UNIX domain socket).
 *
 * To specify permissions EIO_S_I* constants could be
 * used.
 * @param int $dev If  the  file type is EIO_S_IFCHR or
 * EIO_S_IFBLK then dev specifies the major and minor
 * numbers of the newly created device special file. Otherwise
 * dev ignored. See mknod(2) man page for
 * details.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_mknod returns request resource on success.
 * @throws EioException
 *
 */
function eio_mknod(string $path, int $mode, int $dev, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_mknod($path, $mode, $dev, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_nop does nothing, except go through the whole
 * request cycle. Could be useful in debugging.
 *
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_nop returns request resource on success.
 * @throws EioException
 *
 */
function eio_nop(int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_nop($pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_readahead populates the page cache with data from a file so that subsequent reads from
 * that file will not block on disk I/O. See READAHEAD(2) man page for details.
 *
 * @param mixed $fd Stream, Socket resource, or numeric file descriptor
 * @param int $offset Starting point from which data is to be read.
 * @param int $length Number of bytes to be read.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_readahead returns request resource on success.
 * @throws EioException
 *
 */
function eio_readahead($fd, int $offset, int $length, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_readahead($fd, $offset, $length, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * Reads through a whole directory(via the opendir, readdir and
 * closedir system calls) and returns either the names or an array in
 * result argument of callback
 * function, depending on the flags argument.
 *
 * @param string $path Directory path.
 * @param int $flags Combination of EIO_READDIR_* constants.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param string $data is custom data passed to the request.
 * @return resource eio_readdir returns request resource on success. Sets result argument of
 * callback function according to
 * flags:
 *
 *
 *
 *
 *
 *
 * EIO_READDIR_DENTS
 * (integer)
 *
 *
 *
 * eio_readdir flag. If specified, the result argument of the callback
 * becomes an array with the following keys:
 * 'names' - array of directory names
 * 'dents' - array of struct
 * eio_dirent-like arrays having the following keys each:
 * 'name' - the directory name;
 * 'type' - one of EIO_DT_*
 * constants;
 * 'inode' - the inode number, if available, otherwise
 * unspecified;
 *
 *
 *
 *
 *
 * EIO_READDIR_DIRS_FIRST
 * (integer)
 *
 *
 *
 * When this flag is specified, the names will be returned in an order
 * where likely directories come first, in optimal stat order.
 *
 *
 *
 *
 *
 * EIO_READDIR_STAT_ORDER
 * (integer)
 *
 *
 *
 * When this flag is specified, then the names will be returned in an order
 * suitable for stat'ing each one. When planning to
 * stat all files in the given directory, the
 * returned order will likely be
 * fastest.
 *
 *
 *
 *
 *
 * EIO_READDIR_FOUND_UNKNOWN
 * (integer)
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 * Node types:
 *
 *
 *
 *
 *
 * EIO_DT_UNKNOWN
 * (integer)
 *
 *
 *
 * Unknown node type(very common). Further stat needed.
 *
 *
 *
 *
 *
 * EIO_DT_FIFO
 * (integer)
 *
 *
 *
 * FIFO node type
 *
 *
 *
 *
 *
 * EIO_DT_CHR
 * (integer)
 *
 *
 *
 * Node type
 *
 *
 *
 *
 *
 * EIO_DT_MPC
 * (integer)
 *
 *
 *
 * Multiplexed char device (v7+coherent) node type
 *
 *
 *
 *
 *
 * EIO_DT_DIR
 * (integer)
 *
 *
 *
 * Directory node type
 *
 *
 *
 *
 *
 * EIO_DT_NAM
 * (integer)
 *
 *
 *
 * Xenix special named file node type
 *
 *
 *
 *
 *
 * EIO_DT_BLK
 * (integer)
 *
 *
 *
 * Node type
 *
 *
 *
 *
 *
 * EIO_DT_MPB
 * (integer)
 *
 *
 *
 * Multiplexed block device (v7+coherent)
 *
 *
 *
 *
 *
 * EIO_DT_REG
 * (integer)
 *
 *
 *
 * Node type
 *
 *
 *
 *
 *
 * EIO_DT_NWK
 * (integer)
 *
 *
 *
 *
 *
 *
 *
 *
 * EIO_DT_CMP
 * (integer)
 *
 *
 *
 * HP-UX network special node type
 *
 *
 *
 *
 *
 * EIO_DT_LNK
 * (integer)
 *
 *
 *
 * Link node type
 *
 *
 *
 *
 *
 * EIO_DT_SOCK
 * (integer)
 *
 *
 *
 * Socket node type
 *
 *
 *
 *
 *
 * EIO_DT_DOOR
 * (integer)
 *
 *
 *
 * Solaris door node type
 *
 *
 *
 *
 *
 * EIO_DT_WHT
 * (integer)
 *
 *
 *
 * Node type
 *
 *
 *
 *
 *
 * EIO_DT_MAX
 * (integer)
 *
 *
 *
 * Highest node type value
 *
 *
 *
 *
 *
 *
 *
 * @throws EioException
 *
 */
function eio_readdir(string $path, int $flags, int $pri, callable $callback, string $data = null)
{
    error_clear_last();
    $result = \eio_readdir($path, $flags, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param string $path Source symbolic link path
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param string $data is custom data passed to the request.
 * @return resource eio_readlink returns request resource on success.
 * @throws EioException
 *
 */
function eio_readlink(string $path, int $pri, callable $callback, string $data = null)
{
    error_clear_last();
    $result = \eio_readlink($path, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_rename renames or moves a file to new location.
 *
 * @param string $path Source path
 * @param string $new_path Target path
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_rename returns request resource on success.
 * @throws EioException
 *
 */
function eio_rename(string $path, string $new_path, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_rename($path, $new_path, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_rmdir removes a directory.
 *
 * @param string $path Directory path
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_rmdir returns request resource on success.
 * @throws EioException
 *
 */
function eio_rmdir(string $path, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_rmdir($path, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_seek repositions the offset of the open file associated with
 * stream, Socket resource, or file descriptor specified by fd to the argument offset according to the directive whence as follows:
 *
 * EIO_SEEK_SET - Set position equal to offset bytes.
 * EIO_SEEK_CUR - Set position to current location plus offset.
 * EIO_SEEK_END - Set position to end-of-file plus offset.
 *
 *
 * @param mixed $fd Stream, Socket resource, or numeric file descriptor
 * @param int $offset Starting point from which data is to be read.
 * @param int $whence Number of bytes to be read.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_seek returns request resource on success.
 * @throws EioException
 *
 */
function eio_seek($fd, int $offset, int $whence, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_seek($fd, $offset, $whence, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_sendfile copies  data between one file descriptor
 * and another. See SENDFILE(2) man page for details.
 *
 * @param mixed $out_fd Output stream, Socket resource, or file descriptor. Should be opened for writing.
 * @param mixed $in_fd Input stream, Socket resource, or file descriptor. Should be opened for reading.
 * @param int $offset Offset within the source file.
 * @param int $length Number of bytes to copy.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param string $data is custom data passed to the request.
 * @return resource eio_sendfile returns request resource on success.
 * @throws EioException
 *
 */
function eio_sendfile($out_fd, $in_fd, int $offset, int $length, int $pri = null, callable $callback = null, string $data = null)
{
    error_clear_last();
    if ($data !== null) {
        $result = \eio_sendfile($out_fd, $in_fd, $offset, $length, $pri, $callback, $data);
    } elseif ($callback !== null) {
        $result = \eio_sendfile($out_fd, $in_fd, $offset, $length, $pri, $callback);
    } elseif ($pri !== null) {
        $result = \eio_sendfile($out_fd, $in_fd, $offset, $length, $pri);
    } else {
        $result = \eio_sendfile($out_fd, $in_fd, $offset, $length);
    }
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_stat returns file status information in
 * result argument of callback
 *
 * @param string $path The file path
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_stat returns request resource on success. On success assigns result argument of
 * callback to an array.
 * @throws EioException
 *
 */
function eio_stat(string $path, int $pri, callable $callback, $data = null)
{
    error_clear_last();
    $result = \eio_stat($path, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_statvfs returns file system statistics information in
 * result argument of callback
 *
 * @param string $path Pathname of any file within the mounted file system
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_statvfs returns request resource on success. On success assigns result argument of
 * callback to an array.
 * @throws EioException
 *
 */
function eio_statvfs(string $path, int $pri, callable $callback, $data = null)
{
    error_clear_last();
    if ($data !== null) {
        $result = \eio_statvfs($path, $pri, $callback, $data);
    } else {
        $result = \eio_statvfs($path, $pri, $callback);
    }
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_symlink creates a symbolic link
 * new_path to path.
 *
 * @param string $path Source path
 * @param string $new_path Target path
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_symlink returns request resource on success.
 * @throws EioException
 *
 */
function eio_symlink(string $path, string $new_path, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_symlink($path, $new_path, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_sync_file_range permits fine control when synchronizing the open file referred to by the file
 * descriptor fd with disk.
 *
 * @param mixed $fd File descriptor
 * @param int $offset The starting byte of the file range to be synchronized
 * @param int $nbytes Specifies the length of the range to be synchronized, in bytes. If
 * nbytes is zero, then all bytes from offset through
 * to the end of file are synchronized.
 * @param int $flags A bit-mask. Can include any of the following values:
 * EIO_SYNC_FILE_RANGE_WAIT_BEFORE,
 * EIO_SYNC_FILE_RANGE_WRITE,
 * EIO_SYNC_FILE_RANGE_WAIT_AFTER. These flags have
 * the same meaning as their SYNC_FILE_RANGE_*
 * counterparts(see SYNC_FILE_RANGE(2) man page).
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_sync_file_range returns request resource on success.
 * @throws EioException
 *
 */
function eio_sync_file_range($fd, int $offset, int $nbytes, int $flags, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_sync_file_range($fd, $offset, $nbytes, $flags, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param int $pri
 * @param callable $callback
 * @param mixed $data
 * @return resource eio_sync returns request resource on success.
 * @throws EioException
 *
 */
function eio_sync(int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_sync($pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param mixed $fd File descriptor
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_syncfs returns request resource on success.
 * @throws EioException
 *
 */
function eio_syncfs($fd, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_syncfs($fd, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_truncate causes the regular file named by path to be truncated to
 * a size of precisely length bytes
 *
 * @param string $path File path
 * @param int $offset Offset from beginning of the file.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_busy returns request resource on success.
 * @throws EioException
 *
 */
function eio_truncate(string $path, int $offset = 0, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_truncate($path, $offset, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_unlink deletes  a  name from the file system.
 *
 * @param string $path Path to file
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_unlink returns request resource on success.
 * @throws EioException
 *
 */
function eio_unlink(string $path, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_unlink($path, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param string $path Path to the file.
 * @param float $atime Access time
 * @param float $mtime Modification time
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_utime returns request resource on success.
 * @throws EioException
 *
 */
function eio_utime(string $path, float $atime, float $mtime, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_utime($path, $atime, $mtime, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}


/**
 * eio_write writes up to length
 * bytes from str at offset
 * offset from the beginning of the file.
 *
 * @param mixed $fd Stream, Socket resource, or numeric file descriptor, e.g. returned by eio_open
 * @param string $str Source string
 * @param int $length Maximum number of bytes to write.
 * @param int $offset Offset from the beginning of file.
 * @param int $pri The request priority: EIO_PRI_DEFAULT, EIO_PRI_MIN, EIO_PRI_MAX, or NULL.
 * If NULL passed, pri internally is set to
 * EIO_PRI_DEFAULT.
 * @param callable $callback
 * callback function is called when the request is done.
 * It should match the following prototype:
 *
 *
 * data
 * is custom data passed to the request.
 *
 *
 * result
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 *
 * req
 * is optional request resource which can be used with functions like eio_get_last_error
 *
 *
 *
 * is custom data passed to the request.
 *
 * request-specific result value; basically, the value returned by corresponding
 * system call.
 *
 * is optional request resource which can be used with functions like eio_get_last_error
 * @param mixed $data is custom data passed to the request.
 * @return resource eio_write returns request resource on success.
 * @throws EioException
 *
 */
function eio_write($fd, string $str, int $length = 0, int $offset = 0, int $pri = EIO_PRI_DEFAULT, callable $callback = null, $data = null)
{
    error_clear_last();
    $result = \eio_write($fd, $str, $length, $offset, $pri, $callback, $data);
    if ($result === false) {
        throw EioException::createFromPhpError();
    }
    return $result;
}

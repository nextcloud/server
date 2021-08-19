<?php

namespace Safe;

use Safe\Exceptions\PosixException;

/**
 * posix_access checks the user's permission of a file.
 *
 * @param string $file The name of the file to be tested.
 * @param int $mode A mask consisting of one or more of POSIX_F_OK,
 * POSIX_R_OK, POSIX_W_OK and
 * POSIX_X_OK.
 *
 * POSIX_R_OK, POSIX_W_OK and
 * POSIX_X_OK request checking whether the file
 * exists and has read, write and execute permissions, respectively.
 * POSIX_F_OK just requests checking for the
 * existence of the file.
 * @throws PosixException
 *
 */
function posix_access(string $file, int $mode = POSIX_F_OK): void
{
    error_clear_last();
    $result = \posix_access($file, $mode);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
}


/**
 * Gets information about a group provided its name.
 *
 * @param string $name The name of the group
 * @return array Returns an array on success.
 * The array elements returned are:
 *
 * The group information array
 *
 *
 *
 * Element
 * Description
 *
 *
 *
 *
 * name
 *
 * The name element contains the name of the group. This is
 * a short, usually less than 16 character "handle" of the
 * group, not the real, full name.  This should be the same as
 * the name parameter used when
 * calling the function, and hence redundant.
 *
 *
 *
 * passwd
 *
 * The passwd element contains the group's password in an
 * encrypted format. Often, for example on a system employing
 * "shadow" passwords, an asterisk is returned instead.
 *
 *
 *
 * gid
 *
 * Group ID of the group in numeric form.
 *
 *
 *
 * members
 *
 * This consists of an array of
 * string's for all the members in the group.
 *
 *
 *
 *
 *
 * @throws PosixException
 *
 */
function posix_getgrnam(string $name): array
{
    error_clear_last();
    $result = \posix_getgrnam($name);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
    return $result;
}


/**
 * Returns the process group identifier of the process
 * pid.
 *
 * @param int $pid The process id.
 * @return int Returns the identifier, as an integer.
 * @throws PosixException
 *
 */
function posix_getpgid(int $pid): int
{
    error_clear_last();
    $result = \posix_getpgid($pid);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
    return $result;
}


/**
 * Calculates the group access list for the user specified in name.
 *
 * @param string $name The user to calculate the list for.
 * @param int $base_group_id Typically the group number from the password file.
 * @throws PosixException
 *
 */
function posix_initgroups(string $name, int $base_group_id): void
{
    error_clear_last();
    $result = \posix_initgroups($name, $base_group_id);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
}


/**
 * Send the signal sig to the process with
 * the process identifier pid.
 *
 * @param int $pid The process identifier.
 * @param int $sig One of the PCNTL signals constants.
 * @throws PosixException
 *
 */
function posix_kill(int $pid, int $sig): void
{
    error_clear_last();
    $result = \posix_kill($pid, $sig);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
}


/**
 * posix_mkfifo creates a special
 * FIFO file which exists in the file system and acts as
 * a bidirectional communication endpoint for processes.
 *
 * @param string $pathname Path to the FIFO file.
 * @param int $mode The second parameter mode has to be given in
 * octal notation (e.g. 0644). The permission of the newly created
 * FIFO also depends on the setting of the current
 * umask. The permissions of the created file are
 * (mode &amp; ~umask).
 * @throws PosixException
 *
 */
function posix_mkfifo(string $pathname, int $mode): void
{
    error_clear_last();
    $result = \posix_mkfifo($pathname, $mode);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
}


/**
 * Creates a special or ordinary file.
 *
 * @param string $pathname The file to create
 * @param int $mode This parameter is constructed by a bitwise OR between file type (one of
 * the following constants: POSIX_S_IFREG,
 * POSIX_S_IFCHR, POSIX_S_IFBLK,
 * POSIX_S_IFIFO or
 * POSIX_S_IFSOCK) and permissions.
 * @param int $major The major device kernel identifier (required to pass when using
 * S_IFCHR or S_IFBLK).
 * @param int $minor The minor device kernel identifier.
 * @throws PosixException
 *
 */
function posix_mknod(string $pathname, int $mode, int $major = 0, int $minor = 0): void
{
    error_clear_last();
    $result = \posix_mknod($pathname, $mode, $major, $minor);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
}


/**
 * Set the effective group ID of the current process. This is a
 * privileged function and needs appropriate privileges (usually
 * root) on the system to be able to perform this function.
 *
 * @param int $gid The group id.
 * @throws PosixException
 *
 */
function posix_setegid(int $gid): void
{
    error_clear_last();
    $result = \posix_setegid($gid);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
}


/**
 * Set the effective user ID of the current process. This is a privileged
 * function and needs appropriate privileges (usually root) on
 * the system to be able to perform this function.
 *
 * @param int $uid The user id.
 * @throws PosixException
 *
 */
function posix_seteuid(int $uid): void
{
    error_clear_last();
    $result = \posix_seteuid($uid);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
}


/**
 * Set the real group ID of the current process. This is a
 * privileged function and needs appropriate privileges (usually
 * root) on the system to be able to perform this function. The
 * appropriate order of function calls is
 * posix_setgid first,
 * posix_setuid last.
 *
 * @param int $gid The group id.
 * @throws PosixException
 *
 */
function posix_setgid(int $gid): void
{
    error_clear_last();
    $result = \posix_setgid($gid);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
}


/**
 * Let the process pid join the process group
 * pgid.
 *
 * @param int $pid The process id.
 * @param int $pgid The process group id.
 * @throws PosixException
 *
 */
function posix_setpgid(int $pid, int $pgid): void
{
    error_clear_last();
    $result = \posix_setpgid($pid, $pgid);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
}


/**
 * posix_setrlimit sets the soft and hard limits for a
 * given system resource.
 *
 *
 * Each resource has an associated soft and hard limit.  The soft
 * limit is the value that the kernel enforces for the corresponding
 * resource.  The hard limit acts as a ceiling for the soft limit.
 * An unprivileged process may only set its soft limit to a value
 * from 0 to the hard limit, and irreversibly lower its hard limit.
 *
 * @param int $resource The
 * resource limit constant
 * corresponding to the limit that is being set.
 * @param int $softlimit The soft limit, in whatever unit the resource limit requires, or
 * POSIX_RLIMIT_INFINITY.
 * @param int $hardlimit The hard limit, in whatever unit the resource limit requires, or
 * POSIX_RLIMIT_INFINITY.
 * @throws PosixException
 *
 */
function posix_setrlimit(int $resource, int $softlimit, int $hardlimit): void
{
    error_clear_last();
    $result = \posix_setrlimit($resource, $softlimit, $hardlimit);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
}


/**
 * Set the real user ID of the current process. This is a privileged
 * function that needs appropriate privileges (usually root) on
 * the system to be able to perform this function.
 *
 * @param int $uid The user id.
 * @throws PosixException
 *
 */
function posix_setuid(int $uid): void
{
    error_clear_last();
    $result = \posix_setuid($uid);
    if ($result === false) {
        throw PosixException::createFromPhpError();
    }
}

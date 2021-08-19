<?php

namespace Safe;

use Safe\Exceptions\ExecException;

/**
 * proc_get_status fetches data about a
 * process opened using proc_open.
 *
 * @param resource $process The proc_open resource that will
 * be evaluated.
 * @return array An array of collected information on success. The returned array contains the following elements:
 *
 *
 *
 *
 * elementtypedescription
 *
 *
 *
 * command
 * string
 *
 * The command string that was passed to proc_open.
 *
 *
 *
 * pid
 * int
 * process id
 *
 *
 * running
 * bool
 *
 * TRUE if the process is still running, FALSE if it has
 * terminated.
 *
 *
 *
 * signaled
 * bool
 *
 * TRUE if the child process has been terminated by
 * an uncaught signal. Always set to FALSE on Windows.
 *
 *
 *
 * stopped
 * bool
 *
 * TRUE if the child process has been stopped by a
 * signal. Always set to FALSE on Windows.
 *
 *
 *
 * exitcode
 * int
 *
 * The exit code returned by the process (which is only
 * meaningful if running is FALSE).
 * Only first call of this function return real value, next calls return
 * -1.
 *
 *
 *
 * termsig
 * int
 *
 * The number of the signal that caused the child process to terminate
 * its execution (only meaningful if signaled is TRUE).
 *
 *
 *
 * stopsig
 * int
 *
 * The number of the signal that caused the child process to stop its
 * execution (only meaningful if stopped is TRUE).
 *
 *
 *
 *
 *
 * @throws ExecException
 *
 */
function proc_get_status($process): array
{
    error_clear_last();
    $result = \proc_get_status($process);
    if ($result === false) {
        throw ExecException::createFromPhpError();
    }
    return $result;
}


/**
 * proc_nice changes the priority of the current
 * process by the amount specified in increment. A
 * positive increment will lower the priority of the
 * current process, whereas a negative increment
 * will raise the priority.
 *
 * proc_nice is not related to
 * proc_open and its associated functions in any way.
 *
 * @param int $increment The new priority value, the value of this may differ on platforms.
 *
 * On Unix, a low value, such as -20 means high priority
 * wheras a positive value have a lower priority.
 *
 * For Windows the increment parameter have the
 * following meanings:
 * @throws ExecException
 *
 */
function proc_nice(int $increment): void
{
    error_clear_last();
    $result = \proc_nice($increment);
    if ($result === false) {
        throw ExecException::createFromPhpError();
    }
}


/**
 * system is just like the C version of the
 * function in that it executes the given
 * command and outputs the result.
 *
 * The system call also tries to automatically
 * flush the web server's output buffer after each line of output if
 * PHP is running as a server module.
 *
 * If you need to execute a command and have all the data from the
 * command passed directly back without any interference, use the
 * passthru function.
 *
 * @param string $command The command that will be executed.
 * @param int $return_var If the return_var argument is present, then the
 * return status of the executed command will be written to this
 * variable.
 * @return string Returns the last line of the command output on success.
 * @throws ExecException
 *
 */
function system(string $command, int &$return_var = null): string
{
    error_clear_last();
    $result = \system($command, $return_var);
    if ($result === false) {
        throw ExecException::createFromPhpError();
    }
    return $result;
}

<?php

namespace Safe;

use Safe\Exceptions\PcntlException;

/**
 * Executes the program with the given arguments.
 *
 * @param string $path path must be the path to a binary executable or a
 * script with a valid path pointing to an executable in the shebang (
 * #!/usr/local/bin/perl for example) as the first line.  See your system's
 * man execve(2) page for additional information.
 * @param array $args args is an array of argument strings passed to the
 * program.
 * @param array $envs envs is an array of strings which are passed as
 * environment to the program.  The array is in the format of name =&gt; value,
 * the key being the name of the environmental variable and the value being
 * the value of that variable.
 * @throws PcntlException
 *
 */
function pcntl_exec(string $path, array $args = null, array $envs = null): void
{
    error_clear_last();
    if ($envs !== null) {
        $result = \pcntl_exec($path, $args, $envs);
    } elseif ($args !== null) {
        $result = \pcntl_exec($path, $args);
    } else {
        $result = \pcntl_exec($path);
    }
    if ($result === false) {
        throw PcntlException::createFromPhpError();
    }
}


/**
 * pcntl_getpriority gets the priority of
 * pid. Because priority levels can differ between
 * system types and kernel versions, please see your system's getpriority(2)
 * man page for specific details.
 *
 * @param int $pid If not specified, the pid of the current process is used.
 * @param int $process_identifier One of PRIO_PGRP, PRIO_USER
 * or PRIO_PROCESS.
 * @return int pcntl_getpriority returns the priority of the process.  A lower numerical value causes more favorable
 * scheduling.
 * @throws PcntlException
 *
 */
function pcntl_getpriority(int $pid = null, int $process_identifier = PRIO_PROCESS): int
{
    error_clear_last();
    if ($process_identifier !== PRIO_PROCESS) {
        $result = \pcntl_getpriority($pid, $process_identifier);
    } elseif ($pid !== null) {
        $result = \pcntl_getpriority($pid);
    } else {
        $result = \pcntl_getpriority();
    }
    if ($result === false) {
        throw PcntlException::createFromPhpError();
    }
    return $result;
}


/**
 * pcntl_setpriority sets the priority of
 * pid.
 *
 * @param int $priority priority is generally a value in the range
 * -20 to 20. The default priority
 * is 0 while a lower numerical value causes more
 * favorable scheduling.  Because priority levels can differ between
 * system types and kernel versions, please see your system's setpriority(2)
 * man page for specific details.
 * @param int $pid If not specified, the pid of the current process is used.
 * @param int $process_identifier One of PRIO_PGRP, PRIO_USER
 * or PRIO_PROCESS.
 * @throws PcntlException
 *
 */
function pcntl_setpriority(int $priority, int $pid = null, int $process_identifier = PRIO_PROCESS): void
{
    error_clear_last();
    if ($process_identifier !== PRIO_PROCESS) {
        $result = \pcntl_setpriority($priority, $pid, $process_identifier);
    } elseif ($pid !== null) {
        $result = \pcntl_setpriority($priority, $pid);
    } else {
        $result = \pcntl_setpriority($priority);
    }
    if ($result === false) {
        throw PcntlException::createFromPhpError();
    }
}


/**
 * The pcntl_signal_dispatch function calls the signal
 * handlers installed by pcntl_signal for each pending
 * signal.
 *
 * @throws PcntlException
 *
 */
function pcntl_signal_dispatch(): void
{
    error_clear_last();
    $result = \pcntl_signal_dispatch();
    if ($result === false) {
        throw PcntlException::createFromPhpError();
    }
}


/**
 * The pcntl_sigprocmask function adds, removes or sets blocked
 * signals, depending on the how parameter.
 *
 * @param int $how Sets the behavior of pcntl_sigprocmask. Possible
 * values:
 *
 * SIG_BLOCK: Add the signals to the
 * currently blocked signals.
 * SIG_UNBLOCK: Remove the signals from the
 * currently blocked signals.
 * SIG_SETMASK: Replace the currently
 * blocked signals by the given list of signals.
 *
 * @param array $set List of signals.
 * @param array|null $oldset The oldset parameter is set to an array
 * containing the list of the previously blocked signals.
 * @throws PcntlException
 *
 */
function pcntl_sigprocmask(int $how, array $set, ?array &$oldset = null): void
{
    error_clear_last();
    $result = \pcntl_sigprocmask($how, $set, $oldset);
    if ($result === false) {
        throw PcntlException::createFromPhpError();
    }
}


/**
 *
 *
 * @param int $errno
 * @return string Returns error description on success.
 * @throws PcntlException
 *
 */
function pcntl_strerror(int $errno): string
{
    error_clear_last();
    $result = \pcntl_strerror($errno);
    if ($result === false) {
        throw PcntlException::createFromPhpError();
    }
    return $result;
}

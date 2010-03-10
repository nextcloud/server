<?php
/**
 * $Header: /repository/pear/Log/Log/syslog.php,v 1.22 2004/01/19 08:02:40 jon Exp $
 * $Horde: horde/lib/Log/syslog.php,v 1.6 2000/06/28 21:36:13 jon Exp $
 *
 * @version $Revision: 1.22 $
 * @package Log
 */

/**
 * The Log_syslog class is a concrete implementation of the Log::
 * abstract class which sends messages to syslog on UNIX-like machines
 * (PHP emulates this with the Event Log on Windows machines).
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @since   Horde 1.3
 * @since   Log 1.0
 * @package Log
 *
 * @example syslog.php      Using the syslog handler.
 */
class Log_syslog extends Log
{
    /**
    * Integer holding the log facility to use. 
    * @var string
    * @access private
    */
    var $_name = LOG_SYSLOG;

    /**
     * Constructs a new syslog object.
     *
     * @param string $name     The syslog facility.
     * @param string $ident    The identity string.
     * @param array  $conf     The configuration array.
     * @param int    $level    Log messages up to and including this level.
     * @access public
     */
    function Log_syslog($name, $ident = '', $conf = array(),
                        $level = PEAR_LOG_DEBUG)
    {
        /* Ensure we have a valid integer value for $name. */
        if (empty($name) || !is_int($name)) {
            $name = LOG_SYSLOG;
        }

        $this->_id = md5(microtime());
        $this->_name = $name;
        $this->_ident = $ident;
        $this->_mask = Log::UPTO($level);
    }

    /**
     * Opens a connection to the system logger, if it has not already
     * been opened.  This is implicitly called by log(), if necessary.
     * @access public
     */
    function open()
    {
        if (!$this->_opened) {
            openlog($this->_ident, LOG_PID, $this->_name);
            $this->_opened = true;
        }

        return $this->_opened;
    }

    /**
     * Closes the connection to the system logger, if it is open.
     * @access public
     */
    function close()
    {
        if ($this->_opened) {
            closelog();
            $this->_opened = false;
        }

        return ($this->_opened === false);
    }

    /**
     * Sends $message to the currently open syslog connection.  Calls
     * open() if necessary. Also passes the message along to any Log_observer
     * instances that are observing this Log.
     *
     * @param mixed $message String or object containing the message to log.
     * @param int $priority (optional) The priority of the message.  Valid
     *                  values are: PEAR_LOG_EMERG, PEAR_LOG_ALERT,
     *                  PEAR_LOG_CRIT, PEAR_LOG_ERR, PEAR_LOG_WARNING,
     *                  PEAR_LOG_NOTICE, PEAR_LOG_INFO, and PEAR_LOG_DEBUG.
     * @return boolean  True on success or false on failure.
     * @access public
     */
    function log($message, $priority = null)
    {
        /* If a priority hasn't been specified, use the default value. */
        if ($priority === null) {
            $priority = $this->_priority;
        }

        /* Abort early if the priority is above the maximum logging level. */
        if (!$this->_isMasked($priority)) {
            return false;
        }

        /* If the connection isn't open and can't be opened, return failure. */
        if (!$this->_opened && !$this->open()) {
            return false;
        }

        /* Extract the string representation of the message. */
        $message = $this->_extractMessage($message);

        if (!syslog($this->_toSyslog($priority), $message)) {
            return false;
        }

        $this->_announce(array('priority' => $priority, 'message' => $message));

        return true;
    }

    /**
     * Converts a PEAR_LOG_* constant into a syslog LOG_* constant.
     *
     * This function exists because, under Windows, not all of the LOG_*
     * constants have unique values.  Instead, the PEAR_LOG_* were introduced
     * for global use, with the conversion to the LOG_* constants kept local to
     * to the syslog driver.
     *
     * @param int $priority     PEAR_LOG_* value to convert to LOG_* value.
     *
     * @return  The LOG_* representation of $priority.
     *
     * @access private
     */
    function _toSyslog($priority)
    {
        static $priorities = array(
            PEAR_LOG_EMERG   => LOG_EMERG,
            PEAR_LOG_ALERT   => LOG_ALERT,
            PEAR_LOG_CRIT    => LOG_CRIT,
            PEAR_LOG_ERR     => LOG_ERR,
            PEAR_LOG_WARNING => LOG_WARNING,
            PEAR_LOG_NOTICE  => LOG_NOTICE,
            PEAR_LOG_INFO    => LOG_INFO,
            PEAR_LOG_DEBUG   => LOG_DEBUG
        );

        /* If we're passed an unknown priority, default to LOG_INFO. */
        if (!is_int($priority) || !in_array($priority, $priorities)) {
            return LOG_INFO;
        }

        return $priorities[$priority];
    }
}
?>

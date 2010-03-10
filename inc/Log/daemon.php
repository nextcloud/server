<?php
// $Id: daemon.php,v 1.1 2004/12/21 06:55:38 jon Exp $

/**
 * The Log_daemon class is a concrete implementation of the Log::
 * abstract class which sends messages to syslog daemon on UNIX-like machines.
 * This class uses the syslog protocol: http://www.ietf.org/rfc/rfc3164.txt
 *
 * @author  Bart van der Schans <schans@dds.nl>
 * @version $Revision: 1.1 $
 * @package Log
 */
class Log_daemon extends Log {

    /**
     * Integer holding the log facility to use.
     * @var string
     */
    var $_name = LOG_DAEMON;

    /**
     * Var holding the resource pointer to the socket
     * @var resource
     */
    var $_socket;

    /**
     * The ip address or servername
     * @see http://www.php.net/manual/en/transports.php
     * @var string
     */
    var $_ip = '127.0.0.1';

    /**
     * Protocol to use (tcp, udp, etc.)
     * @see http://www.php.net/manual/en/transports.php
     * @var string
     */
    var $_proto = 'udp';

    /**
     * Port to connect to
     * @var int
     */
    var $_port = 514;

    /**
     * Maximum message length in bytes
     * @var int
     */
    var $_maxsize = 4096;

    /**
     * Socket timeout in seconds
     * @var int
     */
    var $_timeout = 1;


    /**
     * Constructs a new syslog object.
     *
     * @param string $name     The syslog facility.
     * @param string $ident    The identity string.
     * @param array  $conf     The configuration array.
     * @param int    $maxLevel Maximum level at which to log.
     * @access public
     */
    function Log_daemon($name, $ident = '', $conf = array(),
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

        if (isset($conf['ip'])) {
            $this->_ip = $conf['ip'];
        }
        if (isset($conf['proto'])) {
            $this->_proto = $conf['proto'];
        }
        if (isset($conf['port'])) {
            $this->_port = $conf['port'];
        }
        if (isset($conf['maxsize'])) {
            $this->_maxsize = $conf['maxsize'];
        }
        if (isset($conf['timeout'])) {
            $this->_timeout = $conf['timeout'];
        }
        $this->_proto = $this->_proto . '://';

        register_shutdown_function(array(&$this, '_Log_daemon'));
    }

    /**
     * Destructor.
     *
     * @access private
     */
    function _Log_daemon()
    {
        $this->close();
    }

    /**
     * Opens a connection to the system logger, if it has not already
     * been opened.  This is implicitly called by log(), if necessary.
     * @access public
     */
    function open()
    {
        if (!$this->_opened) {
            $this->_opened = (bool)($this->_socket = @fsockopen(
                                                $this->_proto . $this->_ip,
                                                $this->_port,
                                                $errno,
                                                $errstr,
                                                $this->_timeout));
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
            $this->_opened = false;
            return fclose($this->_socket);
        }
        return true;
    }

    /**
     * Sends $message to the currently open syslog connection.  Calls
     * open() if necessary. Also passes the message along to any Log_observer
     * instances that are observing this Log.
     *
     * @param string $message  The textual message to be logged.
     * @param int $priority (optional) The priority of the message.  Valid
     *                  values are: LOG_EMERG, LOG_ALERT, LOG_CRIT,
     *                  LOG_ERR, LOG_WARNING, LOG_NOTICE, LOG_INFO,
     *                  and LOG_DEBUG.  The default is LOG_INFO.
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

        /* Set the facility level. */
        $facility_level = intval($this->_name) +
                          intval($this->_toSyslog($priority));

        /* Prepend ident info. */
        if (!empty($this->_ident)) {
            $message = $this->_ident . ' ' . $message;
        }

        /* Check for message length. */
        if (strlen($message) > $this->_maxsize) {
            $message = substr($message, 0, ($this->_maxsize) - 10) . ' [...]';
        }

        /* Write to socket. */
        fwrite($this->_socket, '<' . $facility_level . '>' . $message . "\n");

        $this->_announce(array('priority' => $priority, 'message' => $message));
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

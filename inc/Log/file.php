<?php
/**
 * $Header: /repository/pear/Log/Log/file.php,v 1.37 2004/01/19 08:02:40 jon Exp $
 *
 * @version $Revision: 1.37 $
 * @package Log
 */

/**
 * The Log_file class is a concrete implementation of the Log abstract
 * class that logs messages to a text file.
 * 
 * @author  Jon Parise <jon@php.net>
 * @author  Roman Neuhauser <neuhauser@bellavista.cz>
 * @since   Log 1.0
 * @package Log
 *
 * @example file.php    Using the file handler.
 */
class Log_file extends Log
{
    /**
     * String containing the name of the log file.
     * @var string
     * @access private
     */
    var $_filename = 'php.log';

    /**
     * Handle to the log file.
     * @var resource
     * @access private
     */
    var $_fp = false;

    /**
     * Should new log entries be append to an existing log file, or should the
     * a new log file overwrite an existing one?
     * @var boolean
     * @access private
     */
    var $_append = true;

    /**
     * Integer (in octal) containing the log file's permissions mode.
     * @var integer
     * @access private
     */
    var $_mode = 0644;

    /**
     * String containing the format of a log line.
     * @var string
     * @access private
     */
    var $_lineFormat = '%1$s %2$s [%3$s] %4$s';

    /**
     * String containing the timestamp format.  It will be passed directly to
     * strftime().  Note that the timestamp string will generated using the
     * current locale.
     * @var string
     * @access private
     */
    var $_timeFormat = '%b %d %H:%M:%S';

    /**
     * Hash that maps canonical format keys to position arguments for the
     * "line format" string.
     * @var array
     * @access private
     */
    var $_formatMap = array('%{timestamp}'  => '%1$s',
                            '%{ident}'      => '%2$s',
                            '%{priority}'   => '%3$s',
                            '%{message}'    => '%4$s',
                            '%\{'           => '%%{');

    /**
     * String containing the end-on-line character sequence.
     * @var string
     * @access private
     */
    var $_eol = "\n";

    /**
     * Constructs a new Log_file object.
     *
     * @param string $name     Ignored.
     * @param string $ident    The identity string.
     * @param array  $conf     The configuration array.
     * @param int    $level    Log messages up to and including this level.
     * @access public
     */
    function Log_file($name, $ident = '', $conf = array(),
                      $level = PEAR_LOG_DEBUG)
    {
        $this->_id = md5(microtime());
        $this->_filename = $name;
        $this->_ident = $ident;
        $this->_mask = Log::UPTO($level);

        if (isset($conf['append'])) {
            $this->_append = $conf['append'];
        }

        if (!empty($conf['mode'])) {
            $this->_mode = $conf['mode'];
        }

        if (!empty($conf['lineFormat'])) {
            $this->_lineFormat = str_replace(array_keys($this->_formatMap),
                                             array_values($this->_formatMap),
                                             $conf['lineFormat']);
        }

        if (!empty($conf['timeFormat'])) {
            $this->_timeFormat = $conf['timeFormat'];
        }

        if (!empty($conf['eol'])) {
            $this->_eol = $conf['eol'];
        } else {
            $this->_eol = (strstr(PHP_OS, 'WIN')) ? "\r\n" : "\n";
        }

        register_shutdown_function(array(&$this, '_Log_file'));
    }

    /**
     * Destructor
     */
    function _Log_file()
    {
        if ($this->_opened) {
            $this->close();
        }
    }

    /**
     * Creates the given directory path.  If the parent directories don't
     * already exist, they will be created, too.
     *
     * @param   string  $path       The full directory path to create.
     * @param   integer $mode       The permissions mode with which the
     *                              directories will be created.
     *
     * @return  True if the full path is successfully created or already
     *          exists.
     *
     * @access  private
     */
    function _mkpath($path, $mode = 0700)
    {
        static $depth = 0;

        /* Guard against potentially infinite recursion. */
        if ($depth++ > 25) {
            trigger_error("_mkpath(): Maximum recursion depth (25) exceeded",
                          E_USER_WARNING);
            return false;
        }

        /* We're only interested in the directory component of the path. */
        $path = dirname($path);

        /* If the directory already exists, return success immediately. */
        if (is_dir($path)) {
            $depth = 0;
            return true;
        }

        /*
         * In order to understand recursion, you must first understand
         * recursion ...
         */
        if ($this->_mkpath($path, $mode) === false) {
            return false;
        }

        return @mkdir($path, $mode);
    }

    /**
     * Opens the log file for output.  If the specified log file does not
     * already exist, it will be created.  By default, new log entries are
     * appended to the end of the log file.
     *
     * This is implicitly called by log(), if necessary.
     *
     * @access public
     */
    function open()
    {
        if (!$this->_opened) {
            /* If the log file's directory doesn't exist, create it. */
            if (!is_dir(dirname($this->_filename))) {
                $this->_mkpath($this->_filename);
            }

            /* Obtain a handle to the log file. */
            $this->_fp = fopen($this->_filename, ($this->_append) ? 'a' : 'w');

            $this->_opened = ($this->_fp !== false);

            /* Attempt to set the log file's mode. */
            @chmod($this->_filename, $this->_mode);
        }

        return $this->_opened;
    }

    /**
     * Closes the log file if it is open.
     *
     * @access public
     */
    function close()
    {
        /* If the log file is open, close it. */
        if ($this->_opened && fclose($this->_fp)) {
            $this->_opened = false;
        }

        return ($this->_opened === false);
    }

    /**
     * Flushes all pending data to the file handle.
     *
     * @access public
     * @since Log 1.8.2
     */
    function flush()
    {
        return fflush($this->_fp);
    }

    /**
     * Logs $message to the output window.  The message is also passed along
     * to any Log_observer instances that are observing this Log.
     *
     * @param mixed  $message  String or object containing the message to log.
     * @param string $priority The priority of the message.  Valid
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

        /* If the log file isn't already open, open it now. */
        if (!$this->_opened && !$this->open()) {
            return false;
        }

        /* Extract the string representation of the message. */
        $message = $this->_extractMessage($message);

        /* Build the string containing the complete log line. */
        $line = sprintf($this->_lineFormat, strftime($this->_timeFormat),
                $this->_ident, $this->priorityToString($priority),
                $message) . $this->_eol;

        /* Write the log line to the log file. */
        $success = (fwrite($this->_fp, $line) !== false);

        /* Notify observers about this log message. */
        $this->_announce(array('priority' => $priority, 'message' => $message));

        return $success;
    }
}

?>

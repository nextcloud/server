<?php
/**
 * $Header: /repository/pear/Log/Log/console.php,v 1.19 2004/01/19 08:02:40 jon Exp $
 *
 * @version $Revision: 1.19 $
 * @package Log
 */

/**
 * The Log_console class is a concrete implementation of the Log::
 * abstract class which writes message to the text console.
 * 
 * @author  Jon Parise <jon@php.net>
 * @since   Log 1.1
 * @package Log
 *
 * @example console.php     Using the console handler.
 */
class Log_console extends Log
{
    /**
     * Handle to the current output stream.
     * @var resource
     * @access private
     */
    var $_stream = STDOUT;

    /**
     * Should the output be buffered or displayed immediately?
     * @var string
     * @access private
     */
    var $_buffering = false;

    /**
     * String holding the buffered output.
     * @var string
     * @access private
     */
    var $_buffer = '';

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
     * Constructs a new Log_console object.
     * 
     * @param string $name     Ignored.
     * @param string $ident    The identity string.
     * @param array  $conf     The configuration array.
     * @param int    $level    Log messages up to and including this level.
     * @access public
     */
    function Log_console($name, $ident = '', $conf = array(),
                         $level = PEAR_LOG_DEBUG)
    {
        $this->_id = md5(microtime());
        $this->_ident = $ident;
        $this->_mask = Log::UPTO($level);

        if (!empty($conf['stream'])) {
            $this->_stream = $conf['stream'];
        }

        if (isset($conf['buffering'])) {
            $this->_buffering = $conf['buffering'];
        }

        if (!empty($conf['lineFormat'])) {
            $this->_lineFormat = str_replace(array_keys($this->_formatMap),
                                             array_values($this->_formatMap),
                                             $conf['lineFormat']);
        }

        if (!empty($conf['timeFormat'])) {
            $this->_timeFormat = $conf['timeFormat'];
        }

        /*
         * If output buffering has been requested, we need to register a
         * shutdown function that will dump the buffer upon termination.
         */
        if ($this->_buffering) {
            register_shutdown_function(array(&$this, '_Log_console'));
        }
    }

    /**
     * Destructor
     */
    function _Log_console()
    {
        $this->flush();
    }

    /**
     * Flushes all pending ("buffered") data to the output stream.
     *
     * @access public
     * @since Log 1.8.2
     */
    function flush()
    {
        /*
         * If output buffering is enabled, dump the contents of the buffer to
         * the output stream.
         */
        if ($this->_buffering && (strlen($this->_buffer) > 0)) {
            fwrite($this->_stream, $this->_buffer);
            $this->_buffer = '';
        }
 
        return fflush($this->_stream);
    }

    /**
     * Writes $message to the text console. Also, passes the message
     * along to any Log_observer instances that are observing this Log.
     * 
     * @param mixed  $message    String or object containing the message to log.
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

        /* Extract the string representation of the message. */
        $message = $this->_extractMessage($message);

        /* Build the string containing the complete log line. */
        $line = sprintf($this->_lineFormat, strftime($this->_timeFormat),
                $this->_ident, $this->priorityToString($priority),
                $message) . "\n";

        /*
         * If buffering is enabled, append this line to the output buffer.
         * Otherwise, print the line to the output stream immediately.
         */
        if ($this->_buffering) {
            $this->_buffer .= $line;
        } else {
            fwrite($this->_stream, $line);
        }

        /* Notify observers about this log message. */
        $this->_announce(array('priority' => $priority, 'message' => $message));

        return true;
    }
}

?>

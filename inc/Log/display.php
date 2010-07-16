<?php
/**
 * $Header: /repository/pear/Log/Log/display.php,v 1.6 2004/11/27 21:46:50 jon Exp $
 *
 * @version $Revision: 1.6 $
 * @package Log
 */

/**
 * The Log_display class is a concrete implementation of the Log::
 * abstract class which writes message into browser in usual PHP maner.
 * This may be useful because when you use PEAR::setErrorHandling in
 * PEAR_ERROR_CALLBACK mode error messages are not displayed by
 * PHP error handler.
 *
 * @author  Paul Yanchenko <pusher@inaco.ru>
 * @since   Log 1.8.0
 * @package Log
 *
 * @example display.php     Using the display handler.
 */
class Log_display extends Log
{
    /**
     * String to output before an error message
     * @var string
     * @access private
     */
    var $_error_prepend = '';

    /**
     * String to output after an error message
     * @var string
     * @access private
     */
    var $_error_append = '';


    /**
     * Constructs a new Log_display object.
     *
     * @param string $name     Ignored.
     * @param string $ident    The identity string.
     * @param array  $conf     The configuration array.
     * @param int    $level    Log messages up to and including this level.
     * @access public
     */
    function Log_display($name = '', $ident = '', $conf = array(),
                         $level = PEAR_LOG_DEBUG)
    {
        $this->_id = md5(microtime());
        $this->_ident = $ident;
        $this->_mask = Log::UPTO($level);

        if (!empty($conf['error_prepend'])) {
            $this->_error_prepend = $conf['error_prepend'];
        } else {
            $this->_error_prepend = ini_get('error_prepend_string');
        }

        if (!empty($conf['error_append'])) {
            $this->_error_append = $conf['error_append'];
        } else {
            $this->_error_append = ini_get('error_append_string');
        }
    }

    /**
     * Writes $message to the text browser. Also, passes the message
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

        /* Build and output the complete log line. */
        echo $this->_error_prepend .
             '<b>' . ucfirst($this->priorityToString($priority)) . '</b>: '.
             nl2br(htmlspecialchars($message)) .
             $this->_error_append . "<br />\n";

        /* Notify observers about this log message. */
        $this->_announce(array('priority' => $priority, 'message' => $message));

        return true;
    }
}

?>

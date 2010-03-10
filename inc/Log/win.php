<?php
/**
 * $Header: /repository/pear/Log/Log/win.php,v 1.16 2004/09/08 23:35:53 jon Exp $
 *
 * @version $Revision: 1.16 $
 * @package Log
 */

/**
 * The Log_win class is a concrete implementation of the Log abstract
 * class that logs messages to a separate browser window.
 *
 * The concept for this log handler is based on part by Craig Davis' article
 * entitled "JavaScript Power PHP Debugging:
 *
 *  http://www.zend.com/zend/tut/tutorial-DebugLib.php
 * 
 * @author  Jon Parise <jon@php.net>
 * @since   Log 1.7.0
 * @package Log
 *
 * @example win.php     Using the window handler.
 */
class Log_win extends Log
{
    /**
     * The name of the output window.
     * @var string
     * @access private
     */
    var $_name = 'LogWindow';

    /**
     * The title of the output window.
     * @var string
     * @access private
     */
    var $_title = 'Log Output Window';

    /**
     * Mapping of log priorities to colors.
     * @var array
     * @access private
     */
    var $_colors = array(
                        PEAR_LOG_EMERG   => 'red',
                        PEAR_LOG_ALERT   => 'orange',
                        PEAR_LOG_CRIT    => 'yellow',
                        PEAR_LOG_ERR     => 'green',
                        PEAR_LOG_WARNING => 'blue',
                        PEAR_LOG_NOTICE  => 'indigo',
                        PEAR_LOG_INFO    => 'violet',
                        PEAR_LOG_DEBUG   => 'black'
                    );

    /**
     * String buffer that holds line that are pending output.
     * @var array
     * @access private
     */
    var $_buffer = array();

    /**
     * Constructs a new Log_win object.
     * 
     * @param string $name     Ignored.
     * @param string $ident    The identity string.
     * @param array  $conf     The configuration array.
     * @param int    $level    Log messages up to and including this level.
     * @access public
     */
    function Log_win($name, $ident = '', $conf = array(),
                          $level = PEAR_LOG_DEBUG)
    {
        $this->_id = md5(microtime());
        $this->_name = $name;
        $this->_ident = $ident;
        $this->_mask = Log::UPTO($level);

        if (isset($conf['title'])) {
            $this->_title = $conf['title'];
        }
        if (isset($conf['colors']) && is_array($conf['colors'])) {
            $this->_colors = $conf['colors'];
        }

        register_shutdown_function(array(&$this, '_Log_win'));
    }

    /**
     * Destructor
     */
    function _Log_win()
    {
        if ($this->_opened || (count($this->_buffer) > 0)) {
            $this->close();
        }
    }

    /**
     * The first time open() is called, it will open a new browser window and
     * prepare it for output.
     *
     * This is implicitly called by log(), if necessary.
     *
     * @access public
     */
    function open()
    {
        if (!$this->_opened) {
            $win = $this->_name;

            if (!empty($this->_ident)) {
                $identHeader = "$win.document.writeln('<th>Ident</th>')";
            } else {
                $identHeader = '';
            }

            echo <<< END_OF_SCRIPT
<script language="JavaScript">
$win = window.open('', '{$this->_name}', 'toolbar=no,scrollbars,width=600,height=400');
$win.document.writeln('<html>');
$win.document.writeln('<head>');
$win.document.writeln('<title>{$this->_title}</title>');
$win.document.writeln('<style type="text/css">');
$win.document.writeln('body { font-family: monospace; font-size: 8pt; }');
$win.document.writeln('td,th { font-size: 8pt; }');
$win.document.writeln('td,th { border-bottom: #999999 solid 1px; }');
$win.document.writeln('td,th { border-right: #999999 solid 1px; }');
$win.document.writeln('</style>');
$win.document.writeln('</head>');
$win.document.writeln('<body>');
$win.document.writeln('<table border="0" cellpadding="2" cellspacing="0">');
$win.document.writeln('<tr><th>Time</th>');
$identHeader
$win.document.writeln('<th>Priority</th><th width="100%">Message</th></tr>');
</script>
END_OF_SCRIPT;
            $this->_opened = true;
        }

        return $this->_opened;
    }

    /**
     * Closes the output stream if it is open.  If there are still pending
     * lines in the output buffer, the output window will be opened so that
     * the buffer can be drained.
     *
     * @access public
     */
    function close()
    {
        /*
         * If there are still lines waiting to be written, open the output
         * window so that we can drain the buffer.
         */
        if (!$this->_opened && (count($this->_buffer) > 0)) {
            $this->open();
        }

        if ($this->_opened) {
            $this->_writeln('</table>');
            $this->_writeln('</body></html>');
            $this->_opened = false;
        }

        return ($this->_opened === false);
    }

    /**
     * Writes a single line of text to the output window.
     *
     * @param string    $line   The line of text to write.
     *
     * @access private
     */
    function _writeln($line)
    {
        /* Add this line to our output buffer. */
        $this->_buffer[] = $line;

        /* Buffer the output until this page's headers have been sent. */
        if (!headers_sent()) {
            return;
        }

        /* If we haven't already opened the output window, do so now. */
        if (!$this->_opened && !$this->open()) {
            return false;
        }

        /* Drain the buffer to the output window. */
        $win = $this->_name;
        foreach ($this->_buffer as $line) {
            echo "<script language='JavaScript'>\n";
            echo "$win.document.writeln('" . addslashes($line) . "');\n";
            echo "self.focus();\n";
            echo "</script>\n";
        }

        /* Now that the buffer has been drained, clear it. */
        $this->_buffer = array();
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

        /* Extract the string representation of the message. */
        $message = $this->_extractMessage($message);

        list($usec, $sec) = explode(' ', microtime());

        /* Build the output line that contains the log entry row. */
        $line  = '<tr align="left" valign="top">';
        $line .= sprintf('<td>%s.%s</td>',
                         strftime('%T', $sec), substr($usec, 2, 2));
        if (!empty($this->_ident)) {
            $line .= '<td>' . $this->_ident . '</td>';
        }
        $line .= '<td>' . ucfirst($this->priorityToString($priority)) . '</td>';
        $line .= sprintf('<td style="color: %s">%s</td>',
                         $this->_colors[$priority],
                         preg_replace('/\r\n|\n|\r/', '<br />', $message));
        $line .= '</tr>';

        $this->_writeln($line);

        $this->_announce(array('priority' => $priority, 'message' => $message));

        return true;
    }
}

?>

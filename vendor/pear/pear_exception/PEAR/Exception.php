<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/**
 * PEAR_Exception
 *
 * PHP version 5
 *
 * @category  PEAR
 * @package   PEAR_Exception
 * @author    Tomas V. V. Cox <cox@idecnet.com>
 * @author    Hans Lellelid <hans@velum.net>
 * @author    Bertrand Mansion <bmansion@mamasam.com>
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 1997-2009 The Authors
 * @license   http://opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://pear.php.net/package/PEAR_Exception
 * @since     File available since Release 1.0.0
 */


/**
 * Base PEAR_Exception Class
 *
 * 1) Features:
 *
 * - Nestable exceptions (throw new PEAR_Exception($msg, $prev_exception))
 * - Definable triggers, shot when exceptions occur
 * - Pretty and informative error messages
 * - Added more context info available (like class, method or cause)
 * - cause can be a PEAR_Exception or an array of mixed
 *   PEAR_Exceptions/PEAR_ErrorStack warnings
 * - callbacks for specific exception classes and their children
 *
 * 2) Ideas:
 *
 * - Maybe a way to define a 'template' for the output
 *
 * 3) Inherited properties from PHP Exception Class:
 *
 * protected $message
 * protected $code
 * protected $line
 * protected $file
 * private   $trace
 *
 * 4) Inherited methods from PHP Exception Class:
 *
 * __clone
 * __construct
 * getMessage
 * getCode
 * getFile
 * getLine
 * getTraceSafe
 * getTraceSafeAsString
 * __toString
 *
 * 5) Usage example
 *
 * <code>
 *  require_once 'PEAR/Exception.php';
 *
 *  class Test {
 *     function foo() {
 *         throw new PEAR_Exception('Error Message', ERROR_CODE);
 *     }
 *  }
 *
 *  function myLogger($pear_exception) {
 *     echo $pear_exception->getMessage();
 *  }
 *  // each time a exception is thrown the 'myLogger' will be called
 *  // (its use is completely optional)
 *  PEAR_Exception::addObserver('myLogger');
 *  $test = new Test;
 *  try {
 *     $test->foo();
 *  } catch (PEAR_Exception $e) {
 *     print $e;
 *  }
 * </code>
 *
 * @category  PEAR
 * @package   PEAR_Exception
 * @author    Tomas V.V.Cox <cox@idecnet.com>
 * @author    Hans Lellelid <hans@velum.net>
 * @author    Bertrand Mansion <bmansion@mamasam.com>
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 1997-2009 The Authors
 * @license   http://opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PEAR_Exception
 * @since     Class available since Release 1.0.0
 */
class PEAR_Exception extends Exception
{
    const OBSERVER_PRINT = -2;
    const OBSERVER_TRIGGER = -4;
    const OBSERVER_DIE = -8;
    protected $cause;
    private static $_observers = array();
    private static $_uniqueid = 0;
    private $_trace;

    /**
     * Supported signatures:
     *  - PEAR_Exception(string $message);
     *  - PEAR_Exception(string $message, int $code);
     *  - PEAR_Exception(string $message, Exception $cause);
     *  - PEAR_Exception(string $message, Exception $cause, int $code);
     *  - PEAR_Exception(string $message, PEAR_Error $cause);
     *  - PEAR_Exception(string $message, PEAR_Error $cause, int $code);
     *  - PEAR_Exception(string $message, array $causes);
     *  - PEAR_Exception(string $message, array $causes, int $code);
     *
     * @param string                              $message exception message
     * @param int|Exception|PEAR_Error|array|null $p2      exception cause
     * @param int|null                            $p3      exception code or null
     */
    public function __construct($message, $p2 = null, $p3 = null)
    {
        if (is_int($p2)) {
            $code = $p2;
            $this->cause = null;
        } elseif (is_object($p2) || is_array($p2)) {
            // using is_object allows both Exception and PEAR_Error
            if (is_object($p2) && !($p2 instanceof Exception)) {
                if (!class_exists('PEAR_Error') || !($p2 instanceof PEAR_Error)) {
                    throw new PEAR_Exception(
                        'exception cause must be Exception, ' .
                        'array, or PEAR_Error'
                    );
                }
            }
            $code = $p3;
            if (is_array($p2) && isset($p2['message'])) {
                // fix potential problem of passing in a single warning
                $p2 = array($p2);
            }
            $this->cause = $p2;
        } else {
            $code = null;
            $this->cause = null;
        }
        parent::__construct($message, (int) $code);
        $this->signal();
    }

    /**
     * Add an exception observer
     *
     * @param mixed  $callback - A valid php callback, see php func is_callable()
     *                         - A PEAR_Exception::OBSERVER_* constant
     *                         - An array(const PEAR_Exception::OBSERVER_*,
     *                           mixed $options)
     * @param string $label    The name of the observer. Use this if you want
     *                         to remove it later with removeObserver()
     *
     * @return void
     */
    public static function addObserver($callback, $label = 'default')
    {
        self::$_observers[$label] = $callback;
    }

    /**
     * Remove an exception observer
     *
     * @param string $label Name of the observer
     *
     * @return void
     */
    public static function removeObserver($label = 'default')
    {
        unset(self::$_observers[$label]);
    }

    /**
     * Generate a unique ID for an observer
     *
     * @return int unique identifier for an observer
     */
    public static function getUniqueId()
    {
        return self::$_uniqueid++;
    }

    /**
     * Send a signal to all observers
     *
     * @return void
     */
    protected function signal()
    {
        foreach (self::$_observers as $func) {
            if (is_callable($func)) {
                call_user_func($func, $this);
                continue;
            }
            settype($func, 'array');
            switch ($func[0]) {
            case self::OBSERVER_PRINT :
                $f = (isset($func[1])) ? $func[1] : '%s';
                printf($f, $this->getMessage());
                break;
            case self::OBSERVER_TRIGGER :
                $f = (isset($func[1])) ? $func[1] : E_USER_NOTICE;
                trigger_error($this->getMessage(), $f);
                break;
            case self::OBSERVER_DIE :
                $f = (isset($func[1])) ? $func[1] : '%s';
                die(printf($f, $this->getMessage()));
                break;
            default:
                trigger_error('invalid observer type', E_USER_WARNING);
            }
        }
    }

    /**
     * Return specific error information that can be used for more detailed
     * error messages or translation.
     *
     * This method may be overridden in child exception classes in order
     * to add functionality not present in PEAR_Exception and is a placeholder
     * to define API
     *
     * The returned array must be an associative array of parameter => value like so:
     * <pre>
     * array('name' => $name, 'context' => array(...))
     * </pre>
     *
     * @return array
     */
    public function getErrorData()
    {
        return array();
    }

    /**
     * Returns the exception that caused this exception to be thrown
     *
     * @return Exception|array The context of the exception
     */
    public function getCause()
    {
        return $this->cause;
    }

    /**
     * Function must be public to call on caused exceptions
     *
     * @param array $causes Array that gets filled.
     *
     * @return void
     */
    public function getCauseMessage(&$causes)
    {
        $trace = $this->getTraceSafe();
        $cause = array('class'   => get_class($this),
                       'message' => $this->message,
                       'file' => 'unknown',
                       'line' => 'unknown');
        if (isset($trace[0])) {
            if (isset($trace[0]['file'])) {
                $cause['file'] = $trace[0]['file'];
                $cause['line'] = $trace[0]['line'];
            }
        }
        $causes[] = $cause;
        if ($this->cause instanceof PEAR_Exception) {
            $this->cause->getCauseMessage($causes);
        } elseif ($this->cause instanceof Exception) {
            $causes[] = array('class'   => get_class($this->cause),
                              'message' => $this->cause->getMessage(),
                              'file' => $this->cause->getFile(),
                              'line' => $this->cause->getLine());
        } elseif (class_exists('PEAR_Error') && $this->cause instanceof PEAR_Error) {
            $causes[] = array('class' => get_class($this->cause),
                              'message' => $this->cause->getMessage(),
                              'file' => 'unknown',
                              'line' => 'unknown');
        } elseif (is_array($this->cause)) {
            foreach ($this->cause as $cause) {
                if ($cause instanceof PEAR_Exception) {
                    $cause->getCauseMessage($causes);
                } elseif ($cause instanceof Exception) {
                    $causes[] = array('class'   => get_class($cause),
                                   'message' => $cause->getMessage(),
                                   'file' => $cause->getFile(),
                                   'line' => $cause->getLine());
                } elseif (class_exists('PEAR_Error')
                    && $cause instanceof PEAR_Error
                ) {
                    $causes[] = array('class' => get_class($cause),
                                      'message' => $cause->getMessage(),
                                      'file' => 'unknown',
                                      'line' => 'unknown');
                } elseif (is_array($cause) && isset($cause['message'])) {
                    // PEAR_ErrorStack warning
                    $causes[] = array(
                        'class' => $cause['package'],
                        'message' => $cause['message'],
                        'file' => isset($cause['context']['file']) ?
                                            $cause['context']['file'] :
                                            'unknown',
                        'line' => isset($cause['context']['line']) ?
                                            $cause['context']['line'] :
                                            'unknown',
                    );
                }
            }
        }
    }

    /**
     * Build a backtrace and return it
     *
     * @return array Backtrace
     */
    public function getTraceSafe()
    {
        if (!isset($this->_trace)) {
            $this->_trace = $this->getTrace();
            if (empty($this->_trace)) {
                $backtrace = debug_backtrace();
                $this->_trace = array($backtrace[count($backtrace)-1]);
            }
        }
        return $this->_trace;
    }

    /**
     * Gets the first class of the backtrace
     *
     * @return string Class name
     */
    public function getErrorClass()
    {
        $trace = $this->getTraceSafe();
        return $trace[0]['class'];
    }

    /**
     * Gets the first method of the backtrace
     *
     * @return string Method/function name
     */
    public function getErrorMethod()
    {
        $trace = $this->getTraceSafe();
        return $trace[0]['function'];
    }

    /**
     * Converts the exception to a string (HTML or plain text)
     *
     * @return string String representation
     *
     * @see toHtml()
     * @see toText()
     */
    public function __toString()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return $this->toHtml();
        }
        return $this->toText();
    }

    /**
     * Generates a HTML representation of the exception
     *
     * @return string HTML code
     */
    public function toHtml()
    {
        $trace = $this->getTraceSafe();
        $causes = array();
        $this->getCauseMessage($causes);
        $html =  '<table style="border: 1px" cellspacing="0">' . "\n";
        foreach ($causes as $i => $cause) {
            $html .= '<tr><td colspan="3" style="background: #ff9999">'
               . str_repeat('-', $i) . ' <b>' . $cause['class'] . '</b>: '
               . htmlspecialchars($cause['message'])
                . ' in <b>' . $cause['file'] . '</b> '
               . 'on line <b>' . $cause['line'] . '</b>'
               . "</td></tr>\n";
        }
        $html .= '<tr><td colspan="3" style="background-color: #aaaaaa; text-align: center; font-weight: bold;">Exception trace</td></tr>' . "\n"
               . '<tr><td style="text-align: center; background: #cccccc; width:20px; font-weight: bold;">#</td>'
               . '<td style="text-align: center; background: #cccccc; font-weight: bold;">Function</td>'
               . '<td style="text-align: center; background: #cccccc; font-weight: bold;">Location</td></tr>' . "\n";

        foreach ($trace as $k => $v) {
            $html .= '<tr><td style="text-align: center;">' . $k . '</td>'
                   . '<td>';
            if (!empty($v['class'])) {
                $html .= $v['class'] . $v['type'];
            }
            $html .= $v['function'];
            $args = array();
            if (!empty($v['args'])) {
                foreach ($v['args'] as $arg) {
                    if (is_null($arg)) {
                        $args[] = 'null';
                    } else if (is_array($arg)) {
                        $args[] = 'Array';
                    } else if (is_object($arg)) {
                        $args[] = 'Object('.get_class($arg).')';
                    } else if (is_bool($arg)) {
                        $args[] = $arg ? 'true' : 'false';
                    } else if (is_int($arg) || is_double($arg)) {
                        $args[] = $arg;
                    } else {
                        $arg = (string)$arg;
                        $str = htmlspecialchars(substr($arg, 0, 16));
                        if (strlen($arg) > 16) {
                            $str .= '&hellip;';
                        }
                        $args[] = "'" . $str . "'";
                    }
                }
            }
            $html .= '(' . implode(', ', $args) . ')'
                   . '</td>'
                   . '<td>' . (isset($v['file']) ? $v['file'] : 'unknown')
                   . ':' . (isset($v['line']) ? $v['line'] : 'unknown')
                   . '</td></tr>' . "\n";
        }
        $html .= '<tr><td style="text-align: center;">' . ($k+1) . '</td>'
               . '<td>{main}</td>'
               . '<td>&nbsp;</td></tr>' . "\n"
               . '</table>';
        return $html;
    }

    /**
     * Generates text representation of the exception and stack trace
     *
     * @return string
     */
    public function toText()
    {
        $causes = array();
        $this->getCauseMessage($causes);
        $causeMsg = '';
        foreach ($causes as $i => $cause) {
            $causeMsg .= str_repeat(' ', $i) . $cause['class'] . ': '
                   . $cause['message'] . ' in ' . $cause['file']
                   . ' on line ' . $cause['line'] . "\n";
        }
        return $causeMsg . $this->getTraceAsString();
    }
}
?>

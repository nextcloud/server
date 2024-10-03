<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Logger;

use Hybridauth\Exception\RuntimeException;
use Hybridauth\Exception\InvalidArgumentException;

/**
 * Debugging and Logging utility.
 */
class Logger implements LoggerInterface
{
    const NONE = 'none';  // turn logging off
    const DEBUG = 'debug'; // debug, info and error messages
    const INFO = 'info';  // info and error messages
    const ERROR = 'error'; // only error messages

    /**
     * Debug level.
     *
     * One of Logger::NONE, Logger::DEBUG, Logger::INFO, Logger::ERROR
     *
     * @var string
     */
    protected $level;

    /**
     * Path to file writeable by the web server. Required if $this->level !== Logger::NONE.
     *
     * @var string
     */
    protected $file;

    /**
     * @param bool|string $level One of Logger::NONE, Logger::DEBUG, Logger::INFO, Logger::ERROR
     * @param string $file File where to write messages
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct($level, $file)
    {
        $this->level = self::NONE;

        if ($level && $level !== self::NONE) {
            $this->initialize($file);

            $this->level = $level === true ? Logger::DEBUG : $level;
            $this->file = $file;
        }
    }

    /**
     * @param string $file
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function initialize($file)
    {
        if (!$file) {
            throw new InvalidArgumentException('Log file is not specified.');
        }

        if (!file_exists($file) && !touch($file)) {
            throw new RuntimeException(sprintf('Log file %s can not be created.', $file));
        }

        if (!is_writable($file)) {
            throw new RuntimeException(sprintf('Log file %s is not writeable.', $file));
        }
    }

    /**
     * @inheritdoc
     */
    public function info($message, array $context = [])
    {
        if (!in_array($this->level, [self::DEBUG, self::INFO])) {
            return;
        }

        $this->log(self::INFO, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function debug($message, array $context = [])
    {
        if (!in_array($this->level, [self::DEBUG])) {
            return;
        }

        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function error($message, array $context = [])
    {
        if (!in_array($this->level, [self::DEBUG, self::INFO, self::ERROR])) {
            return;
        }

        $this->log(self::ERROR, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = [])
    {
        $datetime = new \DateTime();
        $datetime = $datetime->format(DATE_ATOM);

        $content = sprintf('%s -- %s -- %s -- %s', $level, $_SERVER['REMOTE_ADDR'], $datetime, $message);
        $content .= ($context ? "\n" . print_r($context, true) : '');
        $content .= "\n";

        file_put_contents($this->file, $content, FILE_APPEND);
    }
}

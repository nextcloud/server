<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @author John Stevenson <john-stevenson@blueyonder.co.uk>
 * @internal
 */
class Status
{
    const ENV_RESTART = 'XDEBUG_HANDLER_RESTART';
    const CHECK = 'Check';
    const ERROR = 'Error';
    const INFO = 'Info';
    const NORESTART = 'NoRestart';
    const RESTART = 'Restart';
    const RESTARTING = 'Restarting';
    const RESTARTED = 'Restarted';

    private $debug;
    private $envAllowXdebug;
    private $loaded;
    private $logger;
    private $time;

    /**
     * Constructor
     *
     * @param string $envAllowXdebug Prefixed _ALLOW_XDEBUG name
     * @param bool $debug Whether debug output is required
     */
    public function __construct($envAllowXdebug, $debug)
    {
        $start = getenv(self::ENV_RESTART);
        Process::setEnv(self::ENV_RESTART);
        $this->time = $start ? round((microtime(true) - $start) * 1000) : 0;

        $this->envAllowXdebug = $envAllowXdebug;
        $this->debug = $debug && defined('STDERR');
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Calls a handler method to report a message
     *
     * @param string $op The handler constant
     * @param null|string $data Data required by the handler
     */
    public function report($op, $data)
    {
        if ($this->logger || $this->debug) {
            call_user_func(array($this, 'report'.$op), $data);
        }
    }

    /**
     * Outputs a status message
     *
     * @param string $text
     * @param string $level
     */
    private function output($text, $level = null)
    {
        if ($this->logger) {
            $this->logger->log($level ?: LogLevel::DEBUG, $text);
        }

        if ($this->debug) {
            fwrite(STDERR, sprintf('xdebug-handler[%d] %s', getmypid(), $text.PHP_EOL));
        }
    }

    private function reportCheck($loaded)
    {
        $this->loaded = $loaded;
        $this->output('Checking '.$this->envAllowXdebug);
    }

    private function reportError($error)
    {
        $this->output(sprintf('No restart (%s)', $error), LogLevel::WARNING);
    }

    private function reportInfo($info)
    {
        $this->output($info);
    }

    private function reportNoRestart()
    {
        $this->output($this->getLoadedMessage());

        if ($this->loaded) {
            $text = sprintf('No restart (%s)', $this->getEnvAllow());
            if (!getenv($this->envAllowXdebug)) {
                $text .= ' Allowed by application';
            }
            $this->output($text);
        }
    }

    private function reportRestart()
    {
        $this->output($this->getLoadedMessage());
        Process::setEnv(self::ENV_RESTART, (string) microtime(true));
    }

    private function reportRestarted()
    {
        $loaded = $this->getLoadedMessage();
        $text = sprintf('Restarted (%d ms). %s', $this->time, $loaded);
        $level = $this->loaded ? LogLevel::WARNING : null;
        $this->output($text, $level);
    }

    private function reportRestarting($command)
    {
        $text = sprintf('Process restarting (%s)', $this->getEnvAllow());
        $this->output($text);
        $text = 'Running '.$command;
        $this->output($text);
    }

    /**
     * Returns the _ALLOW_XDEBUG environment variable as name=value
     *
     * @return string
     */
    private function getEnvAllow()
    {
        return $this->envAllowXdebug.'='.getenv($this->envAllowXdebug);
    }

    /**
     * Returns the Xdebug status and version
     *
     * @return string
     */
    private function getLoadedMessage()
    {
        $loaded = $this->loaded ? sprintf('loaded (%s)', $this->loaded) : 'not loaded';
        return 'The Xdebug extension is '.$loaded;
    }
}

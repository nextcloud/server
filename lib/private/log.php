<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

use \OCP\ILogger;

/**
 * logging utilities
 *
 * This is a stand in, this should be replaced by a Psr\Log\LoggerInterface
 * compatible logger. See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * for the full interface specification.
 *
 * MonoLog is an example implementing this interface.
 */

class Log implements ILogger {

	private $logger;

	/**
	 * @param string $logger The logger that should be used
	 */
	public function __construct($logger=null) {
		// FIXME: Add this for backwards compatibility, should be fixed at some point probably
		if($logger === null) {
			$this->logger = 'OC_Log_'.ucfirst(\OC_Config::getValue('log_type', 'owncloud'));
			call_user_func(array($this->logger, 'init'));
		} else {
			$this->logger = $logger;
		}

	}


	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function emergency($message, array $context = array()) {
		$this->log(\OC_Log::FATAL, $message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function alert($message, array $context = array()) {
		$this->log(\OC_Log::ERROR, $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function critical($message, array $context = array()) {
		$this->log(\OC_Log::ERROR, $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function error($message, array $context = array()) {
		$this->log(\OC_Log::ERROR, $message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function warning($message, array $context = array()) {
		$this->log(\OC_Log::WARN, $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function notice($message, array $context = array()) {
		$this->log(\OC_Log::INFO, $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function info($message, array $context = array()) {
		$this->log(\OC_Log::INFO, $message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function debug($message, array $context = array()) {
		$this->log(\OC_Log::DEBUG, $message, $context);
	}


	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 */
	public function log($level, $message, array $context = array()) {
		if (isset($context['app'])) {
			$app = $context['app'];
		} else {
			$app = 'no app in context';
		}
		// interpolate $message as defined in PSR-3
		$replace = array();
		foreach ($context as $key => $val) {
			$replace['{' . $key . '}'] = $val;
		}

		// interpolate replacement values into the message and return
		$message = strtr($message, $replace);

		$logger = $this->logger;
		call_user_func(array($logger, 'write'), $app, $message, $level);
	}
}

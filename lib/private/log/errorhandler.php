<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Log;

use OC\Log as LoggerInterface;

class ErrorHandler {
	/** @var LoggerInterface */
	private static $logger;

	/**
	 * remove password in URLs
	 * @param string $msg
	 * @return string
	 */
	protected static function removePassword($msg) {
		return preg_replace('/\/\/(.*):(.*)@/', '//xxx:xxx@', $msg);
	}

	public static function register($debug=false) {
		$handler = new ErrorHandler();

		if ($debug) {
			set_error_handler(array($handler, 'onAll'), E_ALL);
		} else {
			set_error_handler(array($handler, 'onError'));
		}
		register_shutdown_function(array($handler, 'onShutdown'));
		set_exception_handler(array($handler, 'onException'));
	}

	public static function setLogger(LoggerInterface $logger) {
		self::$logger = $logger;
	}

	//Fatal errors handler
	public static function onShutdown() {
		$error = error_get_last();
		if($error && self::$logger) {
			//ob_end_clean();
			$msg = $error['message'] . ' at ' . $error['file'] . '#' . $error['line'];
			self::$logger->critical(self::removePassword($msg), array('app' => 'PHP'));
		}
	}

	// Uncaught exception handler
	public static function onException($exception) {
		$msg = $exception->getMessage() . ' at ' . $exception->getFile() . '#' . $exception->getLine();
		self::$logger->critical(self::removePassword($msg), array('app' => 'PHP'));
	}

	//Recoverable errors handler
	public static function onError($number, $message, $file, $line) {
		if (error_reporting() === 0) {
			return;
		}
		$msg = $message . ' at ' . $file . '#' . $line;
		self::$logger->error(self::removePassword($msg), array('app' => 'PHP'));

	}

	//Recoverable handler which catch all errors, warnings and notices
	public static function onAll($number, $message, $file, $line) {
		$msg = $message . ' at ' . $file . '#' . $line;
		self::$logger->debug(self::removePassword($msg), array('app' => 'PHP'));

	}

}

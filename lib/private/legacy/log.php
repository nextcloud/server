<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * logging utilities
 *
 * Log is saved by default at data/owncloud.log using OC_Log_Owncloud.
 * Selecting other backend is done with a config option 'log_type'.
 */

OC_Log::$object = new \OC\Log();
/**
 * @deprecated use \OC::$server->getLogger() to get an \OCP\ILogger instance
 */
class OC_Log {
	public static $object;

	const DEBUG=0;
	const INFO=1;
	const WARN=2;
	const ERROR=3;
	const FATAL=4;

	static private $level_funcs = array(
		self::DEBUG	=> 'debug',
		self::INFO	=> 'info',
		self::WARN	=> 'warning',
		self::ERROR	=> 'error',
		self::FATAL	=> 'emergency',
		);

	static public $enabled = true;
	static protected $class = null;

	/**
	 * write a message in the log
	 * @param string $app
	 * @param string $message
	 * @param int $level
	 */
	public static function write($app, $message, $level) {
		if (self::$enabled) {
			$context = array('app' => $app);
			$func = array(self::$object, self::$level_funcs[$level]);
			call_user_func($func, $message, $context);
		}
	}
}

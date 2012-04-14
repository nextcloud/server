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

class OC_Log {
	const DEBUG=0;
	const INFO=1;
	const WARN=2;
	const ERROR=3;
	const FATAL=4;

	static protected $class = null;

	/**
	 * write a message in the log
	 * @param string $app
	 * @param string $message
	 * @param int level
	 */
	public static function write($app, $message, $level) {
		if (!self::$class) {
			self::$class = 'OC_Log_'.ucfirst(OC_Config::getValue('log_type', 'owncloud'));
			call_user_func(array(self::$class, 'init'));
		}
		$log_class=self::$class;
		$log_class::write($app, $message, $level);
	}
}

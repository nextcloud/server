<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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

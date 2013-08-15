<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Log_Syslog {
	static protected $levels = array(
		OC_Log::DEBUG => LOG_DEBUG,
		OC_Log::INFO => LOG_INFO,
		OC_Log::WARN => LOG_WARNING,
		OC_Log::ERROR => LOG_ERR,
		OC_Log::FATAL => LOG_CRIT,
	);

	/**
	 * Init class data
	 */
	public static function init() {
		openlog('ownCloud', LOG_PID | LOG_CONS, LOG_USER);
		// Close at shutdown
		register_shutdown_function('closelog');
	}

	/**
	 * write a message in the log
	 * @param string $app
	 * @param string $message
	 * @param int $level
	 */
	public static function write($app, $message, $level) {
		$minLevel = min(OC_Config::getValue("loglevel", OC_Log::WARN), OC_Log::ERROR);
		if ($level >= $minLevel) {
			$syslog_level = self::$levels[$level];
			syslog($syslog_level, '{'.$app.'} '.$message);
		}
	}
}

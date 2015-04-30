<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
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
		$syslog_level = self::$levels[$level];
		syslog($syslog_level, '{'.$app.'} '.$message);
	}
}

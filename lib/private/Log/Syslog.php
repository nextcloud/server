<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

namespace OC\Log;

class Syslog {
	static protected $levels = array(
		\OCP\Util::DEBUG => LOG_DEBUG,
		\OCP\Util::INFO => LOG_INFO,
		\OCP\Util::WARN => LOG_WARNING,
		\OCP\Util::ERROR => LOG_ERR,
		\OCP\Util::FATAL => LOG_CRIT,
	);

	/**
	 * Init class data
	 */
	public static function init() {
		openlog(\OC::$server->getSystemConfig()->getValue("syslog_tag", "ownCloud"), LOG_PID | LOG_CONS, LOG_USER);
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

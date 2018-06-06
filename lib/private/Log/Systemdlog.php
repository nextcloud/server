<?php
/**
 * @copyright Copyright (c) 2018, Johannes Ernst
 *
 * @author Johannes Ernst <jernst@indiecomputing.com>
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

use OCP\ILogger;
use OCP\IConfig;
use OCP\Log\IWriter;

// The following fields are understood by systemd/journald, see
// man systemd.journal-fields. All are optional:
// MESSAGE=
//     The human-readable message string for this entry.
// MESSAGE_ID=
//     A 128-bit message identifier ID
// PRIORITY=
//     A priority value between 0 ("emerg") and 7 ("debug")
// CODE_FILE=, CODE_LINE=, CODE_FUNC=
//     The code location generating this message, if known
// ERRNO=
//     The low-level Unix error number causing this entry, if any.
// SYSLOG_FACILITY=, SYSLOG_IDENTIFIER=, SYSLOG_PID=
//     Syslog compatibility fields

class Systemdlog implements IWriter {
	protected $levels = [
		ILogger::DEBUG => 7,
		ILogger::INFO => 6,
		ILogger::WARN => 4,
		ILogger::ERROR => 3,
		ILogger::FATAL => 2,
	];

	public function __construct(IConfig $config) {
	}

	/**
	 * write a message in the log
	 * @param string $app
	 * @param string $message
	 * @param int $level
	 */
	public function write(string $app, $message, int $level) {
		$journal_level = $this->levels[$level];
		sd_journal_send('PRIORITY='.$journal_level,
				'SYSLOG_IDENTIFIER=nextcloud',
				'MESSAGE={'.$app.'} '.$message);
	}
}

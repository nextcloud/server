<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Log;

use OC\SystemConfig;
use OCP\HintException;
use OCP\ILogger;
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

class Systemdlog extends LogDetails implements IWriter {
	protected array $levels = [
		ILogger::DEBUG => 7,
		ILogger::INFO => 6,
		ILogger::WARN => 4,
		ILogger::ERROR => 3,
		ILogger::FATAL => 2,
	];

	protected string $syslogId;

	public function __construct(
		SystemConfig $config,
		?string $tag = null,
	) {
		parent::__construct($config);
		if (!function_exists('sd_journal_send')) {
			throw new HintException(
				'PHP extension php-systemd is not available.',
				'Please install and enable PHP extension systemd if you wish to log to the Systemd journal.');
		}
		if ($tag === null) {
			$tag = $config->getValue('syslog_tag', 'Nextcloud');
		}
		$this->syslogId = $tag;
	}

	/**
	 * Write a message to the log.
	 * @param string|array $message
	 */
	public function write(string $app, $message, int $level): void {
		$journal_level = $this->levels[$level];
		sd_journal_send('PRIORITY=' . $journal_level,
			'SYSLOG_IDENTIFIER=' . $this->syslogId,
			'MESSAGE=' . $this->logDetailsAsJSON($app, $message, $level));
	}
}

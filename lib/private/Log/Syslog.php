<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Log;

use OC\SystemConfig;
use OCP\ILogger;
use OCP\Log\IWriter;

class Syslog extends LogDetails implements IWriter {
	protected array $levels = [
		ILogger::DEBUG => LOG_DEBUG,
		ILogger::INFO => LOG_INFO,
		ILogger::WARN => LOG_WARNING,
		ILogger::ERROR => LOG_ERR,
		ILogger::FATAL => LOG_CRIT,
	];

	public function __construct(
		SystemConfig $config,
		?string $tag = null,
	) {
		parent::__construct($config);
		if ($tag === null) {
			$tag = $config->getValue('syslog_tag', 'Nextcloud');
		}
		openlog($tag, LOG_PID | LOG_CONS, LOG_USER);
	}

	public function __destruct() {
		closelog();
	}

	/**
	 * write a message in the log
	 * @param string|array $message
	 */
	public function write(string $app, $message, int $level): void {
		$syslog_level = $this->levels[$level];
		syslog($syslog_level, $this->logDetailsAsJSON($app, $message, $level));
	}
}

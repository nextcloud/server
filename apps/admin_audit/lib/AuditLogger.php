<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit;

use OCP\IConfig;
use OCP\Log\ILogFactory;
use Psr\Log\LoggerInterface;

/**
 * Logger that logs in the audit log file instead of the normal log file
 */
class AuditLogger implements IAuditLogger {

	private LoggerInterface $parentLogger;

	public function __construct(ILogFactory $logFactory, IConfig $config) {
		$auditType = $config->getSystemValueString('log_type_audit', 'file');
		$defaultTag = $config->getSystemValueString('syslog_tag', 'Nextcloud');
		$auditTag = $config->getSystemValueString('syslog_tag_audit', $defaultTag);
		$logFile = $config->getSystemValueString('logfile_audit', '');

		if ($auditType === 'file' && !$logFile) {
			$default = $config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . '/audit.log';
			// Legacy way was appconfig, now it's paralleled with the normal log config
			$logFile = $config->getAppValue('admin_audit', 'logfile', $default);
		}

		$this->parentLogger = $logFactory->getCustomPsrLogger($logFile, $auditType, $auditTag);
	}

	#[\Override]
	public function emergency($message, array $context = []): void {
		$this->parentLogger->emergency($message, $context);
	}

	#[\Override]
	public function alert($message, array $context = []): void {
		$this->parentLogger->alert($message, $context);
	}

	#[\Override]
	public function critical($message, array $context = []): void {
		$this->parentLogger->critical($message, $context);
	}

	#[\Override]
	public function error($message, array $context = []): void {
		$this->parentLogger->error($message, $context);
	}

	#[\Override]
	public function warning($message, array $context = []): void {
		$this->parentLogger->warning($message, $context);
	}

	#[\Override]
	public function notice($message, array $context = []): void {
		$this->parentLogger->notice($message, $context);
	}

	#[\Override]
	public function info($message, array $context = []): void {
		$this->parentLogger->info($message, $context);
	}

	#[\Override]
	public function debug($message, array $context = []): void {
		$this->parentLogger->debug($message, $context);
	}

	#[\Override]
	public function log($level, $message, array $context = []): void {
		$this->parentLogger->log($level, $message, $context);
	}
}

<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Log;

use OC\Log;
use OC\SystemConfig;
use OCP\IServerContainer;
use OCP\Log\ILogFactory;
use OCP\Log\IWriter;
use Psr\Log\LoggerInterface;

class LogFactory implements ILogFactory {
	public function __construct(
		private IServerContainer $c,
		private SystemConfig $systemConfig,
	) {
	}

	/**
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function get(string $type):IWriter {
		return match (strtolower($type)) {
			'errorlog' => new Errorlog($this->systemConfig),
			'syslog' => $this->c->resolve(Syslog::class),
			'systemd' => $this->c->resolve(Systemdlog::class),
			'file' => $this->buildLogFile(),
			default => $this->buildLogFile(),
		};
	}

	protected function createNewLogger(string $type, string $tag, string $path): IWriter {
		return match (strtolower($type)) {
			'errorlog' => new Errorlog($this->systemConfig, $tag),
			'syslog' => new Syslog($this->systemConfig, $tag),
			'systemd' => new Systemdlog($this->systemConfig, $tag),
			default => $this->buildLogFile($path),
		};
	}

	public function getCustomPsrLogger(string $path, string $type = 'file', string $tag = 'Nextcloud'): LoggerInterface {
		$log = $this->createNewLogger($type, $tag, $path);
		return new PsrLoggerAdapter(
			new Log($log, $this->systemConfig)
		);
	}

	protected function buildLogFile(string $logFile = ''): File {
		$defaultLogFile = $this->systemConfig->getValue('datadirectory', \OC::$SERVERROOT . '/data') . '/nextcloud.log';
		if ($logFile === '') {
			$logFile = $this->systemConfig->getValue('logfile', $defaultLogFile);
		}
		$fallback = $defaultLogFile !== $logFile ? $defaultLogFile : '';

		return new File($logFile, $fallback, $this->systemConfig);
	}
}

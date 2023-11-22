<?php
/**
 * @copyright Copyright (c) 2018 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Johannes Ernst <jernst@indiecomputing.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Log;

use OC\Log;
use OC\SystemConfig;
use OCP\ILogger;
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

	public function getCustomLogger(string $path): ILogger {
		$log = $this->buildLogFile($path);
		return new Log($log, $this->systemConfig);
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
		$defaultLogFile = $this->systemConfig->getValue('datadirectory', \OC::$SERVERROOT.'/data').'/nextcloud.log';
		if ($logFile === '') {
			$logFile = $this->systemConfig->getValue('logfile', $defaultLogFile);
		}
		$fallback = $defaultLogFile !== $logFile ? $defaultLogFile : '';

		return new File($logFile, $fallback, $this->systemConfig);
	}
}

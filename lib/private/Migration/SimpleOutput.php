<?php
/**
 * @copyright Copyright (c) 2017, ownCloud GmbH
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Migration;

use OCP\Migration\IOutput;
use Psr\Log\LoggerInterface;

/**
 * Class SimpleOutput
 *
 * Just a simple IOutput implementation with writes messages to the log file.
 * Alternative implementations will write to the console or to the web ui (web update case)
 *
 * @package OC\Migration
 */
class SimpleOutput implements IOutput {
	public function __construct(
		private LoggerInterface $logger,
		private $appName,
	) {
	}

	public function debug(string $message): void {
		$this->logger->debug($message, ['app' => $this->appName]);
	}

	/**
	 * @param string $message
	 * @since 9.1.0
	 */
	public function info($message): void {
		$this->logger->info($message, ['app' => $this->appName]);
	}

	/**
	 * @param string $message
	 * @since 9.1.0
	 */
	public function warning($message): void {
		$this->logger->warning($message, ['app' => $this->appName]);
	}

	/**
	 * @param int $max
	 * @since 9.1.0
	 */
	public function startProgress($max = 0): void {
	}

	/**
	 * @param int $step
	 * @param string $description
	 * @since 9.1.0
	 */
	public function advance($step = 1, $description = ''): void {
	}

	/**
	 * @since 9.1.0
	 */
	public function finishProgress(): void {
	}
}

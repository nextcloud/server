<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
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

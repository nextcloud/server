<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework;

use Psr\Log\LoggerInterface;
use function array_merge;

class ScopedPsrLogger implements LoggerInterface {
	public function __construct(
		private LoggerInterface $inner,
		private string $appId,
	) {
	}

	#[\Override]
	public function emergency($message, array $context = []): void {
		$this->inner->emergency(
			$message,
			array_merge(
				[
					'app' => $this->appId,
				],
				$context
			)
		);
	}

	#[\Override]
	public function alert($message, array $context = []): void {
		$this->inner->alert(
			$message,
			array_merge(
				[
					'app' => $this->appId,
				],
				$context
			)
		);
	}

	#[\Override]
	public function critical($message, array $context = []): void {
		$this->inner->critical(
			$message,
			array_merge(
				[
					'app' => $this->appId,
				],
				$context
			)
		);
	}

	#[\Override]
	public function error($message, array $context = []): void {
		$this->inner->error(
			$message,
			array_merge(
				[
					'app' => $this->appId,
				],
				$context
			)
		);
	}

	#[\Override]
	public function warning($message, array $context = []): void {
		$this->inner->warning(
			$message,
			array_merge(
				[
					'app' => $this->appId,
				],
				$context
			)
		);
	}

	#[\Override]
	public function notice($message, array $context = []): void {
		$this->inner->notice(
			$message,
			array_merge(
				[
					'app' => $this->appId,
				],
				$context
			)
		);
	}

	#[\Override]
	public function info($message, array $context = []): void {
		$this->inner->info(
			$message,
			array_merge(
				[
					'app' => $this->appId,
				],
				$context
			)
		);
	}

	#[\Override]
	public function debug($message, array $context = []): void {
		$this->inner->debug(
			$message,
			array_merge(
				[
					'app' => $this->appId,
				],
				$context
			)
		);
	}

	#[\Override]
	public function log($level, $message, array $context = []): void {
		$this->inner->log(
			$level,
			$message,
			array_merge(
				[
					'app' => $this->appId,
				],
				$context
			)
		);
	}
}

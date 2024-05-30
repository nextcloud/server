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
	/** @var LoggerInterface */
	private $inner;

	/** @var string */
	private $appId;

	public function __construct(LoggerInterface $inner,
		string $appId) {
		$this->inner = $inner;
		$this->appId = $appId;
	}

	public function emergency($message, array $context = []) {
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

	public function alert($message, array $context = []) {
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

	public function critical($message, array $context = []) {
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

	public function error($message, array $context = []) {
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

	public function warning($message, array $context = []) {
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

	public function notice($message, array $context = []) {
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

	public function info($message, array $context = []) {
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

	public function debug($message, array $context = []) {
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

	public function log($level, $message, array $context = []) {
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

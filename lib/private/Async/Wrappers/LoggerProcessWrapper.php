<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Wrappers;

use OC\Async\Enum\ProcessActivity;
use OC\Async\AProcessWrapper;
use OC\Async\Model\Process;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class LoggerProcessWrapper extends AProcessWrapper {
	private array $metadata = [];
	public function __construct(
		private LoggerInterface $logger,
	) {
	}

	// TODO: switch to debug()
	public function session(array $metadata): void {
		$this->metadata = $metadata;
		$this->logger->warning('session: ' . json_encode($metadata));
	}

	public function init(Process $process): void {
		$this->logger->warning('process: ' . $this->metadata['sessionToken'] . ' ' . $process->getToken());
	}

	public function activity(ProcessActivity $activity, string $line = ''): void {
		$this->logger->warning('activity (' . $activity->value . ') ' . $line);
	}

	public function end(string $line = ''): void {
		$this->logger->warning('end session - ' . $line);
	}
}

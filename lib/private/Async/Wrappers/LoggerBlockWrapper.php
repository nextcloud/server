<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Async\Wrappers;

use OC\Async\ABlockWrapper;
use OC\Async\Model\Block;
use OCP\Async\Enum\BlockActivity;
use Psr\Log\LoggerInterface;

class LoggerBlockWrapper extends ABlockWrapper {
	private array $metadata = [];
	public function __construct(
		private LoggerInterface $logger,
	) {
	}

	public function session(array $metadata): void {
		$this->metadata = $metadata;
		$this->logger->debug('session: ' . json_encode($metadata));
	}

	public function init(): void {
		$this->logger->debug('process: ' . $this->metadata['sessionToken'] . ' ' . $this->block->getToken());
	}

	public function activity(BlockActivity $activity, string $line = ''): void {
		$this->logger->debug('activity (' . $activity->value . ') ' . $line);
	}

	public function end(string $line = ''): void {
		$this->logger->debug('end session - ' . $line);
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Snowflake;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Nextcloud Snowflake DTO
 *
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
final readonly class Snowflake {
	/**
	 * @psalm-param int<0,1023> $serverId
	 * @psalm-param int<0,4095> $sequenceId
	 * @psalm-param non-negative-int $seconds
	 * @psalm-param int<0,999> $milliseconds
	 */
	public function __construct(
		private int $serverId,
		private int $sequenceId,
		private bool $isCli,
		private int $seconds,
		private int $milliseconds,
		private \DateTimeImmutable $createdAt,
	) {
	}

	/**
	 * @psalm-return int<0,1023>
	 */
	public function getServerId(): int {
		return $this->serverId;
	}

	/**
	 * @psalm-return int<0,4095>
	 */
	public function getSequenceId(): int {
		return $this->sequenceId;
	}

	public function isCli(): bool {
		return $this->isCli;
	}

	/**
	 * @psalm-return non-negative-int
	 */
	public function getSeconds(): int {
		return $this->seconds;
	}

	/**
	 * @psalm-return  int<0,999>
	 */
	public function getMilliseconds(): int {
		return $this->milliseconds;
	}

	public function getCreatedAt(): \DateTimeImmutable {
		return $this->createdAt;
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Snowflake;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Nextcloud Snowflake DTO
 *
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
final class Snowflake {
	protected int $serverId = 0;
	protected int $sequenceId = 0;
	protected bool $isCli = false;
	protected int $seconds = 0;
	protected int $milliseconds = 0;
	protected \DateTimeImmutable $createdAt;

	public function __construct() {
		$this->createdAt = new \DateTimeImmutable();
	}
	public static function fromArray(array $data): self {
		$snowflake = new self();
		$snowflake->setServerId($data['server_id']);
		$snowflake->setSequenceId($data['sequence_id']);
		$snowflake->setIsCli($data['is_cli']);
		$snowflake->setSeconds($data['seconds']);
		$snowflake->setMilliseconds($data['milliseconds']);
		$snowflake->setCreatedAt($data['created_at']);
		return $snowflake;
	}

	public function toArray(): array {
		return [
			'server_id' => $this->getServerId(),
			'sequence_id' => $this->getSequenceId(),
			'is_cli' => $this->isCli(),
			'seconds' => $this->getSeconds(),
			'milliseconds' => $this->getMilliseconds(),
			'createdAt' => $this->getCreatedAt(),
		];
	}

	public function getServerId(): int {
		return $this->serverId;
	}

	/**
	 * @psalm-param int<0,1023> $serverId
	 */
	public function setServerId(int $serverId): void {
		$this->serverId = $serverId;
	}

	public function getSequenceId(): int {
		return $this->sequenceId;
	}

	/**
	 * @psalm-param int<0,4095> $sequenceId
	 */
	public function setSequenceId(int $sequenceId): void {
		$this->sequenceId = $sequenceId;
	}

	public function isCli(): bool {
		return $this->isCli;
	}

	public function setIsCli(bool $isCli): void {
		$this->isCli = $isCli;
	}

	public function getSeconds(): int {
		return $this->seconds;
	}

	/**
	 * @psalm-param  non-negative-int $seconds
	 */
	public function setSeconds(int $seconds): void {
		$this->seconds = $seconds;
	}

	public function getMilliseconds(): int {
		return $this->milliseconds;
	}

	/**
	 * @psalm-param  int<0,999> $milliseconds
	 */
	public function setMilliseconds(int $milliseconds): void {
		$this->milliseconds = $milliseconds;
	}

	public function getCreatedAt(): \DateTimeImmutable {
		return $this->createdAt;
	}

	public function setCreatedAt(\DateTimeImmutable $createdAt): void {
		$this->createdAt = $createdAt;
	}
}

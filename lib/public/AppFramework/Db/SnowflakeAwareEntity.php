<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Db;

use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\Types;
use OCP\Server;
use OCP\Snowflake\ISnowflakeDecoder;
use OCP\Snowflake\ISnowflakeGenerator;
use OCP\Snowflake\Snowflake;

/**
 * Entity with snowflake support
 * @method string getId()
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
abstract class SnowflakeAwareEntity extends Entity {
	protected ?Snowflake $snowflake = null;

	/** @psalm-param $_fieldTypes array<string, Types::*> */
	protected array $_fieldTypes = ['id' => Types::STRING];

	public function setId($id): void {
		throw new \LogicException('Use generated id to set a new id to the Snowflake aware entity.');
	}

	/**
	 * Automatically creates a snowflake ID
	 */
	public function generateId(): void {
		if ($this->id === null) {
			$this->id = Server::get(ISnowflakeGenerator::class)->nextId();
			$this->markFieldUpdated('id');
		}
	}

	public function getCreatedAt(): ?\DateTimeImmutable {
		return $this->getSnowflake()?->getCreatedAt();
	}

	public function getSnowflake(): ?Snowflake {
		if ($this->id === null) {
			return null;
		}

		if ($this->snowflake === null) {
			$this->snowflake = Server::get(ISnowflakeDecoder::class)->decode($this->id);
		}

		return $this->snowflake;
	}
}

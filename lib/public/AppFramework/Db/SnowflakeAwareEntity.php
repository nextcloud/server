<?php

namespace OCP\AppFramework\Db;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Server;
use OCP\Snowflake\ISnowflakeDecoder;
use OCP\Snowflake\ISnowflakeGenerator;
use OCP\Snowflake\Snowflake;

/**
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
abstract class SnowflakeAwareEntity extends Entity {
	/** @var string $id */
	public $id;

	/** @var array<string, \OCP\DB\Types::*> */
	private array $_fieldTypes = ['id' => 'string'];

	#[\Override]
	public function setId(): void {
		if (empty($this->id)) {
			$this->id = Server::get(ISnowflakeGenerator::class)->nextId();
			$this->markFieldUpdated('id');
		}
	}

	#[\Override]
	public function getId(): string {
		return $this->id;
	}

	public function getCreatedAt(): ?\DateTimeImmutable {
		if (empty($this->id)) {
			return null;
		}
		return Server::get(ISnowflakeDecoder::class)->decodeToSnowflake($this->id)->getCreatedAt();
	}

	public function getSnowflake(): ?Snowflake {
		if (empty($this->id)) {
			return null;
		}
		return Server::get(ISnowflakeDecoder::class)->decodeToSnowflake($this->id);
	}
}

<?php

namespace OCP\AppFramework\Db;

use OCP\Server;
use OCP\Snowflake\IDecoder;
use OCP\Snowflake\IGenerator;

/**
 * @method string getId()
 */
class SnowflakeAwareEntity extends Entity {
	/** @var string $id */
	public $id;

	/** @var array<string, \OCP\DB\Types::*> */
	private array $_fieldTypes = ['id' => 'string'];

	#[\Override]
	public function setId(): void {
		if (empty($this->id)) {
			$generator = Server::get(IGenerator::class);
			$this->id = $generator->nextId();
			$this->markFieldUpdated('id');
		}
	}

	public function getCreatedAt(): ?\DateTimeImmutable {
		if (empty($this->id)) {
			return null;
		}
		$decoder = Server::get(IDecoder::class);
		return $decoder->decode($this->id)['created_at'];
	}

	public function getDecodedId(): array {
		if (empty($this->id)) {
			return [];
		}
		return Server::get(IDecoder::class)->decode($this->id);
	}
}

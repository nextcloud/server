<?php

namespace OCP\AppFramework\Db;

use OC\Snowflake\Decoder;
use OC\Snowflake\Generator;
use OCP\Server;

class SnowflakeAwareEntity extends Entity {

	#[\Override]
	public function setId(): string {
		$generator = Server::get(Generator::class);
		$this->id = $generator->nextId();
	}

	public function getCreatedAt(): \DateTimeImmutable {
		$decoder = Server::get(Decoder::class);
		return $decoder->decode($this->id)['created_at'];
	}

	public function getDecodedId(): array {
		return Server::get(Decoder::class)->decode($this->id);
	}
}

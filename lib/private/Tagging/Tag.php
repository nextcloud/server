<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Tagging;

use OCP\AppFramework\Db\Attribute\Column;
use OCP\AppFramework\Db\Attribute\Entity;
use OCP\AppFramework\Db\Attribute\Id;
use OCP\AppFramework\Db\Attribute\Table;
use OCP\DB\Types;
use OCP\Snowflake\IGenerator;

/**
 * Class to represent a tag.
 */
#[Entity]
#[Table(name: 'vcategory')]
final class Tag {
	#[Id(generatorClass: IGenerator::class)]
	#[Column(name: 'id', type: Types::BIGINT, nullable: false)]
	public ?string $id = null;

	#[Column(name: 'uid', type: Types::STRING, length: 64, nullable: false)]
	public string $owner;

	#[Column(name: 'type', type: Types::STRING, length: 64, nullable: false)]
	public string $type;

	#[Column(name: 'category', type: Types::STRING, length: 255, nullable: false)]
	public string $name;

	/**
	 * @param ?string $owner The tag's owner
	 * @param ?string $type The type of item this tag is used for
	 * @param ?string $name The tag's name
	 */
	public function __construct(?string $owner = null, ?string $type = null, ?string $name = null) {
		$this->owner = $owner;
		$this->type = $type;
		$this->name = $name;
	}
}

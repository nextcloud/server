<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Tagging;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;

/**
 * Class to represent a tag.
 */
#[Entity(name: 'vcategory')]
final class Tag {
	#[Id]
	#[Column(name: 'id', type: ColumnType::Bigint, nullable: false)]
	public ?int $id = null;

	#[Column(name: 'uid', type: ColumnType::String, length: 64, nullable: false)]
	public string $owner;

	#[Column(name: 'type', type: ColumnType::String, length: 64, nullable: false)]
	public string $type;

	#[Column(name: 'category', type: ColumnType::String, length: 255, nullable: false)]
	public string $name;
}

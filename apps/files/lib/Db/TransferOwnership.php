<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Db;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;

/**
 * @psalm-suppress MissingConstructor ORM based hydration
 */
#[Entity(name: 'user_transfer_owner')]
final class TransferOwnership {
	#[Id]
	#[Column(name: 'id', type: ColumnType::Bigint)]
	public int $id;

	#[Column(name: 'source_user', type: ColumnType::String, length: 64)]
	public string $sourceUser;

	#[Column(name: 'target_user', type: ColumnType::String, length: 64)]
	public string $targetUser;

	#[Column(name: 'file_id', type: ColumnType::Bigint)]
	public int $fileId;

	#[Column(name: 'node_name', type: ColumnType::String, length: 255)]
	public string $nodeName;
}

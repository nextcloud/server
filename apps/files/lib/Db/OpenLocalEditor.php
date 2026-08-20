<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Db;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;

#[Entity(name: 'open_local_editor')]
final class OpenLocalEditor {
	#[Id]
	#[Column(name: 'id', type: ColumnType::Bigint)]
	public int $id;

	#[Column(name: 'user_id', type: ColumnType::String, length: 64)]
	public string $userId;

	#[Column(name: 'path_hash', type: ColumnType::String, length: 64)]
	public string $pathHash;

	#[Column(name: 'expiration_time', type: ColumnType::Bigint)]
	public int $expirationTime;

	#[Column(name: 'token', type: ColumnType::String, length: 128)]
	public string $token;
}

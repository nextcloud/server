<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Db;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;

#[Entity(name: 'oauth2_access_tokens')]
class AccessToken {
	#[Id, Column(name: 'id', type: ColumnType::Integer)]
	public int $id;

	#[Column(name: 'token_id', type: ColumnType::Integer)]
	public int $tokenId;

	#[Column(name: 'client_id', type: ColumnType::Integer)]
	public int $clientId;

	#[Column(name: 'hashed_code', type: ColumnType::String, length: 128)]
	public string $hashedCode;

	#[Column(name: 'encrypted_token', type: ColumnType::String, length: 786)]
	public string $encryptedToken;

	#[Column(name: 'code_created_at', type: ColumnType::Bigint, default: 0)]
	public int $codeCreatedAt = 0;

	#[Column(name: 'token_count', type: ColumnType::Bigint, default: 0)]
	public int $tokenCount = 0;
}

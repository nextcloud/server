<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\RememberLogin;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;
use OCP\Snowflake\ISnowflakeGenerator;

#[Entity(name: 'remember_login_tokens')]
final class RememberLoginToken {
	#[Id(generatorClass: ISnowflakeGenerator::class)]
	#[Column(name: 'id', type: ColumnType::Bigint)]
	public ?string $id = null;

	#[Column(name: 'uid', type: ColumnType::String, length: 64)]
	public string $uid;

	#[Column(name: 'token', type: ColumnType::String, length: 200)]
	public string $token;
}

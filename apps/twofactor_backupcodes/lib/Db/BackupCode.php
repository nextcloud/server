<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorBackupCodes\Db;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;
use OCP\Snowflake\ISnowflakeGenerator;

#[Entity(name: 'twofactor_backupcodes')]
final class BackupCode {
	#[Id(generatorClass: ISnowflakeGenerator::class)]
	#[Column(name: 'id', type: ColumnType::String, length: 64, nullable: false)]
	public ?string $id = null;

	#[Column(name: 'user_id', type: ColumnType::String, length: 64, nullable: false)]
	public string $userId;

	#[Column(name: 'code', type: ColumnType::String, length: 128, nullable: false)]
	public string $code;

	#[Column(name: 'used', type: ColumnType::Smallint, nullable: false, default: 0)]
	public int $used = 0;
}

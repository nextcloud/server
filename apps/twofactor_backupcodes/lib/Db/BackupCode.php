<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Db;

use OCP\AppFramework\Db\Attribute\Column;
use OCP\AppFramework\Db\Attribute\Entity;
use OCP\AppFramework\Db\Attribute\Id;
use OCP\AppFramework\Db\Attribute\Table;
use OCP\DB\Types;
use OCP\Snowflake\IGenerator;

#[Entity]
#[Table(name: 'twofactor_backupcodes')]
final class BackupCode {
	#[Id(generatorClass: IGenerator::class)]
	#[Column(name: 'id', type: Types::STRING, length: 64, nullable: false)]
	public ?string $id = null;

	#[Column(name: 'user_id', type: Types::STRING, length: 64, nullable: false)]
	public string $userId;

	#[Column(name: 'code', type: Types::STRING, length: 128, nullable: false)]
	public string $code;

	#[Column(name: 'used', type: Types::SMALLINT, nullable: false, default: 0)]
	public int $used = 0;
}

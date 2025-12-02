<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Db;

use OCP\AppFramework\Db\Attribute\Column;
use OCP\AppFramework\Db\Attribute\Table;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method string getId()
 * @method void setId(string $id)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getCode()
 * @method void setCode(string $code)
 * @method int getUsed()
 * @method void setUsed(int $code)
 */
#[Table(name: 'twofactor_backupcodes', useSnowflakeId: true)]
class BackupCode extends Entity {
	#[Column(name: 'user_id', type: Types::STRING, length: 64, nullable: false)]
	protected ?string $userId = null;

	#[Column(name: 'code', type: Types::STRING, length: 128, nullable: false)]
	protected ?string $code = null;

	#[Column(name: 'used', type: Types::SMALLINT, nullable: false)]
	protected ?int $used = null;
}

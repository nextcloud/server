<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getCode()
 * @method void setCode(string $code)
 * @method int getUsed()
 * @method void setUsed(int $code)
 */
class BackupCode extends Entity {

	/** @var string */
	protected $userId;

	/** @var string */
	protected $code;

	/** @var int */
	protected $used;
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorBackupCodes\Db;

use OCP\AppFramework\ORM\Repository;
use OCP\IDBConnection;
use OCP\IUser;

/**
 * @template-extends Repository<BackupCode>
 */
class BackupCodeMapper extends Repository {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, BackupCode::class);
	}

	/**
	 * @return \Generator<BackupCode>
	 */
	public function findByUser(IUser $user): \Generator {
		return $this->findBy([
			'userId' => $user->getUID(),
		]);
	}

	public function deleteByUser(IUser $user): void {
		$this->deleteBy([
			'userId' => $user->getUID(),
		]);
	}

	public function findOneByUser(IUser $user): ?BackupCode {
		return $this->findOneBy([
			'userId' => $user->getUID(),
		]);
	}
}

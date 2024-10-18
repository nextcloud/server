<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Service;

use OCA\TwoFactorBackupCodes\Db\BackupCode;
use OCA\TwoFactorBackupCodes\Db\BackupCodeMapper;
use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;

class BackupCodeStorage {
	private static $CODE_LENGTH = 16;

	public function __construct(
		private BackupCodeMapper $mapper,
		private ISecureRandom $random,
		private IHasher $hasher,
		private IEventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * @param IUser $user
	 * @param int $number
	 * @return string[]
	 */
	public function createCodes(IUser $user, int $number = 10): array {
		$result = [];

		// Delete existing ones
		$this->mapper->deleteCodes($user);

		$uid = $user->getUID();
		foreach (range(1, min([$number, 20])) as $i) {
			$code = $this->random->generate(self::$CODE_LENGTH, ISecureRandom::CHAR_HUMAN_READABLE);

			$dbCode = new BackupCode();
			$dbCode->setUserId($uid);
			$dbCode->setCode($this->hasher->hash($code));
			$dbCode->setUsed(0);
			$this->mapper->insert($dbCode);

			$result[] = $code;
		}

		$this->eventDispatcher->dispatchTyped(new CodesGenerated($user));

		return $result;
	}

	/**
	 * @param IUser $user
	 * @return bool
	 */
	public function hasBackupCodes(IUser $user): bool {
		$codes = $this->mapper->getBackupCodes($user);
		return count($codes) > 0;
	}

	/**
	 * @param IUser $user
	 * @return array
	 */
	public function getBackupCodesState(IUser $user): array {
		$codes = $this->mapper->getBackupCodes($user);
		$total = count($codes);
		$used = 0;
		array_walk($codes, function (BackupCode $code) use (&$used): void {
			if ((int)$code->getUsed() === 1) {
				$used++;
			}
		});
		return [
			'enabled' => $total > 0,
			'total' => $total,
			'used' => $used,
		];
	}

	/**
	 * @param IUser $user
	 * @param string $code
	 * @return bool
	 */
	public function validateCode(IUser $user, string $code): bool {
		$dbCodes = $this->mapper->getBackupCodes($user);

		foreach ($dbCodes as $dbCode) {
			if ((int)$dbCode->getUsed() === 0 && $this->hasher->verify($code, $dbCode->getCode())) {
				$dbCode->setUsed(1);
				$this->mapper->update($dbCode);
				return true;
			}
		}
		return false;
	}

	public function deleteCodes(IUser $user): void {
		$this->mapper->deleteCodes($user);
	}
}

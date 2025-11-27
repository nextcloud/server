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
		$this->mapper->deleteByUser($user);

		$uid = $user->getUID();
		foreach (range(1, min([$number, 20])) as $i) {
			$code = $this->random->generate(self::$CODE_LENGTH, ISecureRandom::CHAR_HUMAN_READABLE);

			$dbCode = new BackupCode();
			$dbCode->userId = $uid;
			$dbCode->code = $this->hasher->hash($code);
			$dbCode->used = 0;
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
		return $this->mapper->findOneByUser($user) !== null;
	}

	/**
	 * @param IUser $user
	 * @return array
	 */
	public function getBackupCodesState(IUser $user): array {
		$codes = $this->mapper->findByUser($user);
		$total = 0;
		$used = 0;

		foreach ($codes as $code) {
			$total++;
			if ($code->used === 1) {
				$used++;
			}
		}
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
		$dbCodes = $this->mapper->findByUser($user);

		foreach ($dbCodes as $dbCode) {
			if ($dbCode->used === 0 && $this->hasher->verify($code, $dbCode->code)) {
				$dbCode->used = 1;
				$this->mapper->update($dbCode);
				return true;
			}
		}
		return false;
	}

	public function deleteCodes(IUser $user): void {
		$this->mapper->deleteByUser($user);
	}
}

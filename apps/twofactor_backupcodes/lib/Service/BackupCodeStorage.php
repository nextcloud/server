<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\TwoFactorBackupCodes\Service;

use OCA\TwoFactorBackupCodes\Db\BackupCode;
use OCA\TwoFactorBackupCodes\Db\BackupCodeMapper;
use OCP\IUser;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;

class BackupCodeStorage {

	/** @var BackupCodeMapper */
	private $mapper;

	/** @var IHasher */
	private $hasher;

	/** @var ISecureRandom */
	private $random;

	public function __construct(BackupCodeMapper $mapper, ISecureRandom $random, IHasher $hasher) {
		$this->mapper = $mapper;
		$this->hasher = $hasher;
		$this->random = $random;
	}

	/**
	 * @param IUser $user
	 * @return string[]
	 */
	public function createCodes(IUser $user, $number = 10) {
		$result = [];

		// Delete existing ones
		$this->mapper->deleteCodes($user);

		$uid = $user->getUID();
		foreach (range(1, min([$number, 20])) as $i) {
			$code = $this->random->generate(10, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

			$dbCode = new BackupCode();
			$dbCode->setUserId($uid);
			$dbCode->setCode($this->hasher->hash($code));
			$dbCode->setUsed(0);
			$this->mapper->insert($dbCode);

			array_push($result, $code);
		}

		return $result;
	}

	/**
	 * @param IUser $user
	 * @return bool
	 */
	public function hasBackupCodes(IUser $user) {
		$codes = $this->mapper->getBackupCodes($user);
		return count($codes) > 0;
	}

	/**
	 * @param IUser $user
	 * @return array
	 */
	public function getBackupCodesState(IUser $user) {
		$codes = $this->mapper->getBackupCodes($user);
		$total = count($codes);
		$used = 0;
		array_walk($codes, function (BackupCode $code) use (&$used) {
			if (1 === (int) $code->getUsed()) {
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
	public function validateCode(IUser $user, $code) {
		$dbCodes = $this->mapper->getBackupCodes($user);

		foreach ($dbCodes as $dbCode) {
			if (0 === (int) $dbCode->getUsed() && $this->hasher->verify($code, $dbCode->getCode())) {
				$dbCode->setUsed(1);
				$this->mapper->update($dbCode);
				return true;
			}
		}
		return false;
	}

}

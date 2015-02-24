<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 * @since 2/19/15, 11:45 AM
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption;


use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCP\Encryption\IKeyStorage;
use OCP\IConfig;
use OCP\IUser;
use OCP\Security\ISecureRandom;

class Recovery {


	/**
	 * @var null|IUser
	 */
	protected $user;
	/**
	 * @var Crypt
	 */
	protected $crypt;
	/**
	 * @var ISecureRandom
	 */
	private $random;
	/**
	 * @var KeyManager
	 */
	private $keyManager;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IEncryptionKeyStorage
	 */
	private $keyStorage;

	/**
	 * @param IUser $user
	 * @param Crypt $crypt
	 * @param ISecureRandom $random
	 * @param KeyManager $keyManager
	 * @param IConfig $config
	 * @param IKeyStorage $keyStorage
	 */
	public function __construct(IUser $user,
								Crypt $crypt,
								ISecureRandom $random,
								KeyManager $keyManager,
								IConfig $config,
								IKeyStorage $keyStorage) {
		$this->user = $user;
		$this->crypt = $crypt;
		$this->random = $random;
		$this->keyManager = $keyManager;
		$this->config = $config;
		$this->keyStorage = $keyStorage;
	}

	/**
	 * @param $recoveryKeyId
	 * @param $password
	 * @return bool
	 */
	public function enableAdminRecovery($recoveryKeyId, $password) {
		$appConfig = $this->config;

		if ($recoveryKeyId === null) {
			$recoveryKeyId = $this->random->getLowStrengthGenerator();
			$appConfig->setAppValue('encryption', 'recoveryKeyId', $recoveryKeyId);
		}

		$keyManager = $this->keyManager;

		if (!$keyManager->recoveryKeyExists()) {
			$keyPair = $this->crypt->createKeyPair();

			return $this->keyManager->storeKeyPair($password, $keyPair);
		}

		if ($keyManager->checkRecoveryPassword($password)) {
			$appConfig->setAppValue('encryption', 'recoveryAdminEnabled', 1);
			return true;
		}

		return false;
	}

	/**
	 * @param $recoveryPassword
	 * @return bool
	 */
	public function disableAdminRecovery($recoveryPassword) {
		$keyManager = $this->keyManager;

		if ($keyManager->checkRecoveryPassword($recoveryPassword)) {
			// Set recoveryAdmin as disabled
			$this->config->setAppValue('encryption', 'recoveryAdminEnabled', 0);
			return true;
		}
		return false;
	}

	public function addRecoveryKeys($keyId) {
		// No idea new way to do this....
	}

	public function removeRecoveryKeys() {
		// No idea new way to do this....
	}

}

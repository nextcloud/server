<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Users;

use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\KeyManager;

class Setup {
	/** @var Crypt */
	private $crypt;
	/** @var KeyManager */
	private $keyManager;

	public function __construct(Crypt $crypt,
		KeyManager $keyManager) {
		$this->crypt = $crypt;
		$this->keyManager = $keyManager;
	}

	/**
	 * @param string $uid user id
	 * @param string $password user password
	 * @return bool
	 */
	public function setupUser($uid, $password) {
		if (!$this->keyManager->userHasKeys($uid)) {
			$keyPair = $this->crypt->createKeyPair();
			return is_array($keyPair) ? $this->keyManager->storeKeyPair($uid, $password, $keyPair) : false;
		}
		return true;
	}

	/**
	 * make sure that all system keys exists
	 */
	public function setupSystem() {
		$this->keyManager->validateShareKey();
		$this->keyManager->validateMasterKey();
	}
}

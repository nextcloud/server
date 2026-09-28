<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Users;

use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\KeyManager;
use OCP\ICache;
use OCP\ICacheFactory;

class Setup {
	private readonly ICache $cache;

	public function __construct(
		private Crypt $crypt,
		private KeyManager $keyManager,
		ICacheFactory $cacheFactory,
	) {
		$this->cache = $cacheFactory->createLocal('encryption-setup');
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
		if (!$this->cache->get('keys-validated')) {
			$this->keyManager->validateShareKey();
			$this->keyManager->validateMasterKey();
			$this->cache->set('keys-validated', true);
		}
	}
}

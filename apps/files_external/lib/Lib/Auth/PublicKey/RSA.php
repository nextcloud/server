<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth\PublicKey;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\StorageConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use phpseclib\Crypt\RSA as RSACrypt;

/**
 * RSA public key authentication
 */
class RSA extends AuthMechanism {

	public function __construct(
		IL10N $l,
		private IConfig $config,
	) {
		$this
			->setIdentifier('publickey::rsa')
			->setScheme(self::SCHEME_PUBLICKEY)
			->setText($l->t('RSA public key'))
			->addParameters([
				new DefinitionParameter('user', $l->t('Login')),
				new DefinitionParameter('public_key', $l->t('Public key')),
				(new DefinitionParameter('private_key', 'private_key'))
					->setType(DefinitionParameter::VALUE_HIDDEN),
			])
			->addCustomJs('public_key')
		;
	}

	/**
	 * @return void
	 */
	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null) {
		$auth = new RSACrypt();
		$auth->setPassword($this->config->getSystemValue('secret', ''));
		if (!$auth->loadKey($storage->getBackendOption('private_key'))) {
			// Add fallback routine for a time where secret was not enforced to be exists
			$auth->setPassword('');
			if (!$auth->loadKey($storage->getBackendOption('private_key'))) {
				throw new \RuntimeException('unable to load private key');
			}
		}
		$storage->setBackendOption('public_key_auth', $auth);
	}

	/**
	 * Generate a keypair
	 *
	 * @param int $keyLenth
	 * @return array ['privatekey' => $privateKey, 'publickey' => $publicKey]
	 */
	public function createKey($keyLength) {
		$rsa = new RSACrypt();
		$rsa->setPublicKeyFormat(RSACrypt::PUBLIC_FORMAT_OPENSSH);
		$rsa->setPassword($this->config->getSystemValue('secret', ''));

		if ($keyLength !== 1024 && $keyLength !== 2048 && $keyLength !== 4096) {
			$keyLength = 1024;
		}

		return $rsa->createKey($keyLength);
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
class RSAPrivateKey extends AuthMechanism {

	public function __construct(
		IL10N $l,
		private IConfig $config,
	) {
		$this
			->setIdentifier('publickey::rsa_private')
			->setScheme(self::SCHEME_PUBLICKEY)
			->setText($l->t('RSA private key'))
			->addParameters([
				new DefinitionParameter('user', $l->t('Login')),
				(new DefinitionParameter('password', $l->t('Password')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL)
					->setType(DefinitionParameter::VALUE_PASSWORD),
				new DefinitionParameter('private_key', $l->t('Private key')),
			]);
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
}

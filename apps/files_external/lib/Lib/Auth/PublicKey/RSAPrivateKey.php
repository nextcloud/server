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
use phpseclib3\Crypt\RSA;
use phpseclib3\Exception\NoKeyLoadedException;

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
	#[\Override]
	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null) {

		try {
			$auth = RSA\PrivateKey::loadPrivateKey(
				$storage->getBackendOption('private_key'),
				$this->config->getSystemValue('secret', ''),
			);
		} catch (NoKeyLoadedException) {
			// Add fallback routine for a time where secret was not enforced to be exists
			$auth = RSA\PrivateKey::loadPrivateKey(
				$storage->getBackendOption('private_key'),
				'',
			);
		}
		$storage->setBackendOption('public_key_auth', $auth);
	}
}

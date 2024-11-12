<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\PublicPrivateKeyPairs;

use NCU\Security\PublicPrivateKeyPairs\Exceptions\KeyPairConflictException;
use NCU\Security\PublicPrivateKeyPairs\Exceptions\KeyPairNotFoundException;
use NCU\Security\PublicPrivateKeyPairs\IKeyPairManager;
use NCU\Security\PublicPrivateKeyPairs\Model\IKeyPair;
use OC\Security\PublicPrivateKeyPairs\Model\KeyPair;
use OCP\IAppConfig;

/**
 * @inheritDoc
 *
 * KeyPairManager store internal public/private key pair using AppConfig, taking advantage of the encryption
 * and lazy loading.
 *
 * @since 31.0.0
 */
class KeyPairManager implements IKeyPairManager {
	private const CONFIG_PREFIX = 'security.keypair.';

	public function __construct(
		private readonly IAppConfig $appConfig,
	) {
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app appId
	 * @param string $name key name
	 * @param array $options algorithms, metadata
	 *
	 * @return IKeyPair
	 * @throws KeyPairConflictException if a key already exist
	 * @since 31.0.0
	 */
	public function generateKeyPair(string $app, string $name, array $options = []): IKeyPair {
		if ($this->hasKeyPair($app, $name)) {
			throw new KeyPairConflictException('key pair already exist');
		}

		$keyPair = new KeyPair($app, $name);

		[$publicKey, $privateKey] = $this->generateKeys($options);
		$keyPair->setPublicKey($publicKey)
			->setPrivateKey($privateKey)
			->setOptions($options);

		$this->appConfig->setValueArray(
			$app, $this->generateAppConfigKey($name),
			[
				'public' => $keyPair->getPublicKey(),
				'private' => $keyPair->getPrivateKey(),
				'options' => $keyPair->getOptions()
			],
			lazy:      true,
			sensitive: true
		);

		return $keyPair;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app appId
	 * @param string $name key name
	 *
	 * @return bool TRUE if key pair exists in database
	 * @since 31.0.0
	 */
	public function hasKeyPair(string $app, string $name): bool {
		$key = $this->generateAppConfigKey($name);
		return $this->appConfig->hasKey($app, $key, lazy: true);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app appId
	 * @param string $name key name
	 *
	 * @return IKeyPair
	 * @throws KeyPairNotFoundException if key pair is not known
	 * @since 31.0.0
	 */
	public function getKeyPair(string $app, string $name): IKeyPair {
		if (!$this->hasKeyPair($app, $name)) {
			throw new KeyPairNotFoundException('unknown key pair');
		}

		$key = $this->generateAppConfigKey($name);
		$stored = $this->appConfig->getValueArray($app, $key, lazy: true);
		if (!array_key_exists('public', $stored) ||
			!array_key_exists('private', $stored)) {
			throw new KeyPairNotFoundException('corrupted key pair');
		}

		$keyPair = new KeyPair($app, $name);
		return $keyPair->setPublicKey($stored['public'])
			->setPrivateKey($stored['private'])
			->setOptions($stored['options'] ?? []);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app appid
	 * @param string $name key name
	 *
	 * @since 31.0.0
	 */
	public function deleteKeyPair(string $app, string $name): void {
		$this->appConfig->deleteKey('core', $this->generateAppConfigKey($name));
	}

	/**
	 * @inheritDoc
	 *
	 * @param IKeyPair $keyPair keypair to test
	 *
	 * @return bool
	 * @since 31.0.0
	 */
	public function testKeyPair(IKeyPair $keyPair): bool {
		$clear = md5((string)time());

		// signing with private key
		openssl_sign($clear, $signed, $keyPair->getPrivateKey(), OPENSSL_ALGO_SHA256);
		$encoded = base64_encode($signed);

		// verify with public key
		$signed = base64_decode($encoded);
		return (openssl_verify($clear, $signed, $keyPair->getPublicKey(), 'sha256') === 1);
	}

	/**
	 * return appconfig key based on name of the key pair
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	private function generateAppConfigKey(string $name): string {
		return self::CONFIG_PREFIX . $name;
	}

	/**
	 * generate the key pair, based on $options with the following default values:
	 *   [
	 *     'algorithm' => 'rsa',
	 *     'bits' => 2048,
	 *     'type' => OPENSSL_KEYTYPE_RSA
	 *   ]
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	private function generateKeys(array $options = []): array {
		$res = openssl_pkey_new(
			[
				'digest_alg' => $options['algorithm'] ?? 'rsa',
				'private_key_bits' => $options['bits'] ?? 2048,
				'private_key_type' => $options['type'] ?? OPENSSL_KEYTYPE_RSA,
			]
		);

		openssl_pkey_export($res, $privateKey);
		$publicKey = openssl_pkey_get_details($res)['key'];

		return [$publicKey, $privateKey];
	}
}

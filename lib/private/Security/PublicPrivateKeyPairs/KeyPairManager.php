<?php

declare(strict_types=1);

namespace OC\Security\PublicPrivateKeyPairs;

use OC\Security\PublicPrivateKeyPairs\Model\KeyPair;
use OCP\IAppConfig;
use OCP\Security\PublicPrivateKeyPairs\IKeyPairManager;
use OCP\Security\PublicPrivateKeyPairs\Model\IKeyPair;

class KeyPairManager implements IKeyPairManager {
	private const CONFIG_PREFIX = 'security.keypair.';

	public function __construct(
		private readonly IAppConfig $appConfig,
	) {
	}

	public function getKeyPair(string $app, string $name, array $options = []): IKeyPair {
		$key = $this->generateAppConfigKey($name);
		if (!$this->appConfig->hasKey($app, $key, lazy: true)) {
			return $this->generateKeyPair($app, $name, $options);
		}

		$stored = $this->appConfig->getValueArray($app, $key, lazy: true);
		if (!array_key_exists('public', $stored) ||
			!array_key_exists('private', $stored)) {
			return $this->generateKeyPair($app, $name);
		}

		$keyPair = new KeyPair($app, $name);
		$keyPair->setPublicKey($stored['public'])
				->setPrivateKey($stored['private']);

		return $keyPair;
	}

	public function deleteKeyPair(string $app, string $name): void {
		$this->appConfig->deleteKey('core', $this->generateAppConfigKey($name));
	}

	public function testKeyPair(IKeyPair $keyPair): bool {
		// encrypt using private key
		// decrypt using public key
		// compare
		return false;
	}

	private function generateAppConfigKey(string $name): string {
		return self::CONFIG_PREFIX . $name;
	}

	private function generateKeyPair(string $app, string $name, array $options = []): IKeyPair {
		$keyPair = new KeyPair($app, $name);

		[$publicKey, $privateKey] = $this->generateKeys($options);
		$keyPair->setPublicKey($publicKey)
				->setPrivateKey($privateKey);

		$this->appConfig->setValueArray(
					   $app, $this->generateAppConfigKey($name),
					   [
						   'public' => $keyPair->getPublicKey(),
						   'private' => $keyPair->getPrivateKey()
					   ],
			lazy:      true,
			sensitive: true
		);

		return $keyPair;
	}

	/**
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

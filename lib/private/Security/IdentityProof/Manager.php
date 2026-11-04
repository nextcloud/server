<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\IdentityProof;

use OC\Files\AppData\Factory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\Security\IdentityProof\Algorithm;
use OCP\Security\IdentityProof\IKey;
use OCP\Security\IdentityProof\IManager;
use Psr\Log\LoggerInterface;

class Manager implements IManager {
	private IAppData $appData;

	protected ICache $cache;

	public function __construct(
		Factory $appDataFactory,
		private ICrypto $crypto,
		private IConfig $config,
		private LoggerInterface $logger,
		private ICacheFactory $cacheFactory,
	) {
		$this->appData = $appDataFactory->get('identityproof');
		$this->cache = $this->cacheFactory->createDistributed('identityproof::');
	}

	private function generateKeyId(KeyType $type, Algorithm $algorithm, string $id, string $name = '') {
		$keyId = $algorithm->value . '-';
		if ($algorithm === Algorithm::Rsa2048 || $algorithm === Algorithm::Es256) {
			// Legacy key without algorithm in the name
			$keyId = '';
		}

		$keyId .= $type->value . '-' . $id;
		if ($name !== '') {
			$keyId .= '-' . $name;
		}
		return $keyId;
	}

	/**
	 * Calls the openssl functions to generate a public and private key
	 *
	 * @throws \RuntimeException
	 */
	protected function generateKeyPair(Algorithm $algorithm): IKey {
		if ($algorithm === Algorithm::Rsa2048) {
			$config = [
				'digest_alg' => 'sha512',
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			];
		} elseif ($algorithm === Algorithm::Es256) {
			$config = [
				'private_key_type' => OPENSSL_KEYTYPE_EC,
				'curve_name' => 'prime256v1',
			];
		} elseif ($algorithm === Algorithm::Es384) {
			$config = [
				'private_key_type' => OPENSSL_KEYTYPE_EC,
				'curve_name' => 'secp384r1',
			];
		} else {
			throw new \OutOfBoundsException('Algorithm not implemented');
		}

		$asymmetricKey = openssl_pkey_new($config);
		if ($asymmetricKey === false) {
			$this->logOpensslError();
			throw new \RuntimeException('OpenSSL reported a problem');
		}

		if (openssl_pkey_export($asymmetricKey, $privateKey, null, $config) === false) {
			$this->logOpensslError();
			throw new \RuntimeException('OpenSSL reported a problem');
		}

		$details = openssl_pkey_get_details($asymmetricKey);
		return new Key($details['key'], $privateKey, $algorithm);
	}

	/**
	 * Generate a key for a given ID
	 * Note: If a key already exists it will be overwritten
	 *
	 * @param string $id key id
	 *
	 * @throws \RuntimeException
	 */
	protected function generateKey(Algorithm $algorithm, string $keyId): IKey {
		$key = $this->generateKeyPair($algorithm);

		try {
			$folder = $this->appData->newFolder($keyId);
		} catch (\Exception) {
			$folder = $this->appData->getFolder($keyId);
		}

		// Write the private and public key to the disk
		$folder->newFile('private')
			->putContent($this->crypto->encrypt($key->getPrivate()));
		$folder->newFile('public')
			->putContent($key->getPublic());

		return $key;
	}

	/**
	 * Get key for a specific id
	 *
	 * @throws \RuntimeException
	 */
	protected function retrieveKey(KeyType $type, Algorithm $algorithm, string $id, string $name = ''): IKey {
		$keyId = $this->generateKeyId($type, $algorithm, $id, $name);

		try {
			$cachedPublicKey = $this->cache->get($keyId . '-public');
			$cachedPrivateKey = $this->cache->get($keyId . '-private');

			if ($cachedPublicKey !== null && $cachedPrivateKey !== null) {
				$decryptedPrivateKey = $this->crypto->decrypt($cachedPrivateKey);

				return new Key($cachedPublicKey, $decryptedPrivateKey, $algorithm);
			}

			$folder = $this->appData->getFolder($keyId);
			$privateKey = $folder->getFile('private')->getContent();
			$publicKey = $folder->getFile('public')->getContent();

			$this->cache->set($keyId . '-public', $publicKey);
			$this->cache->set($keyId . '-private', $privateKey);

			$decryptedPrivateKey = $this->crypto->decrypt($privateKey);
			return new Key($publicKey, $decryptedPrivateKey, $algorithm);
		} catch (\Exception) {
			return $this->generateKey($algorithm, $keyId);
		}
	}

	#[\Override]
	public function getUserKey(IUser $user, Algorithm $algorithm): IKey {
		return $this->retrieveKey(KeyType::User, $algorithm, $user->getUID());
	}

	/**
	 * Get public and private key for $user
	 *
	 * @throws \RuntimeException
	 */
	#[\Deprecated(message: 'Not post-quantum save, please transition to another algorithm and use getUserKey() instead.', since: '36.0.0')]
	public function getKey(IUser $user): IKey {
		return $this->getUserKey($user, Algorithm::Rsa2048);
	}

	/**
	 * Set public key for $user
	 * @internal Only used by the OCC command that regenerates a users public key
	 */
	public function setPublicKey(IUser $user, string $publicKey): void {
		$keyId = $this->generateKeyId(KeyType::User, Algorithm::Rsa2048, $user->getUID());

		$folder = $this->appData->getFolder($keyId);
		$folder->newFile('public', $publicKey);

		$this->cache->set($keyId . '-public', $publicKey);
	}

	#[\Override]
	public function getServerKey(Algorithm $algorithm): IKey {
		$instanceId = $this->config->getSystemValue('instanceid', null);
		if ($instanceId === null) {
			throw new \RuntimeException('no instance id!');
		}
		return $this->retrieveKey(KeyType::System, $algorithm, $instanceId);
	}

	#[\Deprecated(message: 'Not post-quantum save, please transition to another algorithm and use getServerKey() instead.', since: '36.0.0')]
	public function getSystemKey(): IKey {
		return $this->getServerKey(Algorithm::Rsa2048);
	}

	#[\Deprecated(message: 'Not post-quantum save, please transition to another algorithm', since: '36.0.0')]
	public function hasAppKey(string $app, string $name): bool {
		$keyId = $this->generateKeyId(KeyType::App, Algorithm::Rsa2048, $app, $name);
		try {
			$folder = $this->appData->getFolder($keyId);
			return ($folder->fileExists('public') && $folder->fileExists('private'));
		} catch (NotFoundException) {
			return false;
		}
	}

	#[\Override]
	public function getAppKey(string $app, Algorithm $algorithm, string $name = ''): IKey {
		return $this->retrieveKey(KeyType::App, $algorithm, $app, $name);
	}

	#[\Deprecated(message: 'Not post-quantum save, please transition to another algorithm', since: '36.0.0')]
	public function deleteAppKey(string $app, string $name): bool {
		$keyId = $this->generateKeyId(KeyType::App, Algorithm::Rsa2048, $app, $name);

		try {
			$folder = $this->appData->getFolder($keyId);
			$folder->delete();
			return true;
		} catch (NotFoundException) {
			return false;
		}
	}

	private function logOpensslError(): void {
		$errors = [];
		while ($error = openssl_error_string()) {
			$errors[] = $error;
		}
		$this->logger->critical('Something is wrong with your openssl setup: ' . implode(', ', $errors));
	}
}

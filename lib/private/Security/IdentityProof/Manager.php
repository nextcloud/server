<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\IdentityProof;

use OC\Files\AppData\Factory;
use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\IUser;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

class Manager {
	private IAppData $appData;

	public function __construct(
		Factory $appDataFactory,
		private ICrypto $crypto,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
		$this->appData = $appDataFactory->get('identityproof');
	}

	/**
	 * Calls the openssl functions to generate a public and private key.
	 * In a separate function for unit testing purposes.
	 *
	 * @return array [$publicKey, $privateKey]
	 * @throws \RuntimeException
	 */
	protected function generateKeyPair(): array {
		$config = [
			'digest_alg' => 'sha512',
			'private_key_bits' => 2048,
		];

		// Generate new key
		$res = openssl_pkey_new($config);

		if ($res === false) {
			$this->logOpensslError();
			throw new \RuntimeException('OpenSSL reported a problem');
		}

		if (openssl_pkey_export($res, $privateKey, null, $config) === false) {
			$this->logOpensslError();
			throw new \RuntimeException('OpenSSL reported a problem');
		}

		// Extract the public key from $res to $pubKey
		$publicKey = openssl_pkey_get_details($res);
		$publicKey = $publicKey['key'];

		return [$publicKey, $privateKey];
	}

	/**
	 * Generate a key for a given ID
	 * Note: If a key already exists it will be overwritten
	 *
	 * @param string $id key id
	 * @throws \RuntimeException
	 */
	protected function generateKey(string $id): Key {
		[$publicKey, $privateKey] = $this->generateKeyPair();

		// Write the private and public key to the disk
		try {
			$this->appData->newFolder($id);
		} catch (\Exception $e) {
		}
		$folder = $this->appData->getFolder($id);
		$folder->newFile('private')
			->putContent($this->crypto->encrypt($privateKey));
		$folder->newFile('public')
			->putContent($publicKey);

		return new Key($publicKey, $privateKey);
	}

	/**
	 * Get key for a specific id
	 *
	 * @throws \RuntimeException
	 */
	protected function retrieveKey(string $id): Key {
		try {
			$folder = $this->appData->getFolder($id);
			$privateKey = $this->crypto->decrypt(
				$folder->getFile('private')->getContent()
			);
			$publicKey = $folder->getFile('public')->getContent();
			return new Key($publicKey, $privateKey);
		} catch (\Exception $e) {
			return $this->generateKey($id);
		}
	}

	/**
	 * Get public and private key for $user
	 *
	 * @throws \RuntimeException
	 */
	public function getKey(IUser $user): Key {
		$uid = $user->getUID();
		return $this->retrieveKey('user-' . $uid);
	}

	/**
	 * Get instance wide public and private key
	 *
	 * @throws \RuntimeException
	 */
	public function getSystemKey(): Key {
		$instanceId = $this->config->getSystemValue('instanceid', null);
		if ($instanceId === null) {
			throw new \RuntimeException('no instance id!');
		}
		return $this->retrieveKey('system-' . $instanceId);
	}

	private function logOpensslError(): void {
		$errors = [];
		while ($error = openssl_error_string()) {
			$errors[] = $error;
		}
		$this->logger->critical('Something is wrong with your openssl setup: ' . implode(', ', $errors));
	}
}

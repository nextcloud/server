<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Security\IdentityProof;

use OC\Files\AppData\Factory;
use OC\Security\IdentityProof\Exception\IdentityProofKeySumException;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IUser;
use OCP\PreConditionNotMetException;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Manager {
	public const SUM_PREFERENCES_KEY = 'identity_proof_key_sum';

	/** @var IAppData */
	private $appData;
	/** @var ICrypto */
	private $crypto;
	/** @var IConfig */
	private $config;
	private LoggerInterface $logger;

	public function __construct(Factory $appDataFactory,
								ICrypto $crypto,
								IConfig $config,
								LoggerInterface $logger
	) {
		$this->appData = $appDataFactory->get('identityproof');
		$this->crypto = $crypto;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * Calls the openssl functions to generate a public and private key.
	 * In a separate function for unit testing purposes.
	 *
	 * @return array [$publicKey, $privateKey]
	 * @throws RuntimeException
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
			throw new RuntimeException('OpenSSL reported a problem');
		}

		if (openssl_pkey_export($res, $privateKey, null, $config) === false) {
			$this->logOpensslError();
			throw new RuntimeException('OpenSSL reported a problem');
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
	 *
	 * @return Key
	 * @throws NotFoundException
	 * @throws NotPermittedException
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
	 * if $userId is set, a checksum of the key will be stored for future comparison
	 *
	 * @param string $id
	 * @param string $userId
	 *
	 * @return Key
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws PreConditionNotMetException
	 */
	protected function retrieveKey(string $id, string $userId = ''): Key {
		try {
			$folder = $this->appData->getFolder($id);
			$privateKey = $this->crypto->decrypt(
				$folder->getFile('private')->getContent()
			);
			$publicKey = $folder->getFile('public')->getContent();
			$key = new Key($publicKey, $privateKey);

			$this->confirmSum($key, $userId);
		} catch (\Exception $e) {
			$key = $this->generateKey($id);
			$this->generateKeySum($key, $userId);
		}

		return $key;
	}


	/**
	 * @param Key $key
	 * @param string $userId
	 *
	 * @throws IdentityProofKeySumException
	 * @throws PreConditionNotMetException
	 */
	protected function confirmSum(Key $key, string $userId): void {
		if ($userId === '') {
			$knownSum = $this->config->getAppValue('core', self::SUM_PREFERENCES_KEY, '');
		} else {
			$knownSum = $this->config->getUserValue($userId, 'core', self::SUM_PREFERENCES_KEY, '');
		}

		if ($knownSum === '') { // sum is not known, generate a new one
			$this->generateKeySum($key, $userId);
		}

		if ($knownSum !== $key->getSum()) {
			throw new IdentityProofKeySumException();
		}
	}


	/**
	 * @param Key $key
	 * @param string $userId
	 *
	 * @return void
	 * @throws PreConditionNotMetException
	 */
	public function generateKeySum(Key $key, string $userId): void {
		if ($userId === '') {
			$this->config->setAppValue('core', self::SUM_PREFERENCES_KEY, $key->getSum());
		} else {
			$this->config->setUserValue($userId, 'core', self::SUM_PREFERENCES_KEY, $key->getSum());
		}
	}


	/**
	 * Get public and private key for $user
	 *
	 * @param IUser $user
	 *
	 * @return Key
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws PreConditionNotMetException
	 */
	public function getKey(IUser $user): Key {
		$uid = $user->getUID();

		return $this->retrieveKey('user-' . $uid, $uid);
	}

	/**
	 * Get instance wide public and private key
	 *
	 * @return Key
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws PreConditionNotMetException
	 */
	public function getSystemKey(): Key {
		$instanceId = $this->config->getSystemValue('instanceid', null);
		if ($instanceId === null) {
			throw new RuntimeException('no instance id!');
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

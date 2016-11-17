<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Security\IdentityProof;

use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IUser;
use OCP\Security\ICrypto;

class Manager {
	/** @var ISimpleFolder */
	private $folder;
	/** @var ICrypto */
	private $crypto;

	/**
	 * @param IAppData $appData
	 * @param ICrypto $crypto
	 */
	public function __construct(IAppData $appData,
								ICrypto $crypto) {
		$this->folder = $appData->getFolder('identityproof');
		$this->crypto = $crypto;
	}

	/**
	 * Generate a key for $user
	 * Note: If a key already exists it will be overwritten
	 *
	 * @param IUser $user
	 * @return Key
	 */
	public function generateKey(IUser $user) {
		$config = [
			'digest_alg' => 'sha512',
			'private_key_bits' => 2048,
		];

		// Generate new key
		$res = openssl_pkey_new($config);
		openssl_pkey_export($res, $privateKey);

		// Extract the public key from $res to $pubKey
		$publicKey = openssl_pkey_get_details($res);
		$publicKey = $publicKey['key'];

		// Write the private and public key to the disk
		$this->folder->newFile($user->getUID() . '.private')
			->putContent($this->crypto->encrypt($privateKey));
		$this->folder->newFile($user->getUID() . '.public')
			->putContent($publicKey);

		return new Key($publicKey, $privateKey);
	}

	/**
	 * Get public and private key for $user
	 *
	 * @param IUser $user
	 * @return Key
	 */
	public function getKey(IUser $user) {
		try {
			$privateKey = $this->crypto->decrypt($this->folder->getFile($user->getUID() . '.private')->getContent());
			$publicKey = $this->folder->getFile($user->getUID() . '.public')->getContent();
			return new Key($publicKey, $privateKey);
		} catch (\Exception $e) {
			return $this->generateKey($user);
		}
	}
}

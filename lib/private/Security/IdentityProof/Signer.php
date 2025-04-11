<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\IdentityProof;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUser;
use OCP\IUserManager;

class Signer {
	public function __construct(
		private Manager $keyManager,
		private ITimeFactory $timeFactory,
		private IUserManager $userManager,
	) {
	}

	/**
	 * Returns a signed blob for $data
	 *
	 * @return array ['message', 'signature']
	 */
	public function sign(string $type, array $data, IUser $user): array {
		$privateKey = $this->keyManager->getKey($user)->getPrivate();
		$data = [
			'data' => $data,
			'type' => $type,
			'signer' => $user->getCloudId(),
			'timestamp' => $this->timeFactory->getTime(),
		];
		openssl_sign(json_encode($data), $signature, $privateKey, OPENSSL_ALGO_SHA512);

		return [
			'message' => $data,
			'signature' => base64_encode($signature),
		];
	}

	/**
	 * Whether the data is signed properly
	 *
	 */
	public function verify(array $data): bool {
		if (isset($data['message']['signer'])
			&& isset($data['signature'])
		) {
			$location = strrpos($data['message']['signer'], '@');
			$userId = substr($data['message']['signer'], 0, $location);

			$user = $this->userManager->get($userId);
			if ($user !== null) {
				$key = $this->keyManager->getKey($user);
				return openssl_verify(
					json_encode($data['message']),
					base64_decode($data['signature']),
					$key->getPublic(),
					OPENSSL_ALGO_SHA512
				) === 1;
			}
		}

		return false;
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Service;

use OC\Authentication\Token\IProvider as IAuthTokenProvider;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Exceptions\WipeTokenException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class ClientService {
	public const validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	public function __construct(
		private readonly ISecureRandom $secureRandom,
		private readonly ICrypto $crypto,
		private readonly ClientMapper $clientMapper,
		private readonly IUserManager $userManager,
		private readonly IAuthTokenProvider $tokenProvider,
		private readonly LoggerInterface $logger,
		private readonly AccessTokenMapper $accessTokenMapper,
	) {
	}

	/**
	 * @param non-empty-string $name
	 * @param non-empty-string $redirectUri
	 * @return array{
	 *     id: int,
	 *     name: string,
	 *     redirectUri: string,
	 *     clientId: string,
	 *     clientSecret: string,
	 * }
	 */
	public function addClient(string $name, string $redirectUri): array {
		$client = new Client();
		$client->setName($name);
		$client->setRedirectUri($redirectUri);
		$secret = $this->secureRandom->generate(64, self::validChars);
		$hashedSecret = bin2hex($this->crypto->calculateHMAC($secret));
		$client->setSecret($hashedSecret);
		$client->setClientIdentifier($this->secureRandom->generate(64, self::validChars));
		$client = $this->clientMapper->insert($client);

		return [
			'id' => $client->getId(),
			'name' => $client->getName(),
			'redirectUri' => $client->getRedirectUri(),
			'clientId' => $client->getClientIdentifier(),
			'clientSecret' => $secret,
		];
	}

	public function deleteClient(int $id): void {
		$client = $this->clientMapper->getByUid($id);

		$this->userManager->callForSeenUsers(function (IUser $user) use ($client): void {
			// Skip tokens that are marked for remote wipe so revoking the
			// OAuth2 client does not silently cancel a pending wipe.
			$tokens = $this->tokenProvider->getTokenByUser($user->getUID());
			foreach ($tokens as $token) {
				if ($token->getName() !== $client->getName()) {
					continue;
				}
				try {
					$this->tokenProvider->getTokenById($token->getId());
				} catch (WipeTokenException) {
					$this->logger->info('Preserving token {tokenId} of user {uid}: marked for remote wipe, OAuth2 client revoke would cancel the wipe.', [
						'tokenId' => $token->getId(),
						'uid' => $user->getUID(),
					]);
					continue;
				} catch (InvalidTokenException) {
					// Token already invalid; let invalidateTokenById handle it.
				}
				$this->tokenProvider->invalidateTokenById($user->getUID(), $token->getId());
			}
		});

		$this->accessTokenMapper->deleteByClientId($id);
		$this->clientMapper->delete($client);
	}
}

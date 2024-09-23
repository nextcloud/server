<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Controller;

use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\Token\IProvider as IAuthTokenProvider;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

class SettingsController extends Controller {

	public const validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	public function __construct(
		string $appName,
		IRequest $request,
		private ClientMapper $clientMapper,
		private ISecureRandom $secureRandom,
		private AccessTokenMapper $accessTokenMapper,
		private IL10N $l,
		private IAuthTokenProvider $tokenProvider,
		private IUserManager $userManager,
		private ICrypto $crypto,
	) {
		parent::__construct($appName, $request);
	}

	public function addClient(string $name,
		string $redirectUri): JSONResponse {
		if (filter_var($redirectUri, FILTER_VALIDATE_URL) === false) {
			return new JSONResponse(['message' => $this->l->t('Your redirect URL needs to be a full URL for example: https://yourdomain.com/path')], Http::STATUS_BAD_REQUEST);
		}

		$client = new Client();
		$client->setName($name);
		$client->setRedirectUri($redirectUri);
		$secret = $this->secureRandom->generate(64, self::validChars);
		$hashedSecret = bin2hex($this->crypto->calculateHMAC($secret));
		$client->setSecret($hashedSecret);
		$client->setClientIdentifier($this->secureRandom->generate(64, self::validChars));
		$client = $this->clientMapper->insert($client);

		$result = [
			'id' => $client->getId(),
			'name' => $client->getName(),
			'redirectUri' => $client->getRedirectUri(),
			'clientId' => $client->getClientIdentifier(),
			'clientSecret' => $secret,
		];

		return new JSONResponse($result);
	}

	public function deleteClient(int $id): JSONResponse {
		$client = $this->clientMapper->getByUid($id);

		$this->userManager->callForSeenUsers(function (IUser $user) use ($client): void {
			$this->tokenProvider->invalidateTokensOfUser($user->getUID(), $client->getName());
		});

		$this->accessTokenMapper->deleteByClientId($id);
		$this->clientMapper->delete($client);
		return new JSONResponse([]);
	}
}

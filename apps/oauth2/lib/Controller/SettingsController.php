<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Patrik Kernstock <info@pkern.at>
 * @author rakekniven <mark.ziegler@rakekniven.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
		private ICrypto $crypto
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

		$this->userManager->callForAllUsers(function (IUser $user) use ($client) {
			$this->tokenProvider->invalidateTokensOfUser($user->getUID(), $client->getName());
		});

		$this->accessTokenMapper->deleteByClientId($id);
		$this->clientMapper->delete($client);
		return new JSONResponse([]);
	}
}

<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\OAuth2\Controller;

use OC\Security\Bruteforce\Throttler;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Exceptions\AccessTokenNotFoundException;
use OCA\OAuth2\Exceptions\ClientNotFoundException;
use OCA\OAuth2\Exceptions\RefreshFailedException;
use OCA\OAuth2\Service\ClientService;
use OCA\OAuth2\Service\TokenService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class OauthApiController extends Controller {
	/** @var AccessTokenMapper */
	private $accessTokenMapper;
	/** @var Throttler */
	private $throttler;
	/** @var ClientService */
	private $clientService;
	/** @var TokenService */
	private $tokenService;

	public function __construct(string $appName,
								IRequest $request,
								AccessTokenMapper $accessTokenMapper,
								ClientService $clientService,
								TokenService $tokenService,
								Throttler $throttler) {
		parent::__construct($appName, $request);
		$this->accessTokenMapper = $accessTokenMapper;
		$this->throttler = $throttler;
		$this->clientService = $clientService;
		$this->tokenService = $tokenService;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $grant_type
	 * @param string $code
	 * @param string $refresh_token
	 * @param string $client_id
	 * @param string $client_secret
	 * @return JSONResponse
	 */
	public function getToken($grant_type, $code, $refresh_token, $client_id, $client_secret): JSONResponse {

		// We only handle two types
		if ($grant_type !== 'authorization_code' && $grant_type !== 'refresh_token') {
			return new JSONResponse([
				'error' => 'invalid_grant',
			], Http::STATUS_BAD_REQUEST);
		}

		// We handle the initial and refresh tokens the same way
		if ($grant_type === 'refresh_token' ) {
			$code = $refresh_token;
		}

		if (isset($this->request->server['PHP_AUTH_USER'])) {
			$client_id = $this->request->server['PHP_AUTH_USER'];
			$client_secret = $this->request->server['PHP_AUTH_PW'];
		}

		try {
			$client = $this->clientService->getClient($client_id, $client_secret);
		} catch (ClientNotFoundException $e) {
			return new JSONResponse([
				'error' => 'invalid_request',
			], Http::STATUS_BAD_REQUEST);
		}

		try {
			$accessToken = $this->accessTokenMapper->getByCode($code);
		} catch (AccessTokenNotFoundException $e) {
			return new JSONResponse([
				'error' => 'invalid_request',
			], Http::STATUS_BAD_REQUEST);
		}

		// The client must match the client of the access token
		if ($client->getId() !== $accessToken->getClientId()) {
			return new JSONResponse([
				'error' => 'invalid_client',
			], Http::STATUS_BAD_REQUEST);
		}

		try {
			$result = $this->tokenService->refreshToken($accessToken, $code);
		} catch (RefreshFailedException $e) {
			return new JSONResponse([
				'error' => 'invalid_request',
			], Http::STATUS_BAD_REQUEST);
		}

		$this->throttler->resetDelay($this->request->getRemoteAddress(), 'login', ['user' => $result->getUid()]);

		return new JSONResponse(
			[
				'access_token' => $result->getNewToken(),
				'token_type' => 'Bearer',
				'expires_in' => 3600,
				'refresh_token' => $result->getNewCode(),
				'user_id' => $result->getUid(),
			]
		);
	}
}

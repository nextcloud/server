<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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

use OC\Authentication\Token\IProvider as TokenProvider;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Exceptions\AccessTokenNotFoundException;
use OCA\OAuth2\Exceptions\ClientNotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Exceptions\ExpiredTokenException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\DB\Exception;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class OauthApiController extends Controller {
	// the authorization code expires after 10 minutes
	public const AUTHORIZATION_CODE_EXPIRES_AFTER = 10 * 60;

	public function __construct(
		string $appName,
		IRequest $request,
		private ICrypto $crypto,
		private AccessTokenMapper $accessTokenMapper,
		private ClientMapper $clientMapper,
		private TokenProvider $tokenProvider,
		private ISecureRandom $secureRandom,
		private ITimeFactory $time,
		private LoggerInterface $logger,
		private IThrottler $throttler,
		private ITimeFactory $timeFactory,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @BruteForceProtection(action=oauth2GetToken)
	 *
	 * Get a token
	 *
	 * @param string $grant_type Token type that should be granted
	 * @param ?string $code Code of the flow
	 * @param ?string $refresh_token Refresh token
	 * @param ?string $client_id Client ID
	 * @param ?string $client_secret Client secret
	 * @throws Exception
	 * @return JSONResponse<Http::STATUS_OK, array{access_token: string, token_type: string, expires_in: int, refresh_token: string, user_id: string}, array{}>|JSONResponse<Http::STATUS_BAD_REQUEST, array{error: string}, array{}>
	 *
	 * 200: Token returned
	 * 400: Getting token is not possible
	 */
	public function getToken(
		string $grant_type, ?string $code, ?string $refresh_token,
		?string $client_id, ?string $client_secret
	): JSONResponse {

		// We only handle two types
		if ($grant_type !== 'authorization_code' && $grant_type !== 'refresh_token') {
			$response = new JSONResponse([
				'error' => 'invalid_grant',
			], Http::STATUS_BAD_REQUEST);
			$response->throttle(['invalid_grant' => $grant_type]);
			return $response;
		}

		// We handle the initial and refresh tokens the same way
		if ($grant_type === 'refresh_token') {
			$code = $refresh_token;
		}

		try {
			$accessToken = $this->accessTokenMapper->getByCode($code);
		} catch (AccessTokenNotFoundException $e) {
			$response = new JSONResponse([
				'error' => 'invalid_request',
			], Http::STATUS_BAD_REQUEST);
			$response->throttle(['invalid_request' => 'token not found', 'code' => $code]);
			return $response;
		}

		if ($grant_type === 'authorization_code') {
			// check this token is in authorization code state
			$deliveredTokenCount = $accessToken->getTokenCount();
			if ($deliveredTokenCount > 0) {
				$response = new JSONResponse([
					'error' => 'invalid_request',
				], Http::STATUS_BAD_REQUEST);
				$response->throttle(['invalid_request' => 'authorization_code_received_for_active_token']);
				return $response;
			}

			// check authorization code expiration
			$now = $this->timeFactory->now()->getTimestamp();
			$codeCreatedAt = $accessToken->getCodeCreatedAt();
			if ($codeCreatedAt < $now - self::AUTHORIZATION_CODE_EXPIRES_AFTER) {
				// we know this token is not useful anymore
				$this->accessTokenMapper->delete($accessToken);

				$response = new JSONResponse([
					'error' => 'invalid_request',
				], Http::STATUS_BAD_REQUEST);
				$expiredSince = $now - self::AUTHORIZATION_CODE_EXPIRES_AFTER - $codeCreatedAt;
				$response->throttle(['invalid_request' => 'authorization_code_expired', 'expired_since' => $expiredSince]);
				return $response;
			}
		}

		try {
			$client = $this->clientMapper->getByUid($accessToken->getClientId());
		} catch (ClientNotFoundException $e) {
			$response = new JSONResponse([
				'error' => 'invalid_request',
			], Http::STATUS_BAD_REQUEST);
			$response->throttle(['invalid_request' => 'client not found', 'client_id' => $accessToken->getClientId()]);
			return $response;
		}

		if (isset($this->request->server['PHP_AUTH_USER'])) {
			$client_id = $this->request->server['PHP_AUTH_USER'];
			$client_secret = $this->request->server['PHP_AUTH_PW'];
		}

		try {
			$storedClientSecretHash = $client->getSecret();
			$clientSecretHash = bin2hex($this->crypto->calculateHMAC($client_secret));
		} catch (\Exception $e) {
			$this->logger->error('OAuth client secret decryption error', ['exception' => $e]);
			// we don't throttle here because it might not be a bruteforce attack
			return new JSONResponse([
				'error' => 'invalid_client',
			], Http::STATUS_BAD_REQUEST);
		}
		// The client id and secret must match. Else we don't provide an access token!
		if ($client->getClientIdentifier() !== $client_id || $storedClientSecretHash !== $clientSecretHash) {
			$response = new JSONResponse([
				'error' => 'invalid_client',
			], Http::STATUS_BAD_REQUEST);
			$response->throttle(['invalid_client' => 'client ID or secret does not match']);
			return $response;
		}

		$decryptedToken = $this->crypto->decrypt($accessToken->getEncryptedToken(), $code);

		// Obtain the appToken associated
		try {
			$appToken = $this->tokenProvider->getTokenById($accessToken->getTokenId());
		} catch (ExpiredTokenException $e) {
			$appToken = $e->getToken();
		} catch (InvalidTokenException $e) {
			//We can't do anything...
			$this->accessTokenMapper->delete($accessToken);
			$response = new JSONResponse([
				'error' => 'invalid_request',
			], Http::STATUS_BAD_REQUEST);
			$response->throttle(['invalid_request' => 'token is invalid']);
			return $response;
		}

		// Rotate the apptoken (so the old one becomes invalid basically)
		$newToken = $this->secureRandom->generate(72, ISecureRandom::CHAR_ALPHANUMERIC);

		$appToken = $this->tokenProvider->rotate(
			$appToken,
			$decryptedToken,
			$newToken
		);

		// Expiration is in 1 hour again
		$appToken->setExpires($this->time->getTime() + 3600);
		$this->tokenProvider->updateToken($appToken);

		// Generate a new refresh token and encrypt the new apptoken in the DB
		$newCode = $this->secureRandom->generate(128, ISecureRandom::CHAR_ALPHANUMERIC);
		$accessToken->setHashedCode(hash('sha512', $newCode));
		$accessToken->setEncryptedToken($this->crypto->encrypt($newToken, $newCode));
		// increase the number of delivered oauth token
		// this helps with cleaning up DB access token when authorization code has expired
		// and it never delivered any oauth token
		$tokenCount = $accessToken->getTokenCount();
		$accessToken->setTokenCount($tokenCount + 1);
		$this->accessTokenMapper->update($accessToken);

		$this->throttler->resetDelay($this->request->getRemoteAddress(), 'login', ['user' => $appToken->getUID()]);

		return new JSONResponse(
			[
				'access_token' => $newToken,
				'token_type' => 'Bearer',
				'expires_in' => 3600,
				'refresh_token' => $newCode,
				'user_id' => $appToken->getUID(),
			]
		);
	}
}

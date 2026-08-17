<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Controller;

use OC\Authentication\Token\IProvider as TokenProvider;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Exceptions\AccessTokenNotFoundException;
use OCA\OAuth2\Exceptions\ClientNotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Exceptions\ExpiredTokenException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Token\IToken;
use OCP\DB\Exception;
use OCP\GlobalScale\IConfig as GlobalScaleConfig;
use OCP\GlobalScale\IGlobalScaleService;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
class OauthApiController extends Controller {
	// the authorization code expires after 10 minutes
	public const AUTHORIZATION_CODE_EXPIRES_AFTER = 10 * 60;
	private const PKCE_STRING_PATTERN = '/^[A-Za-z0-9._~-]{43,128}$/';

	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ICrypto $crypto,
		private readonly AccessTokenMapper $accessTokenMapper,
		private readonly ClientMapper $clientMapper,
		private readonly TokenProvider $tokenProvider,
		private readonly ISecureRandom $secureRandom,
		private readonly ITimeFactory $time,
		private readonly LoggerInterface $logger,
		private readonly IThrottler $throttler,
		private readonly ITimeFactory $timeFactory,
		private readonly IDBConnection $db,
		private readonly GlobalScaleConfig $globalScaleConfig,
		private readonly IUserManager $userManager,
		private readonly IURLGenerator $urlGenerator,
		private readonly ContainerInterface $container,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get a token
	 *
	 * @param 'authorization_code'|'refresh_token' $grant_type Token type that should be granted
	 * @param ?string $code Code of the flow
	 * @param ?string $refresh_token Refresh token
	 * @param ?string $client_id Client ID
	 * @param ?string $client_secret Client secret
	 * @param ?string $code_verifier PKCE code verifier in RFC 7636 format (required if code_challenge was provided)
	 * @throws Exception
	 * @return JSONResponse<Http::STATUS_OK, array{access_token: string, token_type: string, expires_in: int, refresh_token: string, user_id: string, "x.nc-gss.secondary_url"?: ?string}, array{}>|JSONResponse<Http::STATUS_BAD_REQUEST, array{error: string}, array{}>
	 *
	 * 200: Token returned
	 * 400: Getting token is not possible
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'oauth2GetToken')]
	public function getToken(
		string $grant_type, ?string $code, ?string $refresh_token,
		?string $client_id, ?string $client_secret,
		?string $code_verifier = null,
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
			$response->throttle(['invalid_request' => 'token not found']);
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

			// verify PKCE code_verifier if code_challenge was provided
			$hashedCodeChallenge = $accessToken->getHashedCodeChallenge();
			if ($hashedCodeChallenge !== null && $hashedCodeChallenge !== '') {
				if ($code_verifier === null || $code_verifier === '') {
					$response = new JSONResponse([
						'error' => 'invalid_request',
					], Http::STATUS_BAD_REQUEST);
					$response->throttle(['invalid_request' => 'code_verifier_required']);
					return $response;
				}

				if (preg_match(self::PKCE_STRING_PATTERN, $code_verifier) !== 1) {
					$response = new JSONResponse([
						'error' => 'invalid_grant',
					], Http::STATUS_BAD_REQUEST);
					$response->throttle(['invalid_grant' => 'invalid_code_verifier']);
					return $response;
				}

				$codeChallengeMethod = $accessToken->getCodeChallengeMethod();
				if ($codeChallengeMethod !== 'S256') {
					$response = new JSONResponse([
						'error' => 'invalid_grant',
					], Http::STATUS_BAD_REQUEST);
					$response->throttle(['invalid_grant' => 'unsupported_code_challenge_method']);
					return $response;
				}

				$expectedHash = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');
				if (!hash_equals($hashedCodeChallenge, $expectedHash)) {
					$response = new JSONResponse([
						'error' => 'invalid_grant',
					], Http::STATUS_BAD_REQUEST);
					$response->throttle(['invalid_grant' => 'pkce_verification_failed']);
					return $response;
				}
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
		$newCode = $this->secureRandom->generate(128, ISecureRandom::CHAR_ALPHANUMERIC);
		$newEncryptedToken = $this->crypto->encrypt($newToken, $newCode);
		$redeemedThrottleReason = $grant_type === 'authorization_code'
			? 'authorization_code_already_redeemed'
			: 'refresh_token_already_redeemed';

		$this->db->beginTransaction();
		try {
			$updatedRows = $this->accessTokenMapper->rotateToken(
				$accessToken->getId(),
				$code,
				$newCode,
				$newEncryptedToken,
				$grant_type === 'authorization_code',
			);

			if ($updatedRows !== 1) {
				$this->db->rollBack();
				$response = new JSONResponse([
					'error' => 'invalid_request',
				], Http::STATUS_BAD_REQUEST);
				$response->throttle(['invalid_request' => $redeemedThrottleReason]);
				return $response;
			}

			$appToken = $this->tokenProvider->rotate(
				$appToken,
				$decryptedToken,
				$newToken
			);

			// Expiration is in 1 hour again
			$expires = $this->time->getTime() + 3600;
			$appToken->setExpires($expires);
			$this->tokenProvider->updateToken($appToken);

			$this->db->commit();
		} catch (\Throwable $e) {
			if ($this->db->inTransaction()) {
				$this->db->rollBack();
			}
			// rotate() and updateToken() write the auth token to the cache,
			// so if we are past rotate() we must invalidate the new token
			$this->tokenProvider->invalidateToken($newToken);

			throw $e;
		}

		$this->throttler->resetDelay($this->request->getRemoteAddress(), 'login', ['user' => $appToken->getUID()]);

		$data = [
			'access_token' => $newToken,
			'token_type' => 'Bearer',
			'expires_in' => 3600,
			'refresh_token' => $newCode,
			'user_id' => $appToken->getUID(),
		];

		if ($this->globalScaleConfig->isGlobalScaleEnabled() && $this->globalScaleConfig->isPrimary()) {
			// Also make sure the access token is available on the secondary instance
			$data['x.nc-gss.secondary_url'] = $this->pushTokenToSecondary($appToken, $newToken, $expires);
		}

		return new JSONResponse($data);
	}

	/**
	 * Push the freshly issued app token to the secondary instance holding the
	 * user's account, so the OAuth client can use it there directly.
	 */
	private function pushTokenToSecondary(IToken $appToken, string $newToken, ?int $expires): ?string {
		$user = $this->userManager->get($appToken->getUID());
		if ($user === null) {
			$this->logger->warning('could not push oauth token to secondary: unknown user', ['uid' => $appToken->getUID()]);
			return null;
		}

		try {
			/** @var IGlobalScaleService $globalScaleService */
			$globalScaleService = $this->container->get(IGlobalScaleService::class);
		} catch (ContainerExceptionInterface $e) {
			$this->logger->warning('could not push oauth token to secondary: globalsiteselector is not available', ['exception' => $e]);
			return null;
		}

		try {
			return $globalScaleService->sendToSecondary($user, $this->urlGenerator->linkToRoute('oauth2.OauthApi.pushToken'), [
				'uid' => $appToken->getUID(),
				'loginName' => $appToken->getLoginName(),
				'name' => $appToken->getName(),
				'type' => $appToken->getType(),
				'remember' => $appToken->getRemember(),
				'scope' => $appToken->getScopeAsArray(),
				'expires' => $expires,
				'token' => $newToken,
			]);
		} catch (\Exception $e) {
			$this->logger->warning('could not push oauth token to secondary', ['exception' => $e]);
		}
		return null;
	}

	/**
	 * Receive an app token pushed from the primary instance, so it can be used
	 * directly against this (secondary) instance.
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[BruteForceProtection(action: 'oauth2PushToken')]
	public function pushToken(string $jwt): JSONResponse {
		if (!$this->globalScaleConfig->isGlobalScaleEnabled() || !$this->globalScaleConfig->isSecondary() || $jwt === '') {
			$response = new JSONResponse([], Http::STATUS_BAD_REQUEST);
			$response->throttle();
			return $response;
		}

		try {
			/** @var IGlobalScaleService $globalScaleService */
			$globalScaleService = $this->container->get(IGlobalScaleService::class);
		} catch (ContainerExceptionInterface $e) {
			$this->logger->warning('could not receive oauth token from primary: globalsiteselector is not available', ['exception' => $e]);
			$response = new JSONResponse([], Http::STATUS_BAD_REQUEST);
			$response->throttle();
			return $response;
		}

		try {
			$decoded = $globalScaleService->decodePayload($jwt);

			$uid = (string)$decoded['uid'];
			if (!$this->userManager->userExists($uid)) {
				throw new \InvalidArgumentException('unknown user: ' . $uid);
			}

			$this->tokenProvider->generateToken(
				(string)$decoded['token'],
				$uid,
				(string)$decoded['loginName'],
				null,
				(string)$decoded['name'],
				(int)$decoded['type'],
				(int)$decoded['remember'],
				(array)$decoded['scope'],
				$decoded['expires'] !== null ? (int)$decoded['expires'] : null,
			);
		} catch (\Exception $e) {
			$this->logger->warning('could not create pushed oauth token', ['exception' => $e]);
			$response = new JSONResponse([], Http::STATUS_BAD_REQUEST);
			$response->throttle();
			return $response;
		}

		return new JSONResponse([]);
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Controller;

use Firebase\JWT\JWT;
use OC\Authentication\Token\IProvider;
use OC\OCM\OCMSignatoryManager;
use OC\Security\Signature\Model\IncomingSignedRequest;
use OCA\DAV\Db\OcmTokenMap;
use OCA\DAV\Db\OcmTokenMapMapper;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Exceptions\ExpiredTokenException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Token\IToken;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\Exceptions\SignatoryNotFoundException;
use OCP\Security\Signature\Exceptions\SignatureException;
use OCP\Security\Signature\Exceptions\SignatureNotFoundException;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Security\Signature\ISignatureManager;
use OCP\Security\Signature\Model\Signatory;
use OCP\Share\IManager as IShareManager;
use Psr\Log\LoggerInterface;

/**
 * Controller for the /token endpoint
 * Exchanges long-lived refresh tokens for short-lived access tokens
 *
 * @since 32.0.0
 */
class TokenController extends ApiController {
	public function __construct(
		IRequest $request,
		private readonly IProvider $tokenProvider,
		private readonly ISecureRandom $random,
		private readonly ITimeFactory $timeFactory,
		private readonly LoggerInterface $logger,
		private readonly ISignatureManager $signatureManager,
		private readonly OCMSignatoryManager $signatoryManager,
		private readonly IAppConfig $appConfig,
		private readonly OcmTokenMapMapper $ocmTokenMapMapper,
		private readonly IShareManager $shareManager,
	) {
		parent::__construct('dav', $request);
	}

	/**
	 * Verify the signature of incoming request if available
	 *
	 * @return IncomingSignedRequest|null null if remote does not support signed requests
	 * @throws IncomingRequestException if signature is required but invalid
	 */
	private function verifySignedRequest(): ?IncomingSignedRequest {
		try {
			$signedRequest = $this->signatureManager->getIncomingSignedRequest($this->signatoryManager);
			$this->logger->debug('Token request signature verified', [
				'origin' => $signedRequest->getOrigin()
			]);
			return $signedRequest;
		} catch (SignatureNotFoundException|SignatoryNotFoundException $e) {
			$this->logger->debug('Token request not signed', ['exception' => $e]);

			if ($this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, lazy: true)) {
				$this->logger->notice('Rejected unsigned token request', ['exception' => $e]);
				throw new IncomingRequestException('Unsigned request not allowed');
			}
			return null;
		} catch (SignatureException $e) {
			$this->logger->warning('Invalid token request signature', ['exception' => $e]);
			throw new IncomingRequestException('Invalid signature');
		}
	}

	/**
	 * @return array{0: string, 1: string} [JWS algorithm, key material accepted by firebase/php-jwt]
	 * @throws \RuntimeException if the key cannot be parsed or its type is unsupported
	 */
	private function resolveJwtSigningKey(string $privateKeyPem): array {
		$key = openssl_pkey_get_private($privateKeyPem);
		if ($key === false) {
			throw new \RuntimeException('Cannot parse signatory private key');
		}
		$details = openssl_pkey_get_details($key);

		if (isset($details['rsa'])) {
			$algorithm = $details['bits'] >= 4096 ? 'RS512' : 'RS256';
			return [$algorithm, $privateKeyPem];
		}
		if (isset($details['ed25519']['priv_key'])) {
			$secretKey = sodium_crypto_sign_secretkey(sodium_crypto_sign_seed_keypair($details['ed25519']['priv_key']));
			return ['EdDSA', base64_encode($secretKey)];
		}

		throw new \RuntimeException('Unsupported signatory key type for JWT access token');
	}

	/**
	 * Exchange a refresh token for a short-lived access token
	 *
	 * @return DataResponse<Http::STATUS_OK, array{access_token: string, token_type: string, expires_in: int}, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED|Http::STATUS_BAD_REQUEST, array{error: string}, array{}>
	 *
	 * 200: Access token successfully generated
	 * 400: Bad request - missing refresh token or invalid request format
	 * 401: Unauthorized - invalid or expired refresh token, or invalid signature
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'POST', url: '/api/v1/access-token')]
	public function accessToken(): DataResponse {
		try {
			$signedRequest = $this->verifySignedRequest();
		} catch (IncomingRequestException $e) {
			$this->logger->warning('Token request signature verification failed', [
				'exception' => $e
			]);
			return new DataResponse(
				['error' => 'invalid_request'],
				Http::STATUS_UNAUTHORIZED
			);
		}

		$body = file_get_contents('php://input');
		parse_str($body, $data);

		$refreshToken = $data['code'] ?? '';
		$grantType = $data['grant_type'] ?? '';

		if ($grantType !== 'authorization_code') {
			return new DataResponse(
				['error' => 'unsupported_grant_type'],
				Http::STATUS_BAD_REQUEST
			);
		}

		if (empty($refreshToken)) {
			return new DataResponse(
				['error' => 'refresh_token is required'],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$token = $this->tokenProvider->getToken($refreshToken);

			if ($token->getType() !== IToken::PERMANENT_TOKEN) {
				$this->logger->warning('Attempted to use non-permanent token as refresh token', [
					'tokenId' => $token->getId(),
				]);
				return new DataResponse(
					['error' => 'invalid_grant'],
					Http::STATUS_UNAUTHORIZED
				);
			}

			// Revoke the previous access token for this refresh token, if any.
			$existingMapping = $this->ocmTokenMapMapper->findByRefreshToken($refreshToken);
			if ($existingMapping !== null) {
				try {
					$this->tokenProvider->invalidateTokenById(
						$token->getUID(),
						$existingMapping->getAccessTokenId()
					);
				} catch (\Exception) {
					// Token may already be gone; ignore.
				}
				$this->ocmTokenMapMapper->delete($existingMapping);
			}

			$share = $this->shareManager->getShareByToken($refreshToken);
			$expiresIn = 3600; // 1 hour in seconds
			$issuedAt = $this->timeFactory->getTime();
			$expiresAt = $issuedAt + $expiresIn;

			$signatory = $this->signatoryManager->getLocalSignatory();
			$keyId = $signatory->getKeyId();
			$issuer = parse_url($keyId, PHP_URL_SCHEME) . '://' . Signatory::extractIdentityFromUri($keyId);

			[$jwtAlgorithm, $jwtKey] = $this->resolveJwtSigningKey($signatory->getPrivateKey());

			$payload = [
				'iss' => $issuer,
				'sub' => $share->getShareOwner(),
				'aud' => $share->getSharedWith(),
				'client_id' => (string)$token->getId(),
				'iat' => $issuedAt,
				'exp' => $expiresAt,
				'jti' => $this->random->generate(16, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_DIGITS),
			];

			$accessTokenString = JWT::encode($payload, $jwtKey, $jwtAlgorithm, $keyId, ['typ' => 'at+jwt']);

			$accessToken = $this->tokenProvider->generateToken(
				$accessTokenString,
				$token->getUID(),
				$token->getLoginName(),
				null, // No password for access tokens
				IToken::OCM_ACCESS_TOKEN_NAME,
				IToken::TEMPORARY_TOKEN,
				IToken::DO_NOT_REMEMBER
			);

			$accessToken->setExpires($expiresAt);
			$this->tokenProvider->updateToken($accessToken);

			$mapping = new OcmTokenMap();
			$mapping->setAccessTokenId($accessToken->getId());
			$mapping->setRefreshToken($refreshToken);
			$mapping->setExpires($expiresAt);
			$this->ocmTokenMapMapper->insert($mapping);

			return new DataResponse([
				'access_token' => $accessTokenString,
				'token_type' => 'Bearer',
				'expires_in' => $expiresIn,
			], Http::STATUS_OK);
		} catch (InvalidTokenException $e) {
			$this->logger->info('Invalid refresh token provided', [
				'exception' => $e,
			]);
			return new DataResponse(
				['error' => 'invalid_grant'],
				Http::STATUS_UNAUTHORIZED
			);
		} catch (ExpiredTokenException $e) {
			$this->logger->info('Expired refresh token provided', [
				'exception' => $e,
			]);
			return new DataResponse(
				['error' => 'invalid_grant'],
				Http::STATUS_UNAUTHORIZED
			);
		} catch (\Exception $e) {
			$this->logger->error('Error generating access token', [
				'exception' => $e,
			]);
			return new DataResponse(
				['error' => 'server_error'],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Controller;

use Firebase\JWT\JWT;
use OC\OCM\OCMSignatoryManager;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Federation\ICloudIdManager;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use OCP\Security\Signature\Exceptions\IdentityNotFoundException;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\Exceptions\SignatoryNotFoundException;
use OCP\Security\Signature\Exceptions\SignatureException;
use OCP\Security\Signature\Exceptions\SignatureNotFoundException;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Security\Signature\ISignatureManager;
use OCP\Security\Signature\Model\Signatory;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

/**
 * Controller for the /token endpoint
 * Exchanges long-lived refresh tokens for short-lived access tokens
 */
class TokenController extends ApiController {
	public function __construct(
		IRequest $request,
		private readonly ISecureRandom $random,
		private readonly ITimeFactory $timeFactory,
		private readonly LoggerInterface $logger,
		private readonly ISignatureManager $signatureManager,
		private readonly OCMSignatoryManager $signatoryManager,
		private readonly IAppConfig $appConfig,
		private readonly IShareManager $shareManager,
		private readonly ICloudIdManager $cloudIdManager,
	) {
		parent::__construct('cloud_federation_api', $request);
	}

	/**
	 * Resolve the signer origin from the refresh token's share, or null.
	 *
	 * @param string $code refresh token
	 * @return string|null signer origin, or null if it cannot be determined
	 */
	private function resolveOriginFromRefreshToken(string $code): ?string {
		if ($code === '') {
			return null;
		}
		try {
			$share = $this->shareManager->getShareByToken($code);
			$sharedWith = $share->getSharedWith();
			if ($sharedWith === null || $sharedWith === '') {
				return null;
			}
			$remote = $this->cloudIdManager->resolveCloudId($sharedWith)->getRemote();
			return $this->signatureManager->extractIdentityFromUri($remote);
		} catch (ShareNotFound|IdentityNotFoundException|\InvalidArgumentException) {
			return null;
		}
	}

	/**
	 * Verify the signature of incoming request if available
	 *
	 * @param string|null $origin sender origin, or null if unknown
	 *
	 * @return IIncomingSignedRequest|null null if remote does not support signed requests
	 * @throws IncomingRequestException if signature is required but invalid
	 */
	private function verifySignedRequest(?string $origin): ?IIncomingSignedRequest {
		try {
			$signedRequest = $this->signatureManager->getIncomingSignedRequest($this->signatoryManager, null, $origin);
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
		if (isset($details['ec'])) {
			$algorithm = match ($details['ec']['curve_name'] ?? '') {
				'prime256v1' => 'ES256',
				'secp384r1' => 'ES384',
				default => throw new \RuntimeException('Unsupported EC curve for JWT access token: ' . ($details['ec']['curve_name'] ?? 'unknown')),
			};
			return [$algorithm, $privateKeyPem];
		}

		throw new \RuntimeException('Unsupported signatory key type for JWT access token');
	}

	/**
	 * Serve the local JWK Set
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{keys: list<array<string, string>>}, array{}>
	 *
	 * 200: JWK Set returned
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function jwks(): JSONResponse {
		$keys = [];
		try {
			$keys = $this->signatoryManager->getLocalJwks();
		} catch (\Throwable $e) {
			$this->logger->warning('failed to build local JWKs', ['exception' => $e]);
		}
		return new JSONResponse(['keys' => $keys]);
	}

	/**
	 * Exchange a refresh token for a short-lived access token
	 *
	 * @param string $grant_type OAuth grant type, must be `authorization_code`
	 * @param string $code The refresh token to exchange for an access token
	 * @return DataResponse<Http::STATUS_OK, array{access_token: string, token_type: string, expires_in: int}, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED|Http::STATUS_BAD_REQUEST|Http::STATUS_INTERNAL_SERVER_ERROR, array{error: string}, array{}>
	 *
	 * 200: Access token successfully generated
	 * 400: Bad request - missing refresh token or invalid request format
	 * 401: Unauthorized - invalid or expired refresh token, or invalid signature
	 * 500: Internal server error
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'POST', url: '/api/v1/access-token')]
	public function accessToken(string $grant_type = '', string $code = ''): DataResponse {
		try {
			$signedRequest = $this->verifySignedRequest($this->resolveOriginFromRefreshToken($code));
		} catch (IncomingRequestException $e) {
			$this->logger->warning('Token request signature verification failed', [
				'exception' => $e
			]);
			return new DataResponse(
				['error' => 'invalid_request'],
				Http::STATUS_UNAUTHORIZED
			);
		}

		if ($grant_type !== 'authorization_code') {
			return new DataResponse(
				['error' => 'unsupported_grant_type'],
				Http::STATUS_BAD_REQUEST
			);
		}

		if ($code === '') {
			return new DataResponse(
				['error' => 'refresh_token is required'],
				Http::STATUS_BAD_REQUEST
			);
		}

		$refreshToken = $code;

		try {
			$share = $this->shareManager->getShareByToken($refreshToken);
			if (!in_array($share->getShareType(), [IShare::TYPE_REMOTE, IShare::TYPE_REMOTE_GROUP], true)
				|| $share->getToken() === null
				|| !hash_equals($share->getToken(), $refreshToken)
			) {
				return new DataResponse(
					['error' => 'invalid_grant'],
					Http::STATUS_UNAUTHORIZED
				);
			}

			$expiresIn = 3600;
			$issuedAt = $this->timeFactory->getTime();
			$expiresAt = $issuedAt + $expiresIn;

			$signatory = $this->signatoryManager->getLocalJwksSignatory();
			if ($signatory === null) {
				throw new \RuntimeException('No JWKS-published OCM signatory available to sign the access token');
			}
			$keyId = $signatory->getKeyId();
			$issuer = parse_url($keyId, PHP_URL_SCHEME) . '://' . Signatory::extractIdentityFromUri($keyId);

			[$jwtAlgorithm, $jwtKey] = $this->resolveJwtSigningKey($signatory->getPrivateKey());

			$payload = [
				'iss' => $issuer,
				'sub' => $share->getShareOwner(),
				'aud' => $share->getSharedWith(),
				'client_id' => $share->getId(),
				'iat' => $issuedAt,
				'exp' => $expiresAt,
				'jti' => $this->random->generate(16, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_DIGITS),
			];

			$accessTokenString = JWT::encode($payload, $jwtKey, $jwtAlgorithm, $keyId, ['typ' => 'at+jwt']);

			return new DataResponse([
				'access_token' => $accessTokenString,
				'token_type' => 'Bearer',
				'expires_in' => $expiresIn,
			], Http::STATUS_OK);
		} catch (ShareNotFound $e) {
			$this->logger->info('Invalid refresh token provided', [
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

<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Controller;

use OC\Authentication\Token\IProvider;
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
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;
use OCP\IAppConfig;
use OC\OCM\OCMSignatoryManager;
use NCU\Security\Signature\ISignatureManager;
use NCU\Security\Signature\Exceptions\SignatureNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureException;
use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Model\IIncomingSignedRequest;
use NCU\Security\Signature\Exceptions\IncomingRequestException;
use OC\Security\Signature\Model\IncomingSignedRequest;

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
		$data = json_decode($body, true);

		if (!is_array($data)) {
			return new DataResponse(
				['error' => 'invalid_request'],
				Http::STATUS_BAD_REQUEST
			);
		}

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

			$accessTokenString = $this->random->generate(
				72,
				ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS
			);

			$expiresIn = 3600; // 1 hour in seconds
			$expiresAt = $this->timeFactory->getTime() + $expiresIn;

			$accessToken = $this->tokenProvider->generateToken(
				$accessTokenString,
				$refreshToken, // Keep refresh token with access token as UID
				$token->getLoginName(),
				null, // No password for access tokens
				'OCM Access Token',
				IToken::TEMPORARY_TOKEN,
				IToken::DO_NOT_REMEMBER
			);

			$accessToken->setExpires($expiresAt);
			$this->tokenProvider->updateToken($accessToken);

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

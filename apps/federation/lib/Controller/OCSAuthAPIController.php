<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Controller;

use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

/**
 * Class OCSAuthAPI
 *
 * OCS API end-points to exchange shared secret between two connected Nextclouds
 *
 * @package OCA\Federation\Controller
 */
#[OpenAPI(scope: OpenAPI::SCOPE_FEDERATION)]
class OCSAuthAPIController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ISecureRandom $secureRandom,
		private IJobList $jobList,
		private TrustedServers $trustedServers,
		private DbHandler $dbHandler,
		private LoggerInterface $logger,
		private ITimeFactory $timeFactory,
		private IThrottler $throttler,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Request received to ask remote server for a shared secret, for legacy end-points
	 *
	 * @param string $url URL of the server
	 * @param string $token Token of the server
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSForbiddenException Requesting shared secret is not allowed
	 *
	 * 200: Shared secret requested successfully
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[BruteForceProtection(action: 'federationSharedSecret')]
	public function requestSharedSecretLegacy(string $url, string $token): DataResponse {
		return $this->requestSharedSecret($url, $token);
	}


	/**
	 * Create shared secret and return it, for legacy end-points
	 *
	 * @param string $url URL of the server
	 * @param string $token Token of the server
	 * @return DataResponse<Http::STATUS_OK, array{sharedSecret: string}, array{}>
	 * @throws OCSForbiddenException Getting shared secret is not allowed
	 *
	 * 200: Shared secret returned
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[BruteForceProtection(action: 'federationSharedSecret')]
	public function getSharedSecretLegacy(string $url, string $token): DataResponse {
		return $this->getSharedSecret($url, $token);
	}

	/**
	 * Request received to ask remote server for a shared secret
	 *
	 * @param string $url URL of the server
	 * @param string $token Token of the server
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSForbiddenException Requesting shared secret is not allowed
	 *
	 * 200: Shared secret requested successfully
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[BruteForceProtection(action: 'federationSharedSecret')]
	public function requestSharedSecret(string $url, string $token): DataResponse {
		if ($this->trustedServers->isTrustedServer($url) === false) {
			$this->throttler->registerAttempt('federationSharedSecret', $this->request->getRemoteAddress());
			$this->logger->error('remote server not trusted (' . $url . ') while requesting shared secret');
			throw new OCSForbiddenException();
		}

		// if both server initiated the exchange of the shared secret the greater
		// token wins
		$localToken = $this->dbHandler->getToken($url);
		if (strcmp($localToken, $token) > 0) {
			$this->logger->info(
				'remote server (' . $url . ') presented lower token. We will initiate the exchange of the shared secret.'
			);
			throw new OCSForbiddenException();
		}

		$this->jobList->add(
			'OCA\Federation\BackgroundJob\GetSharedSecret',
			[
				'url' => $url,
				'token' => $token,
				'created' => $this->timeFactory->getTime()
			]
		);

		return new DataResponse();
	}

	/**
	 * Create shared secret and return it
	 *
	 * @param string $url URL of the server
	 * @param string $token Token of the server
	 * @return DataResponse<Http::STATUS_OK, array{sharedSecret: string}, array{}>
	 * @throws OCSForbiddenException Getting shared secret is not allowed
	 *
	 * 200: Shared secret returned
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[BruteForceProtection(action: 'federationSharedSecret')]
	public function getSharedSecret(string $url, string $token): DataResponse {
		if ($this->trustedServers->isTrustedServer($url) === false) {
			$this->throttler->registerAttempt('federationSharedSecret', $this->request->getRemoteAddress());
			$this->logger->error('remote server not trusted (' . $url . ') while getting shared secret');
			throw new OCSForbiddenException();
		}

		if ($this->isValidToken($url, $token) === false) {
			$this->throttler->registerAttempt('federationSharedSecret', $this->request->getRemoteAddress());
			$expectedToken = $this->dbHandler->getToken($url);
			$this->logger->error(
				'remote server (' . $url . ') didn\'t send a valid token (got "' . $token . '" but expected "' . $expectedToken . '") while getting shared secret'
			);
			throw new OCSForbiddenException();
		}

		$sharedSecret = $this->secureRandom->generate(32);

		$this->trustedServers->addSharedSecret($url, $sharedSecret);

		return new DataResponse([
			'sharedSecret' => $sharedSecret
		]);
	}

	protected function isValidToken(string $url, string $token): bool {
		$storedToken = $this->dbHandler->getToken($url);
		return hash_equals($storedToken, $token);
	}
}

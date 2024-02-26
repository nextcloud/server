<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Federation\Controller;

use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
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
	private ISecureRandom $secureRandom;
	private IJobList $jobList;
	private TrustedServers $trustedServers;
	private DbHandler $dbHandler;
	private LoggerInterface $logger;
	private ITimeFactory $timeFactory;
	private IThrottler $throttler;

	public function __construct(
		string $appName,
		IRequest $request,
		ISecureRandom $secureRandom,
		IJobList $jobList,
		TrustedServers $trustedServers,
		DbHandler $dbHandler,
		LoggerInterface $logger,
		ITimeFactory $timeFactory,
		IThrottler $throttler
	) {
		parent::__construct($appName, $request);

		$this->secureRandom = $secureRandom;
		$this->jobList = $jobList;
		$this->trustedServers = $trustedServers;
		$this->dbHandler = $dbHandler;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
		$this->throttler = $throttler;
	}

	/**
	 * Request received to ask remote server for a shared secret, for legacy end-points
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 * @BruteForceProtection(action=federationSharedSecret)
	 *
	 * @param string $url URL of the server
	 * @param string $token Token of the server
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSForbiddenException Requesting shared secret is not allowed
	 *
	 * 200: Shared secret requested successfully
	 */
	public function requestSharedSecretLegacy(string $url, string $token): DataResponse {
		return $this->requestSharedSecret($url, $token);
	}


	/**
	 * Create shared secret and return it, for legacy end-points
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 * @BruteForceProtection(action=federationSharedSecret)
	 *
	 * @param string $url URL of the server
	 * @param string $token Token of the server
	 * @return DataResponse<Http::STATUS_OK, array{sharedSecret: string}, array{}>
	 * @throws OCSForbiddenException Getting shared secret is not allowed
	 *
	 * 200: Shared secret returned
	 */
	public function getSharedSecretLegacy(string $url, string $token): DataResponse {
		return $this->getSharedSecret($url, $token);
	}

	/**
	 * Request received to ask remote server for a shared secret
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 * @BruteForceProtection(action=federationSharedSecret)
	 *
	 * @param string $url URL of the server
	 * @param string $token Token of the server
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSForbiddenException Requesting shared secret is not allowed
	 *
	 * 200: Shared secret requested successfully
	 */
	public function requestSharedSecret(string $url, string $token): DataResponse {
		if ($this->trustedServers->isTrustedServer($url) === false) {
			$this->throttler->registerAttempt('federationSharedSecret', $this->request->getRemoteAddress());
			$this->logger->error('remote server not trusted (' . $url . ') while requesting shared secret', ['app' => 'federation']);
			throw new OCSForbiddenException();
		}

		// if both server initiated the exchange of the shared secret the greater
		// token wins
		$localToken = $this->dbHandler->getToken($url);
		if (strcmp($localToken, $token) > 0) {
			$this->logger->info(
				'remote server (' . $url . ') presented lower token. We will initiate the exchange of the shared secret.',
				['app' => 'federation']
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
	 * @NoCSRFRequired
	 * @PublicPage
	 * @BruteForceProtection(action=federationSharedSecret)
	 *
	 * @param string $url URL of the server
	 * @param string $token Token of the server
	 * @return DataResponse<Http::STATUS_OK, array{sharedSecret: string}, array{}>
	 * @throws OCSForbiddenException Getting shared secret is not allowed
	 *
	 * 200: Shared secret returned
	 */
	public function getSharedSecret(string $url, string $token): DataResponse {
		if ($this->trustedServers->isTrustedServer($url) === false) {
			$this->throttler->registerAttempt('federationSharedSecret', $this->request->getRemoteAddress());
			$this->logger->error('remote server not trusted (' . $url . ') while getting shared secret', ['app' => 'federation']);
			throw new OCSForbiddenException();
		}

		if ($this->isValidToken($url, $token) === false) {
			$this->throttler->registerAttempt('federationSharedSecret', $this->request->getRemoteAddress());
			$expectedToken = $this->dbHandler->getToken($url);
			$this->logger->error(
				'remote server (' . $url . ') didn\'t send a valid token (got "' . $token . '" but expected "'. $expectedToken . '") while getting shared secret',
				['app' => 'federation']
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

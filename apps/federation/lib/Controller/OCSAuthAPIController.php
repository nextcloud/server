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
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

/**
 * Class OCSAuthAPI
 *
 * OCS API end-points to exchange shared secret between two connected Nextclouds
 *
 * @package OCA\Federation\Controller
 */
class OCSAuthAPIController extends OCSController {
	private ISecureRandom $secureRandom;
	private IJobList $jobList;
	private TrustedServers $trustedServers;
	private DbHandler $dbHandler;
	private LoggerInterface $logger;
	private ITimeFactory $timeFactory;

	public function __construct(
		string $appName,
		IRequest $request,
		ISecureRandom $secureRandom,
		IJobList $jobList,
		TrustedServers $trustedServers,
		DbHandler $dbHandler,
		LoggerInterface $logger,
		ITimeFactory $timeFactory
	) {
		parent::__construct($appName, $request);

		$this->secureRandom = $secureRandom;
		$this->jobList = $jobList;
		$this->trustedServers = $trustedServers;
		$this->dbHandler = $dbHandler;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * Request received to ask remote server for a shared secret, for legacy end-points
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 * @throws OCSForbiddenException
	 */
	public function requestSharedSecretLegacy(string $url, string $token): DataResponse {
		return $this->requestSharedSecret($url, $token);
	}


	/**
	 * Create shared secret and return it, for legacy end-points
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 * @throws OCSForbiddenException
	 */
	public function getSharedSecretLegacy(string $url, string $token): DataResponse {
		return $this->getSharedSecret($url, $token);
	}

	/**
	 * Request received to ask remote server for a shared secret
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 * @throws OCSForbiddenException
	 */
	public function requestSharedSecret(string $url, string $token): DataResponse {
		if ($this->trustedServers->isTrustedServer($url) === false) {
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
	 * @throws OCSForbiddenException
	 */
	public function getSharedSecret(string $url, string $token): DataResponse {
		if ($this->trustedServers->isTrustedServer($url) === false) {
			$this->logger->error('remote server not trusted (' . $url . ') while getting shared secret', ['app' => 'federation']);
			throw new OCSForbiddenException();
		}

		if ($this->isValidToken($url, $token) === false) {
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

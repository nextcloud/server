<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OCA\Federation\API;

use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http;
use OCP\BackgroundJob\IJobList;
use OCP\ILogger;
use OCP\IRequest;
use OCP\Security\ISecureRandom;

/**
 * Class OCSAuthAPI
 *
 * OCS API end-points to exchange shared secret between two connected ownClouds
 *
 * @package OCA\Federation\API
 */
class OCSAuthAPI {

	/** @var IRequest */
	private $request;

	/** @var ISecureRandom  */
	private $secureRandom;

	/** @var IJobList */
	private $jobList;

	/** @var TrustedServers */
	private $trustedServers;

	/** @var DbHandler */
	private $dbHandler;

	/** @var ILogger */
	private $logger;

	/**
	 * OCSAuthAPI constructor.
	 *
	 * @param IRequest $request
	 * @param ISecureRandom $secureRandom
	 * @param IJobList $jobList
	 * @param TrustedServers $trustedServers
	 * @param DbHandler $dbHandler
	 * @param ILogger $logger
	 */
	public function __construct(
		IRequest $request,
		ISecureRandom $secureRandom,
		IJobList $jobList,
		TrustedServers $trustedServers,
		DbHandler $dbHandler,
		ILogger $logger
	) {
		$this->request = $request;
		$this->secureRandom = $secureRandom;
		$this->jobList = $jobList;
		$this->trustedServers = $trustedServers;
		$this->dbHandler = $dbHandler;
		$this->logger = $logger;
	}

	/**
	 * request received to ask remote server for a shared secret
	 *
	 * @return \OC_OCS_Result
	 */
	public function requestSharedSecret() {

		$url = $this->request->getParam('url');
		$token = $this->request->getParam('token');

		if ($this->trustedServers->isTrustedServer($url) === false) {
			$this->logger->error('remote server not trusted (' . $url . ') while requesting shared secret', ['app' => 'federation']);
			return new \OC_OCS_Result(null, HTTP::STATUS_FORBIDDEN);
		}

		// if both server initiated the exchange of the shared secret the greater
		// token wins
		$localToken = $this->dbHandler->getToken($url);
		if (strcmp($localToken, $token) > 0) {
			$this->logger->info(
				'remote server (' . $url . ') presented lower token. We will initiate the exchange of the shared secret.',
				['app' => 'federation']
			);
			return new \OC_OCS_Result(null, HTTP::STATUS_FORBIDDEN);
		}

		// we ask for the shared secret so we no longer have to ask the other server
		// to request the shared secret
		$this->jobList->remove('OCA\Federation\BackgroundJob\RequestSharedSecret',
			[
				'url' => $url,
				'token' => $localToken
			]
		);

		$this->jobList->add(
			'OCA\Federation\BackgroundJob\GetSharedSecret',
			[
				'url' => $url,
				'token' => $token,
			]
		);

		return new \OC_OCS_Result(null, Http::STATUS_OK);

	}

	/**
	 * create shared secret and return it
	 *
	 * @return \OC_OCS_Result
	 */
	public function getSharedSecret() {

		$url = $this->request->getParam('url');
		$token = $this->request->getParam('token');

		if ($this->trustedServers->isTrustedServer($url) === false) {
			$this->logger->error('remote server not trusted (' . $url . ') while getting shared secret', ['app' => 'federation']);
			return new \OC_OCS_Result(null, HTTP::STATUS_FORBIDDEN);
		}

		if ($this->isValidToken($url, $token) === false) {
			$expectedToken = $this->dbHandler->getToken($url);
			$this->logger->error(
				'remote server (' . $url . ') didn\'t send a valid token (got "' . $token . '" but expected "'. $expectedToken . '") while getting shared secret',
				['app' => 'federation']
			);
			return new \OC_OCS_Result(null, HTTP::STATUS_FORBIDDEN);
		}

		$sharedSecret = $this->secureRandom->generate(32);

		$this->trustedServers->addSharedSecret($url, $sharedSecret);
		// reset token after the exchange of the shared secret was successful
		$this->dbHandler->addToken($url, '');

		return new \OC_OCS_Result(['sharedSecret' => $sharedSecret], Http::STATUS_OK);

	}

	protected function isValidToken($url, $token) {
		$storedToken = $this->dbHandler->getToken($url);
		return hash_equals($storedToken, $token);
	}

}

<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use OC\BackgroundJob\JobList;
use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use OCP\Security\StringUtils;

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

	/** @var JobList */
	private $jobList;

	/** @var TrustedServers */
	private $trustedServers;

	/** @var DbHandler */
	private $dbHandler;

	/**
	 * AuthController constructor.
	 *
	 * @param IRequest $request
	 * @param ISecureRandom $secureRandom
	 * @param JobList $jobList
	 * @param TrustedServers $trustedServers
	 * @param DbHandler $dbHandler
	 */
	public function __construct(
		IRequest $request,
		ISecureRandom $secureRandom,
		JobList $jobList,
		TrustedServers $trustedServers,
		DbHandler $dbHandler
	) {
		$this->request = $request;
		$this->secureRandom = $secureRandom;
		$this->jobList = $jobList;
		$this->trustedServers = $trustedServers;
		$this->dbHandler = $dbHandler;
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
			return new \OC_OCS_Result(null, HTTP::STATUS_FORBIDDEN);
		}

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

		if (
			$this->trustedServers->isTrustedServer($url) === false
			|| $this->isValidToken($url, $token) === false
		) {
			return new \OC_OCS_Result(null, HTTP::STATUS_FORBIDDEN);
		}

		$sharedSecret = $this->secureRandom->getMediumStrengthGenerator()->generate(32);

		$this->trustedServers->addSharedSecret($url, $sharedSecret);

		return new \OC_OCS_Result(['sharedSecret' => $sharedSecret], Http::STATUS_OK);

	}

	protected function isValidToken($url, $token) {
		$storedToken = $this->dbHandler->getToken($url);
		return StringUtils::equals($storedToken, $token);
	}

}

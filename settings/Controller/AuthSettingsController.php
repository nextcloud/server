<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Robin Appelman <robin@icewind.nl>
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

namespace OC\Settings\Controller;

use OC\AppFramework\Http;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use OCP\Session\Exceptions\SessionNotAvailableException;

class AuthSettingsController extends Controller {

	/** @var IProvider */
	private $tokenProvider;

	/** @var IUserManager */
	private $userManager;

	/** @var ISession */
	private $session;

	/** @var string */
	private $uid;

	/** @var ISecureRandom */
	private $random;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IProvider $tokenProvider
	 * @param IUserManager $userManager
	 * @param ISession $session
	 * @param ISecureRandom $random
	 * @param string $uid
	 */
	public function __construct($appName, IRequest $request, IProvider $tokenProvider, IUserManager $userManager,
		ISession $session, ISecureRandom $random, $uid) {
		parent::__construct($appName, $request);
		$this->tokenProvider = $tokenProvider;
		$this->userManager = $userManager;
		$this->uid = $uid;
		$this->session = $session;
		$this->random = $random;
	}

	/**
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 *
	 * @return JSONResponse
	 */
	public function index() {
		$user = $this->userManager->get($this->uid);
		if (is_null($user)) {
			return [];
		}
		$tokens = $this->tokenProvider->getTokenByUser($user);
		
		try {
			$sessionId = $this->session->getId();
		} catch (SessionNotAvailableException $ex) {
			return $this->getServiceNotAvailableResponse();
		}
		try {
			$sessionToken = $this->tokenProvider->getToken($sessionId);
		} catch (InvalidTokenException $ex) {
			return $this->getServiceNotAvailableResponse();
		}

		return array_map(function(IToken $token) use ($sessionToken) {
			$data = $token->jsonSerialize();
			if ($sessionToken->getId() === $token->getId()) {
				$data['canDelete'] = false;
				$data['current'] = true;
			} else {
				$data['canDelete'] = true;
			}
			return $data;
		}, $tokens);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 *
	 * @return JSONResponse
	 */
	public function create($name) {
		try {
			$sessionId = $this->session->getId();
		} catch (SessionNotAvailableException $ex) {
			return $this->getServiceNotAvailableResponse();
		}

		try {
			$sessionToken = $this->tokenProvider->getToken($sessionId);
			$loginName = $sessionToken->getLoginName();
			try {
				$password = $this->tokenProvider->getPassword($sessionToken, $sessionId);
			} catch (PasswordlessTokenException $ex) {
				$password = null;
			}
		} catch (InvalidTokenException $ex) {
			return $this->getServiceNotAvailableResponse();
		}

		$token = $this->generateRandomDeviceToken();
		$deviceToken = $this->tokenProvider->generateToken($token, $this->uid, $loginName, $password, $name, IToken::PERMANENT_TOKEN);

		return [
			'token' => $token,
			'loginName' => $loginName,
			'deviceToken' => $deviceToken
		];
	}

	private function getServiceNotAvailableResponse() {
		$resp = new JSONResponse();
		$resp->setStatus(Http::STATUS_SERVICE_UNAVAILABLE);
		return $resp;
	}

	/**
	 * Return a 20 digit device password
	 *
	 * Example: ABCDE-FGHIJ-KLMNO-PQRST
	 *
	 * @return string
	 */
	private function generateRandomDeviceToken() {
		$groups = [];
		for ($i = 0; $i < 4; $i++) {
			$groups[] = $this->random->generate(5, implode('', range('A', 'Z')));
		}
		return implode('-', $groups);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 *
	 * @return JSONResponse
	 */
	public function destroy($id) {
		$user = $this->userManager->get($this->uid);
		if (is_null($user)) {
			return [];
		}

		$this->tokenProvider->invalidateTokenById($user, $id);
		return [];
	}

}

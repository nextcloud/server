<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Fabrizio Steiner <fabrizio.steiner@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Marcel Waldvogel <marcel.waldvogel@uni-konstanz.de>
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
	 * @param string $userId
	 */
	public function __construct($appName, IRequest $request, IProvider $tokenProvider, IUserManager $userManager,
		ISession $session, ISecureRandom $random, $userId) {
		parent::__construct($appName, $request);
		$this->tokenProvider = $tokenProvider;
		$this->userManager = $userManager;
		$this->uid = $userId;
		$this->session = $session;
		$this->random = $random;
	}

	/**
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 *
	 * @return JSONResponse|array
	 */
	public function index() {
		$user = $this->userManager->get($this->uid);
		if ($user === null) {
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
	 * @PasswordConfirmationRequired
	 *
	 * @param string $name
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
		$tokenData = $deviceToken->jsonSerialize();
		$tokenData['canDelete'] = true;

		return new JSONResponse([
			'token' => $token,
			'loginName' => $loginName,
			'deviceToken' => $tokenData,
		]);
	}

	/**
	 * @return JSONResponse
	 */
	private function getServiceNotAvailableResponse() {
		$resp = new JSONResponse();
		$resp->setStatus(Http::STATUS_SERVICE_UNAVAILABLE);
		return $resp;
	}

	/**
	 * Return a 25 digit device password
	 *
	 * Example: AbCdE-fGhJk-MnPqR-sTwXy-23456
	 *
	 * @return string
	 */
	private function generateRandomDeviceToken() {
		$groups = [];
		for ($i = 0; $i < 5; $i++) {
			$groups[] = $this->random->generate(5, ISecureRandom::CHAR_HUMAN_READABLE);
		}
		return implode('-', $groups);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 *
	 * @return array
	 */
	public function destroy($id) {
		$user = $this->userManager->get($this->uid);
		if (is_null($user)) {
			return [];
		}

		$this->tokenProvider->invalidateTokenById($user, $id);
		return [];
	}

	/**
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 *
	 * @param int $id
	 * @param array $scope
	 * @return array
	 */
	public function update($id, array $scope) {
		$token = $this->tokenProvider->getTokenById((string)$id);
		$token->setScope([
			'filesystem' => $scope['filesystem']
		]);
		$this->tokenProvider->updateToken($token);
		return [];
	}
}

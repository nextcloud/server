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

use BadMethodCallException;
use OC\AppFramework\Http;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Settings\Activity\Provider;
use OCP\Activity\IManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\Security\ISecureRandom;
use OCP\Session\Exceptions\SessionNotAvailableException;

class AuthSettingsController extends Controller {

	/** @var IProvider */
	private $tokenProvider;

	/** @var ISession */
	private $session;

	/** @var string */
	private $uid;

	/** @var ISecureRandom */
	private $random;

	/** @var IManager */
	private $activityManager;

	/** @var ILogger */
	private $logger;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IProvider $tokenProvider
	 * @param ISession $session
	 * @param ISecureRandom $random
	 * @param string|null $userId
	 * @param IManager $activityManager
	 * @param ILogger $logger
	 */
	public function __construct(string $appName,
								IRequest $request,
								IProvider $tokenProvider,
								ISession $session,
								ISecureRandom $random,
								?string $userId,
								IManager $activityManager,
								ILogger $logger) {
		parent::__construct($appName, $request);
		$this->tokenProvider = $tokenProvider;
		$this->uid = $userId;
		$this->session = $session;
		$this->random = $random;
		$this->activityManager = $activityManager;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 *
	 * @return JSONResponse|array
	 */
	public function index() {
		$tokens = $this->tokenProvider->getTokenByUser($this->uid);

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

		return array_map(function (IToken $token) use ($sessionToken) {
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

		$this->publishActivity(Provider::APP_TOKEN_CREATED, $deviceToken->getId(), $deviceToken->getName());

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
	 * @param int $id
	 * @return array|JSONResponse
	 */
	public function destroy($id) {
		try {
			$token = $this->findTokenByIdAndUser($id);
		} catch (InvalidTokenException $e) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		$this->tokenProvider->invalidateTokenById($this->uid, $token->getId());
		$this->publishActivity(Provider::APP_TOKEN_DELETED, $token->getId(), $token->getName());
		return [];
	}

	/**
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 *
	 * @param int $id
	 * @param array $scope
	 * @return array|JSONResponse
	 */
	public function update($id, array $scope) {
		try {
			$token = $this->findTokenByIdAndUser($id);
		} catch (InvalidTokenException $e) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		$token->setScope([
			'filesystem' => $scope['filesystem']
		]);

		$this->tokenProvider->updateToken($token);
		$this->publishActivity(Provider::APP_TOKEN_UPDATED, $token->getId(), $token->getName());
		return [];
	}

	/**
	 * @param string $subject
	 * @param int $id
	 * @param string|null $tokenName
	 */
	private function publishActivity(string $subject, int $id, ?string $tokenName = null): void {
		$event = $this->activityManager->generateEvent();
		$event->setApp('settings')
			->setType('security')
			->setAffectedUser($this->uid)
			->setAuthor($this->uid)
			->setSubject($subject, [$tokenName])
			->setObject('app_token', $id, 'App Password');

		try {
			$this->activityManager->publish($event);
		} catch (BadMethodCallException $e) {
			$this->logger->warning('could not publish activity');
			$this->logger->logException($e);
		}
	}

	/**
	 * Find a token by given id and check if uid for current session belongs to this token
	 *
	 * @param int $id
	 * @return IToken
	 * @throws InvalidTokenException
	 * @throws \OC\Authentication\Exceptions\ExpiredTokenException
	 */
	private function findTokenByIdAndUser(int $id): IToken {
		$token = $this->tokenProvider->getTokenById((string)$id);
		if ($token->getUID() !== $this->uid) {
			throw new InvalidTokenException('This token does not belong to you!');
		}
		return $token;
	}
}

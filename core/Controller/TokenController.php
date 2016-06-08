<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Core\Controller;

use OC\AppFramework\Http;
use OC\Authentication\Token\DefaultTokenProvider;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Authentication\TwoFactorAuth\Manager as TwoFactorAuthManager;
use OC\User\Manager as UserManager;
use OCA\User_LDAP\User\Manager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Security\ISecureRandom;

class TokenController extends Controller {

	/** @var UserManager */
	private $userManager;

	/** @var IProvider */
	private $tokenProvider;

	/** @var TwoFactorAuthManager */
	private $twoFactorAuthManager;

	/** @var ISecureRandom */
	private $secureRandom;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param Manager $userManager
	 * @param DefaultTokenProvider $tokenProvider
	 * @param ISecureRandom $secureRandom
	 */
	public function __construct($appName, IRequest $request, UserManager $userManager, IProvider $tokenProvider, TwoFactorAuthManager $twoFactorAuthManager, ISecureRandom $secureRandom) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->tokenProvider = $tokenProvider;
		$this->secureRandom = $secureRandom;
		$this->twoFactorAuthManager = $twoFactorAuthManager;
	}

	/**
	 * Generate a new access token clients can authenticate with
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $user
	 * @param string $password
	 * @param string $name the name of the client
	 * @return JSONResponse
	 */
	public function generateToken($user, $password, $name = 'unknown client') {
		if (is_null($user) || is_null($password)) {
			$response = new JSONResponse();
			$response->setStatus(Http::STATUS_UNPROCESSABLE_ENTITY);
			return $response;
		}
		$loginName = $user;
		$user = $this->userManager->checkPassword($loginName, $password);
		if ($user === false) {
			$response = new JSONResponse();
			$response->setStatus(Http::STATUS_UNAUTHORIZED);
			return $response;
		}

		if ($this->twoFactorAuthManager->isTwoFactorAuthenticated($user)) {
			$resp = new JSONResponse();
			$resp->setStatus(Http::STATUS_UNAUTHORIZED);
			return $resp;
		}

		$token = $this->secureRandom->generate(128);
		$this->tokenProvider->generateToken($token, $user->getUID(), $loginName, $password, $name, IToken::PERMANENT_TOKEN);
		return [
			'token' => $token,
		];
	}

}

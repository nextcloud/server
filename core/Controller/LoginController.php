<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

use OC\Setup;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

class LoginController extends Controller {
	/** @var IUserManager */
	private $userManager;
	/** @var IConfig */
	private $config;
	/** @var ISession */
	private $session;
	/** @var IUserSession */
	private $userSession;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 * @param ISession $session
	 * @param IUserSession $userSession
	 */
	function __construct($appName,
						 IRequest $request,
						 IUserManager $userManager,
						 IConfig $config,
						 ISession $session,
						 IUserSession $userSession) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->config = $config;
		$this->session = $session;
		$this->userSession = $userSession;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @UseSession
	 *
	 * @param string $user
	 * @param string $redirect_url
	 * @param string $remember_login
	 *
	 * @return TemplateResponse
	 */
	public function showLoginForm($user,
								  $redirect_url,
								  $remember_login) {
		if($this->userSession->isLoggedIn()) {
			return new RedirectResponse(\OC_Util::getDefaultPageUrl());
		}

		$parameters = array();
		$loginMessages = $this->session->get('loginMessages');
		$errors = [];
		$messages = [];
		if(is_array($loginMessages)) {
			list($errors, $messages) = $loginMessages;
		}
		$this->session->remove('loginMessages');
		foreach ($errors as $value) {
			$parameters[$value] = true;
		}

		$parameters['messages'] = $messages;
		if (!empty($user)) {
			$parameters['username'] = $user;
			$parameters['user_autofocus'] = false;
		} else {
			$parameters['username'] = '';
			$parameters['user_autofocus'] = true;
		}
		if (!empty($redirect_url)) {
			$parameters['redirect_url'] = $redirect_url;
		}

		$parameters['canResetPassword'] = true;
		if (!$this->config->getSystemValue('lost_password_link')) {
			if (!empty($user)) {
				$userObj = $this->userManager->get($user);
				if ($userObj instanceof IUser) {
					$parameters['canResetPassword'] = $userObj->canChangePassword();
				}
			}
		}

		$parameters['alt_login'] = \OC_App::getAlternativeLogIns();
		$parameters['rememberLoginAllowed'] = \OC_Util::rememberLoginAllowed();
		$parameters['rememberLoginState'] = !empty($remember_login) ? $remember_login : 0;

		if (!empty($user)) {
			$parameters['username'] = $user;
			$parameters['user_autofocus'] = false;
		} else {
			$parameters['username'] = '';
			$parameters['user_autofocus'] = true;
		}

		return new TemplateResponse(
			$this->appName,
			'login',
			$parameters,
			'guest'
		);
	}

}

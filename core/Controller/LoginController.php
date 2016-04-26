<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
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

use OC;
use OC\User\Session;
use OC_App;
use OC_Util;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;

class LoginController extends Controller {

	/** @var IUserManager */
	private $userManager;

	/** @var IConfig */
	private $config;

	/** @var ISession */
	private $session;

	/** @var Session */
	private $userSession;

	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 * @param ISession $session
	 * @param Session $userSession
	 * @param IURLGenerator $urlGenerator
	 */
	function __construct($appName, IRequest $request, IUserManager $userManager,
		IConfig $config, ISession $session, Session $userSession,
		IURLGenerator $urlGenerator) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->config = $config;
		$this->session = $session;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @NoAdminRequired
	 * @UseSession
	 *
	 * @return RedirectResponse
	 */
	public function logout() {
		$loginToken = $this->request->getCookie('oc_token');
		if (!is_null($loginToken)) {
			$this->config->deleteUserValue($this->userSession->getUser()->getUID(), 'login_token', $loginToken);
		}
		$this->userSession->logout();

		return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('core.login.showLoginForm'));
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
	public function showLoginForm($user, $redirect_url, $remember_login) {
		if ($this->userSession->isLoggedIn()) {
			return new RedirectResponse(OC_Util::getDefaultPageUrl());
		}

		$parameters = array();
		$loginMessages = $this->session->get('loginMessages');
		$errors = [];
		$messages = [];
		if (is_array($loginMessages)) {
			list($errors, $messages) = $loginMessages;
		}
		$this->session->remove('loginMessages');
		foreach ($errors as $value) {
			$parameters[$value] = true;
		}

		$parameters['messages'] = $messages;
		if (!is_null($user) && $user !== '') {
			$parameters['loginName'] = $user;
			$parameters['user_autofocus'] = false;
		} else {
			$parameters['loginName'] = '';
			$parameters['user_autofocus'] = true;
		}
		if (!empty($redirect_url)) {
			$parameters['redirect_url'] = $redirect_url;
		}

		$parameters['canResetPassword'] = true;
		if (!$this->config->getSystemValue('lost_password_link')) {
			if (!is_null($user) && $user !== '') {
				$userObj = $this->userManager->get($user);
				if ($userObj instanceof IUser) {
					$parameters['canResetPassword'] = $userObj->canChangePassword();
				}
			}
		}

		$parameters['alt_login'] = OC_App::getAlternativeLogIns();
		$parameters['rememberLoginAllowed'] = OC_Util::rememberLoginAllowed();
		$parameters['rememberLoginState'] = !empty($remember_login) ? $remember_login : 0;

		if (!is_null($user) && $user !== '') {
			$parameters['loginName'] = $user;
			$parameters['user_autofocus'] = false;
		} else {
			$parameters['loginName'] = '';
			$parameters['user_autofocus'] = true;
		}

		return new TemplateResponse(
			$this->appName, 'login', $parameters, 'guest'
		);
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 * @UseSession
	 *
	 * @param string $user
	 * @param string $password
	 * @param string $redirect_url
	 * @return RedirectResponse
	 */
	public function tryLogin($user, $password, $redirect_url) {
		// TODO: Add all the insane error handling
		if ($this->userManager->checkPassword($user, $password) === false) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('login#showLoginForm'));
		}
		$this->userSession->createSessionToken($user, $password);
		if (!is_null($redirect_url) && $this->userSession->isLoggedIn()) {
			$location = OC::$server->getURLGenerator()->getAbsoluteURL(urldecode($redirect_url));
			// Deny the redirect if the URL contains a @
			// This prevents unvalidated redirects like ?redirect_url=:user@domain.com
			if (strpos($location, '@') === false) {
				return new RedirectResponse($location);
			}
		}
		// TODO: Show invalid login warning
		return new RedirectResponse($this->urlGenerator->linkTo('files', 'index'));
	}

}

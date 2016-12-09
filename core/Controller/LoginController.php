<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OC\Core\Controller;

use OC\AppFramework\Utility\TimeFactory;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Security\Bruteforce\Throttler;
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
	/** @var Manager */
	private $twoFactorManager;
	/** @var Throttler */
	private $throttler;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 * @param ISession $session
	 * @param Session $userSession
	 * @param IURLGenerator $urlGenerator
	 * @param Manager $twoFactorManager
	 * @param Throttler $throttler
	 */
	function __construct($appName,
						 IRequest $request,
						 IUserManager $userManager,
						 IConfig $config,
						 ISession $session,
						 Session $userSession,
						 IURLGenerator $urlGenerator,
						 Manager $twoFactorManager,
						 Throttler $throttler) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->config = $config;
		$this->session = $session;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
		$this->twoFactorManager = $twoFactorManager;
		$this->throttler = $throttler;
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
	 * @return TemplateResponse|RedirectResponse
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
		$parameters['resetPasswordLink'] = $this->config->getSystemValue('lost_password_link', '');
		if (!$parameters['resetPasswordLink']) {
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
	 * @param string $redirectUrl
	 * @return RedirectResponse
	 */
	private function generateRedirect($redirectUrl) {
		if (!is_null($redirectUrl) && $this->userSession->isLoggedIn()) {
			$location = $this->urlGenerator->getAbsoluteURL(urldecode($redirectUrl));
			// Deny the redirect if the URL contains a @
			// This prevents unvalidated redirects like ?redirect_url=:user@domain.com
			if (strpos($location, '@') === false) {
				return new RedirectResponse($location);
			}
		}
		return new RedirectResponse(OC_Util::getDefaultPageUrl());
	}

	/**
	 * @PublicPage
	 * @UseSession
	 * @NoCSRFRequired
	 *
	 * @param string $user
	 * @param string $password
	 * @param string $redirect_url
	 * @param string $timezone
	 * @param string $timezone_offset
	 * @return RedirectResponse
	 */
	public function tryLogin($user, $password, $redirect_url, $timezone = '', $timezone_offset = '') {
		$currentDelay = $this->throttler->getDelay($this->request->getRemoteAddress());
		$this->throttler->sleepDelay($this->request->getRemoteAddress());

		// If the user is already logged in and the CSRF check does not pass then
		// simply redirect the user to the correct page as required. This is the
		// case when an user has already logged-in, in another tab.
		if(!$this->request->passesCSRFCheck()) {
			return $this->generateRedirect($redirect_url);
		}

		$originalUser = $user;
		// TODO: Add all the insane error handling
		/* @var $loginResult IUser */
		$loginResult = $this->userManager->checkPassword($user, $password);
		if ($loginResult === false) {
			$users = $this->userManager->getByEmail($user);
			// we only allow login by email if unique
			if (count($users) === 1) {
				$user = $users[0]->getUID();
				$loginResult = $this->userManager->checkPassword($user, $password);
			}
		}
		if ($loginResult === false) {
			$this->throttler->registerAttempt('login', $this->request->getRemoteAddress(), ['user' => $originalUser]);
			if($currentDelay === 0) {
				$this->throttler->sleepDelay($this->request->getRemoteAddress());
			}
			$this->session->set('loginMessages', [
				['invalidpassword'], []
			]);
			// Read current user and append if possible - we need to return the unmodified user otherwise we will leak the login name
			$args = !is_null($user) ? ['user' => $originalUser] : [];
			return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm', $args));
		}
		// TODO: remove password checks from above and let the user session handle failures
		// requires https://github.com/owncloud/core/pull/24616
		$this->userSession->login($user, $password);
		$this->userSession->createSessionToken($this->request, $loginResult->getUID(), $user, $password);

		if ($timezone_offset !== '') {
			$this->config->setUserValue($loginResult->getUID(), 'core', 'timezone', $timezone);
			$this->session->set('timezone', $timezone_offset);
		}

		if ($this->twoFactorManager->isTwoFactorAuthenticated($loginResult)) {
			$this->twoFactorManager->prepareTwoFactorLogin($loginResult);
			if (!is_null($redirect_url)) {
				return new RedirectResponse($this->urlGenerator->linkToRoute('core.TwoFactorChallenge.selectChallenge', [
					'redirect_url' => $redirect_url
				]));
			}
			return new RedirectResponse($this->urlGenerator->linkToRoute('core.TwoFactorChallenge.selectChallenge'));
		}

		return $this->generateRedirect($redirect_url);
	}

}

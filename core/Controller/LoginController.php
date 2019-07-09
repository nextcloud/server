<?php
/**
 * @copyright Copyright (c) 2017, Sandro Lutz <sandro.lutz@temparus.ch>
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author justin-sleep <justin@quarterfull.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Sandro Lutz <sandro.lutz@temparus.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Ujjwal Bhardwaj <ujjwalb1996@gmail.com>
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

use OC\Authentication\Token\IToken;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Security\Bruteforce\Throttler;
use OC\User\Session;
use OC_App;
use OC_Util;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Defaults;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OC\Hooks\PublicEmitter;
use OCP\Util;

class LoginController extends Controller {

	const LOGIN_MSG_INVALIDPASSWORD = 'invalidpassword';
	const LOGIN_MSG_USERDISABLED = 'userdisabled';

	/** @var IUserManager */
	private $userManager;
	/** @var IConfig */
	private $config;
	/** @var ISession */
	private $session;
	/** @var IUserSession|Session */
	private $userSession;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ILogger */
	private $logger;
	/** @var Manager */
	private $twoFactorManager;
	/** @var Defaults */
	private $defaults;
	/** @var Throttler */
	private $throttler;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 * @param ISession $session
	 * @param IUserSession $userSession
	 * @param IURLGenerator $urlGenerator
	 * @param ILogger $logger
	 * @param Manager $twoFactorManager
	 * @param Defaults $defaults
	 * @param Throttler $throttler
	 */
	public function __construct($appName,
								IRequest $request,
								IUserManager $userManager,
								IConfig $config,
								ISession $session,
								IUserSession $userSession,
								IURLGenerator $urlGenerator,
								ILogger $logger,
								Manager $twoFactorManager,
								Defaults $defaults,
								Throttler $throttler) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->config = $config;
		$this->session = $session;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->twoFactorManager = $twoFactorManager;
		$this->defaults = $defaults;
		$this->throttler = $throttler;
	}

	/**
	 * @NoAdminRequired
	 * @UseSession
	 *
	 * @return RedirectResponse
	 */
	public function logout() {
		$loginToken = $this->request->getCookie('nc_token');
		if (!is_null($loginToken)) {
			$this->config->deleteUserValue($this->userSession->getUser()->getUID(), 'login_token', $loginToken);
		}
		$this->userSession->logout();

		$response = new RedirectResponse($this->urlGenerator->linkToRouteAbsolute(
			'core.login.showLoginForm',
			['clear' => true] // this param the the code in login.js may be removed when the "Clear-Site-Data" is working in the browsers
		));

		$this->session->set('clearingExecutionContexts', '1');
		$this->session->close();
		$response->addHeader('Clear-Site-Data', '"cache", "storage"');
		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @UseSession
	 *
	 * @param string $user
	 * @param string $redirect_url
	 *
	 * @return TemplateResponse|RedirectResponse
	 */
	public function showLoginForm(string $user = null, string $redirect_url = null): Http\Response {
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
		if ($user !== null && $user !== '') {
			$parameters['loginName'] = $user;
			$parameters['user_autofocus'] = false;
		} else {
			$parameters['loginName'] = '';
			$parameters['user_autofocus'] = true;
		}

		$autocomplete = $this->config->getSystemValue('login_form_autocomplete', true);
		if ($autocomplete){
			$parameters['login_form_autocomplete'] = 'on';
		} else {
			$parameters['login_form_autocomplete'] = 'off';
		}

		if (!empty($redirect_url)) {
			$parameters['redirect_url'] = $redirect_url;
		}

		$parameters = $this->setPasswordResetParameters($user, $parameters);
		$parameters['alt_login'] = OC_App::getAlternativeLogIns();

		if ($user !== null && $user !== '') {
			$parameters['loginName'] = $user;
			$parameters['user_autofocus'] = false;
		} else {
			$parameters['loginName'] = '';
			$parameters['user_autofocus'] = true;
		}

		$parameters['throttle_delay'] = $this->throttler->getDelay($this->request->getRemoteAddress());

		// OpenGraph Support: http://ogp.me/
		Util::addHeader('meta', ['property' => 'og:title', 'content' => Util::sanitizeHTML($this->defaults->getName())]);
		Util::addHeader('meta', ['property' => 'og:description', 'content' => Util::sanitizeHTML($this->defaults->getSlogan())]);
		Util::addHeader('meta', ['property' => 'og:site_name', 'content' => Util::sanitizeHTML($this->defaults->getName())]);
		Util::addHeader('meta', ['property' => 'og:url', 'content' => $this->urlGenerator->getAbsoluteURL('/')]);
		Util::addHeader('meta', ['property' => 'og:type', 'content' => 'website']);
		Util::addHeader('meta', ['property' => 'og:image', 'content' => $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'favicon-touch.png'))]);

		return new TemplateResponse(
			$this->appName, 'login', $parameters, 'guest'
		);
	}

	/**
	 * Sets the password reset params.
	 *
	 * Users may not change their passwords if:
	 * - The account is disabled
	 * - The backend doesn't support password resets
	 * - The password reset function is disabled
	 *
	 * @param string $user
	 * @param array $parameters
	 * @return array
	 */
	private function setPasswordResetParameters(
		string $user = null, array $parameters): array {
		if ($user !== null && $user !== '') {
			$userObj = $this->userManager->get($user);
		} else {
			$userObj = null;
		}

		$parameters['resetPasswordLink'] = $this->config
			->getSystemValue('lost_password_link', '');

		if ($parameters['resetPasswordLink'] === 'disabled') {
			$parameters['canResetPassword'] = false;
		} else if (!$parameters['resetPasswordLink'] && $userObj !== null) {
			$parameters['canResetPassword'] = $userObj->canChangePassword();
		} else if ($userObj !== null && $userObj->isEnabled() === false) {
			$parameters['canResetPassword'] = false;
		} else {
			$parameters['canResetPassword'] = true;
		}

		return $parameters;
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
	 * @BruteForceProtection(action=login)
	 *
	 * @param string $user
	 * @param string $password
	 * @param string $redirect_url
	 * @param boolean $remember_login
	 * @param string $timezone
	 * @param string $timezone_offset
	 * @return RedirectResponse
	 */
	public function tryLogin($user, $password, $redirect_url, $remember_login = true, $timezone = '', $timezone_offset = '') {
		if(!is_string($user)) {
			throw new \InvalidArgumentException('Username must be string');
		}

		// If the user is already logged in and the CSRF check does not pass then
		// simply redirect the user to the correct page as required. This is the
		// case when an user has already logged-in, in another tab.
		if(!$this->request->passesCSRFCheck()) {
			return $this->generateRedirect($redirect_url);
		}

		if ($this->userManager instanceof PublicEmitter) {
			$this->userManager->emit('\OC\User', 'preLogin', array($user, $password));
		}

		$originalUser = $user;

		$userObj = $this->userManager->get($user);

		if ($userObj !== null && $userObj->isEnabled() === false) {
			$this->logger->warning('Login failed: \''. $user . '\' disabled' .
				' (Remote IP: \''. $this->request->getRemoteAddress(). '\')',
				['app' => 'core']);
			return $this->createLoginFailedResponse($user, $originalUser,
				$redirect_url, self::LOGIN_MSG_USERDISABLED);
		}

		// TODO: Add all the insane error handling
		/* @var $loginResult IUser */
		$loginResult = $this->userManager->checkPasswordNoLogging($user, $password);
		if ($loginResult === false) {
			$users = $this->userManager->getByEmail($user);
			// we only allow login by email if unique
			if (count($users) === 1) {
				$previousUser = $user;
				$user = $users[0]->getUID();
				if($user !== $previousUser) {
					$loginResult = $this->userManager->checkPassword($user, $password);
				}
			}
		}

		if ($loginResult === false) {
			$this->logger->warning('Login failed: \''. $user .
				'\' (Remote IP: \''. $this->request->getRemoteAddress(). '\')',
				['app' => 'core']);
			return $this->createLoginFailedResponse($user, $originalUser,
				$redirect_url, self::LOGIN_MSG_INVALIDPASSWORD);
		}

		// TODO: remove password checks from above and let the user session handle failures
		// requires https://github.com/owncloud/core/pull/24616
		$this->userSession->completeLogin($loginResult, ['loginName' => $user, 'password' => $password]);

		$tokenType = IToken::REMEMBER;
		if ((int)$this->config->getSystemValue('remember_login_cookie_lifetime', 60*60*24*15) === 0) {
			$remember_login = false;
			$tokenType = IToken::DO_NOT_REMEMBER;
		}

		$this->userSession->createSessionToken($this->request, $loginResult->getUID(), $user, $password, $tokenType);
		$this->userSession->updateTokens($loginResult->getUID(), $password);

		// User has successfully logged in, now remove the password reset link, when it is available
		$this->config->deleteUserValue($loginResult->getUID(), 'core', 'lostpassword');

		$this->session->set('last-password-confirm', $loginResult->getLastLogin());

		if ($timezone_offset !== '') {
			$this->config->setUserValue($loginResult->getUID(), 'core', 'timezone', $timezone);
			$this->session->set('timezone', $timezone_offset);
		}

		if ($this->twoFactorManager->isTwoFactorAuthenticated($loginResult)) {
			$this->twoFactorManager->prepareTwoFactorLogin($loginResult, $remember_login);

			$providers = $this->twoFactorManager->getProviderSet($loginResult)->getPrimaryProviders();
			if (count($providers) === 1) {
				// Single provider, hence we can redirect to that provider's challenge page directly
				/* @var $provider IProvider */
				$provider = array_pop($providers);
				$url = 'core.TwoFactorChallenge.showChallenge';
				$urlParams = [
					'challengeProviderId' => $provider->getId(),
				];
			} else {
				$url = 'core.TwoFactorChallenge.selectChallenge';
				$urlParams = [];
			}

			if (!is_null($redirect_url)) {
				$urlParams['redirect_url'] = $redirect_url;
			}

			return new RedirectResponse($this->urlGenerator->linkToRoute($url, $urlParams));
		}

		if ($remember_login) {
			$this->userSession->createRememberMeToken($loginResult);
		}

		return $this->generateRedirect($redirect_url);
	}

	/**
	 * Creates a login failed response.
	 *
	 * @param string $user
	 * @param string $originalUser
	 * @param string $redirect_url
	 * @param string $loginMessage
	 * @return RedirectResponse
	 */
	private function createLoginFailedResponse(
		$user, $originalUser, $redirect_url, string $loginMessage) {
		// Read current user and append if possible we need to
		// return the unmodified user otherwise we will leak the login name
		$args = !is_null($user) ? ['user' => $originalUser] : [];
		if (!is_null($redirect_url)) {
			$args['redirect_url'] = $redirect_url;
		}
		$response = new RedirectResponse(
			$this->urlGenerator->linkToRoute('core.login.showLoginForm', $args)
		);
		$response->throttle(['user' => substr($user, 0, 64)]);
		$this->session->set('loginMessages', [
			[$loginMessage], []
		]);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @UseSession
	 * @BruteForceProtection(action=sudo)
	 *
	 * @license GNU AGPL version 3 or any later version
	 *
	 * @param string $password
	 * @return DataResponse
	 */
	public function confirmPassword($password) {
		$loginName = $this->userSession->getLoginName();
		$loginResult = $this->userManager->checkPassword($loginName, $password);
		if ($loginResult === false) {
			$response = new DataResponse([], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		}

		$confirmTimestamp = time();
		$this->session->set('last-password-confirm', $confirmTimestamp);
		return new DataResponse(['lastLogin' => $confirmTimestamp], Http::STATUS_OK);
	}
}

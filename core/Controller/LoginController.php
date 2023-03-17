<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017, Sandro Lutz <sandro.lutz@temparus.ch>
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Weimann <mail@michael-weimann.eu>
 * @author Rayn0r <andrew@ilpss8.myfirewall.org>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Controller;

use OC\AppFramework\Http\Request;
use OC\Authentication\Login\Chain;
use OC\Authentication\Login\LoginData;
use OC\Authentication\WebAuthn\Manager as WebAuthnManager;
use OC\Security\Bruteforce\Throttler;
use OC\User\Session;
use OC_App;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Notification\IManager;
use OCP\Util;

class LoginController extends Controller {
	public const LOGIN_MSG_INVALIDPASSWORD = 'invalidpassword';
	public const LOGIN_MSG_USERDISABLED = 'userdisabled';

	private IUserManager $userManager;
	private IConfig $config;
	private ISession $session;
	/** @var IUserSession|Session */
	private $userSession;
	private IURLGenerator $urlGenerator;
	private Defaults $defaults;
	private Throttler $throttler;
	private IInitialStateService $initialStateService;
	private WebAuthnManager $webAuthnManager;
	private IManager $manager;
	private IL10N $l10n;

	public function __construct(?string $appName,
								IRequest $request,
								IUserManager $userManager,
								IConfig $config,
								ISession $session,
								IUserSession $userSession,
								IURLGenerator $urlGenerator,
								Defaults $defaults,
								Throttler $throttler,
								IInitialStateService $initialStateService,
								WebAuthnManager $webAuthnManager,
								IManager $manager,
								IL10N $l10n) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->config = $config;
		$this->session = $session;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
		$this->defaults = $defaults;
		$this->throttler = $throttler;
		$this->initialStateService = $initialStateService;
		$this->webAuthnManager = $webAuthnManager;
		$this->manager = $manager;
		$this->l10n = $l10n;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return RedirectResponse
	 */
	#[UseSession]
	public function logout() {
		$loginToken = $this->request->getCookie('nc_token');
		if (!is_null($loginToken)) {
			$this->config->deleteUserValue($this->userSession->getUser()->getUID(), 'login_token', $loginToken);
		}
		$this->userSession->logout();

		$response = new RedirectResponse($this->urlGenerator->linkToRouteAbsolute(
			'core.login.showLoginForm',
			['clear' => true] // this param the code in login.js may be removed when the "Clear-Site-Data" is working in the browsers
		));

		$this->session->set('clearingExecutionContexts', '1');
		$this->session->close();

		if (!$this->request->isUserAgent([Request::USER_AGENT_CHROME, Request::USER_AGENT_ANDROID_MOBILE_CHROME])) {
			$response->addHeader('Clear-Site-Data', '"cache", "storage"');
		}

		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $user
	 * @param string $redirect_url
	 *
	 * @return TemplateResponse|RedirectResponse
	 */
	#[UseSession]
	public function showLoginForm(string $user = null, string $redirect_url = null): Http\Response {
		if ($this->userSession->isLoggedIn()) {
			return new RedirectResponse($this->urlGenerator->linkToDefaultPageUrl());
		}

		$loginMessages = $this->session->get('loginMessages');
		if (!$this->manager->isFairUseOfFreePushService()) {
			if (!is_array($loginMessages)) {
				$loginMessages = [[], []];
			}
			$loginMessages[1][] = $this->l10n->t('This community release of Nextcloud is unsupported and push notifications are limited.');
		}
		if (is_array($loginMessages)) {
			[$errors, $messages] = $loginMessages;
			$this->initialStateService->provideInitialState('core', 'loginMessages', $messages);
			$this->initialStateService->provideInitialState('core', 'loginErrors', $errors);
		}
		$this->session->remove('loginMessages');

		if ($user !== null && $user !== '') {
			$this->initialStateService->provideInitialState('core', 'loginUsername', $user);
		} else {
			$this->initialStateService->provideInitialState('core', 'loginUsername', '');
		}

		$this->initialStateService->provideInitialState(
			'core',
			'loginAutocomplete',
			$this->config->getSystemValue('login_form_autocomplete', true) === true
		);

		if (!empty($redirect_url)) {
			[$url, ] = explode('?', $redirect_url);
			if ($url !== $this->urlGenerator->linkToRoute('core.login.logout')) {
				$this->initialStateService->provideInitialState('core', 'loginRedirectUrl', $redirect_url);
			}
		}

		$this->initialStateService->provideInitialState(
			'core',
			'loginThrottleDelay',
			$this->throttler->getDelay($this->request->getRemoteAddress())
		);

		$this->setPasswordResetInitialState($user);

		$this->initialStateService->provideInitialState('core', 'webauthn-available', $this->webAuthnManager->isWebAuthnAvailable());

		$this->initialStateService->provideInitialState('core', 'hideLoginForm', $this->config->getSystemValueBool('hide_login_form', false));

		// OpenGraph Support: http://ogp.me/
		Util::addHeader('meta', ['property' => 'og:title', 'content' => Util::sanitizeHTML($this->defaults->getName())]);
		Util::addHeader('meta', ['property' => 'og:description', 'content' => Util::sanitizeHTML($this->defaults->getSlogan())]);
		Util::addHeader('meta', ['property' => 'og:site_name', 'content' => Util::sanitizeHTML($this->defaults->getName())]);
		Util::addHeader('meta', ['property' => 'og:url', 'content' => $this->urlGenerator->getAbsoluteURL('/')]);
		Util::addHeader('meta', ['property' => 'og:type', 'content' => 'website']);
		Util::addHeader('meta', ['property' => 'og:image', 'content' => $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'favicon-touch.png'))]);

		$parameters = [
			'alt_login' => OC_App::getAlternativeLogIns(),
			'pageTitle' => $this->l10n->t('Login'),
		];

		$this->initialStateService->provideInitialState('core', 'countAlternativeLogins', count($parameters['alt_login']));
		$this->initialStateService->provideInitialState('core', 'alternativeLogins', $parameters['alt_login']);

		return new TemplateResponse(
			$this->appName,
			'login',
			$parameters,
			TemplateResponse::RENDER_AS_GUEST,
		);
	}

	/**
	 * Sets the password reset state
	 *
	 * @param string $username
	 */
	private function setPasswordResetInitialState(?string $username): void {
		if ($username !== null && $username !== '') {
			$user = $this->userManager->get($username);
		} else {
			$user = null;
		}

		$passwordLink = $this->config->getSystemValueString('lost_password_link', '');

		$this->initialStateService->provideInitialState(
			'core',
			'loginResetPasswordLink',
			$passwordLink
		);

		$this->initialStateService->provideInitialState(
			'core',
			'loginCanResetPassword',
			$this->canResetPassword($passwordLink, $user)
		);
	}

	/**
	 * @param string|null $passwordLink
	 * @param IUser|null $user
	 *
	 * Users may not change their passwords if:
	 * - The account is disabled
	 * - The backend doesn't support password resets
	 * - The password reset function is disabled
	 *
	 * @return bool
	 */
	private function canResetPassword(?string $passwordLink, ?IUser $user): bool {
		if ($passwordLink === 'disabled') {
			return false;
		}

		if (!$passwordLink && $user !== null) {
			return $user->canChangePassword();
		}

		if ($user !== null && $user->isEnabled() === false) {
			return false;
		}

		return true;
	}

	private function generateRedirect(?string $redirectUrl): RedirectResponse {
		if ($redirectUrl !== null && $this->userSession->isLoggedIn()) {
			$location = $this->urlGenerator->getAbsoluteURL($redirectUrl);
			// Deny the redirect if the URL contains a @
			// This prevents unvalidated redirects like ?redirect_url=:user@domain.com
			if (strpos($location, '@') === false) {
				return new RedirectResponse($location);
			}
		}
		return new RedirectResponse($this->urlGenerator->linkToDefaultPageUrl());
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @BruteForceProtection(action=login)
	 *
	 * @return RedirectResponse
	 */
	#[UseSession]
	public function tryLogin(Chain $loginChain,
							 string $user = '',
							 string $password = '',
							 string $redirect_url = null,
							 string $timezone = '',
							 string $timezone_offset = ''): RedirectResponse {
		if (!$this->request->passesCSRFCheck()) {
			if ($this->userSession->isLoggedIn()) {
				// If the user is already logged in and the CSRF check does not pass then
				// simply redirect the user to the correct page as required. This is the
				// case when a user has already logged-in, in another tab.
				return $this->generateRedirect($redirect_url);
			}

			// Clear any auth remnants like cookies to ensure a clean login
			// For the next attempt
			$this->userSession->logout();
			return $this->createLoginFailedResponse(
				$user,
				$user,
				$redirect_url,
				$this->l10n->t('Please try again')
			);
		}

		$data = new LoginData(
			$this->request,
			trim($user),
			$password,
			$redirect_url,
			$timezone,
			$timezone_offset
		);
		$result = $loginChain->process($data);
		if (!$result->isSuccess()) {
			return $this->createLoginFailedResponse(
				$data->getUsername(),
				$user,
				$redirect_url,
				$result->getErrorMessage()
			);
		}

		if ($result->getRedirectUrl() !== null) {
			return new RedirectResponse($result->getRedirectUrl());
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
	 *
	 * @return RedirectResponse
	 */
	private function createLoginFailedResponse(
		$user, $originalUser, $redirect_url, string $loginMessage) {
		// Read current user and append if possible we need to
		// return the unmodified user otherwise we will leak the login name
		$args = $user !== null ? ['user' => $originalUser, 'direct' => 1] : [];
		if ($redirect_url !== null) {
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
	 * @BruteForceProtection(action=sudo)
	 *
	 * @license GNU AGPL version 3 or any later version
	 *
	 */
	#[UseSession]
	public function confirmPassword(string $password): DataResponse {
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

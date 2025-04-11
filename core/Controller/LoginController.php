<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Controller;

use OC\AppFramework\Http\Request;
use OC\Authentication\Login\Chain;
use OC\Authentication\Login\LoginData;
use OC\Authentication\WebAuthn\Manager as WebAuthnManager;
use OC\User\Session;
use OC_App;
use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Helper;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ITrustedDomainHelper;
use OCP\Util;

class LoginController extends Controller {
	public const LOGIN_MSG_INVALIDPASSWORD = 'invalidpassword';
	public const LOGIN_MSG_USERDISABLED = 'userdisabled';
	public const LOGIN_MSG_CSRFCHECKFAILED = 'csrfCheckFailed';
	public const LOGIN_MSG_INVALID_ORIGIN = 'invalidOrigin';

	public function __construct(
		?string $appName,
		IRequest $request,
		private IUserManager $userManager,
		private IConfig $config,
		private ISession $session,
		private Session $userSession,
		private IURLGenerator $urlGenerator,
		private Defaults $defaults,
		private IThrottler $throttler,
		private IInitialState $initialState,
		private WebAuthnManager $webAuthnManager,
		private IManager $manager,
		private IL10N $l10n,
		private IAppManager $appManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return RedirectResponse
	 */
	#[NoAdminRequired]
	#[UseSession]
	#[FrontpageRoute(verb: 'GET', url: '/logout')]
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

		if (
			$this->request->getServerProtocol() === 'https' &&
			!$this->request->isUserAgent([Request::USER_AGENT_CHROME, Request::USER_AGENT_ANDROID_MOBILE_CHROME])
		) {
			$response->addHeader('Clear-Site-Data', '"cache", "storage"');
		}

		return $response;
	}

	/**
	 * @param string $user
	 * @param string $redirect_url
	 *
	 * @return TemplateResponse|RedirectResponse
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[UseSession]
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/login')]
	public function showLoginForm(?string $user = null, ?string $redirect_url = null): Http\Response {
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
			$this->initialState->provideInitialState('loginMessages', $messages);
			$this->initialState->provideInitialState('loginErrors', $errors);
		}
		$this->session->remove('loginMessages');

		if ($user !== null && $user !== '') {
			$this->initialState->provideInitialState('loginUsername', $user);
		} else {
			$this->initialState->provideInitialState('loginUsername', '');
		}

		$this->initialState->provideInitialState(
			'loginAutocomplete',
			$this->config->getSystemValue('login_form_autocomplete', true) === true
		);

		if (!empty($redirect_url)) {
			[$url, ] = explode('?', $redirect_url);
			if ($url !== $this->urlGenerator->linkToRoute('core.login.logout')) {
				$this->initialState->provideInitialState('loginRedirectUrl', $redirect_url);
			}
		}

		$this->initialState->provideInitialState(
			'loginThrottleDelay',
			$this->throttler->getDelay($this->request->getRemoteAddress())
		);

		$this->setPasswordResetInitialState($user);

		$this->setEmailStates();

		$this->initialState->provideInitialState('webauthn-available', $this->webAuthnManager->isWebAuthnAvailable());

		$this->initialState->provideInitialState('hideLoginForm', $this->config->getSystemValueBool('hide_login_form', false));

		// OpenGraph Support: http://ogp.me/
		Util::addHeader('meta', ['property' => 'og:title', 'content' => Util::sanitizeHTML($this->defaults->getName())]);
		Util::addHeader('meta', ['property' => 'og:description', 'content' => Util::sanitizeHTML($this->defaults->getSlogan())]);
		Util::addHeader('meta', ['property' => 'og:site_name', 'content' => Util::sanitizeHTML($this->defaults->getName())]);
		Util::addHeader('meta', ['property' => 'og:url', 'content' => $this->urlGenerator->getAbsoluteURL('/')]);
		Util::addHeader('meta', ['property' => 'og:type', 'content' => 'website']);
		Util::addHeader('meta', ['property' => 'og:image', 'content' => $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'favicon-touch.png'))]);

		// Add same-origin referrer policy so we can check for valid requests
		Util::addHeader('meta', ['name' => 'referrer', 'content' => 'same-origin']);

		$parameters = [
			'alt_login' => OC_App::getAlternativeLogIns(),
			'pageTitle' => $this->l10n->t('Login'),
		];

		$this->initialState->provideInitialState('countAlternativeLogins', count($parameters['alt_login']));
		$this->initialState->provideInitialState('alternativeLogins', $parameters['alt_login']);
		$this->initialState->provideInitialState('loginTimeout', $this->config->getSystemValueInt('login_form_timeout', 5 * 60));

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

		$this->initialState->provideInitialState(
			'loginResetPasswordLink',
			$passwordLink
		);

		$this->initialState->provideInitialState(
			'loginCanResetPassword',
			$this->canResetPassword($passwordLink, $user)
		);
	}

	/**
	 * Sets the initial state of whether or not a user is allowed to login with their email
	 * initial state is passed in the array of 1 for email allowed and 0 for not allowed
	 */
	private function setEmailStates(): void {
		$emailStates = []; // true: can login with email, false otherwise - default to true

		// check if user_ldap is enabled, and the required classes exist
		if ($this->appManager->isAppLoaded('user_ldap')
			&& class_exists(Helper::class)) {
			$helper = \OCP\Server::get(Helper::class);
			$allPrefixes = $helper->getServerConfigurationPrefixes();
			// check each LDAP server the user is connected too
			foreach ($allPrefixes as $prefix) {
				$emailConfig = new Configuration($prefix);
				array_push($emailStates, $emailConfig->__get('ldapLoginFilterEmail'));
			}
		}
		$this->initialState->provideInitialState('emailStates', $emailStates);
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
			if (!str_contains($location, '@')) {
				return new RedirectResponse($location);
			}
		}
		return new RedirectResponse($this->urlGenerator->linkToDefaultPageUrl());
	}

	#[NoCSRFRequired]
	#[PublicPage]
	#[BruteForceProtection(action: 'login')]
	#[UseSession]
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'POST', url: '/login')]
	public function tryLogin(
		Chain $loginChain,
		ITrustedDomainHelper $trustedDomainHelper,
		string $user = '',
		string $password = '',
		?string $redirect_url = null,
		string $timezone = '',
		string $timezone_offset = '',
	): RedirectResponse {
		$error = '';

		$origin = $this->request->getHeader('Origin');
		$throttle = true;
		if ($origin === '' || !$trustedDomainHelper->isTrustedUrl($origin)) {
			// Login attempt not from the same origin,
			// We only allow this on the login flow but not on the UI login page.
			// This could have come from someone malicious who tries to block a user by triggering the bruteforce protection.
			$error = self::LOGIN_MSG_INVALID_ORIGIN;
			$throttle = false;
		} elseif (!$this->request->passesCSRFCheck()) {
			if ($this->userSession->isLoggedIn()) {
				// If the user is already logged in and the CSRF check does not pass then
				// simply redirect the user to the correct page as required. This is the
				// case when a user has already logged-in, in another tab.
				return $this->generateRedirect($redirect_url);
			}
			$error = self::LOGIN_MSG_CSRFCHECKFAILED;
		}

		if ($error !== '') {
			// Clear any auth remnants like cookies to ensure a clean login
			// For the next attempt
			$this->userSession->logout();
			return $this->createLoginFailedResponse(
				$user,
				$user,
				$redirect_url,
				$error,
				$throttle,
			);
		}

		$user = trim($user);

		if (strlen($user) > 255) {
			return $this->createLoginFailedResponse(
				$user,
				$user,
				$redirect_url,
				$this->l10n->t('Unsupported email length (>255)')
			);
		}

		$data = new LoginData(
			$this->request,
			$user,
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
		$user,
		$originalUser,
		$redirect_url,
		string $loginMessage,
		bool $throttle = true,
	) {
		// Read current user and append if possible we need to
		// return the unmodified user otherwise we will leak the login name
		$args = $user !== null ? ['user' => $originalUser, 'direct' => 1] : [];
		if ($redirect_url !== null) {
			$args['redirect_url'] = $redirect_url;
		}
		$response = new RedirectResponse(
			$this->urlGenerator->linkToRoute('core.login.showLoginForm', $args)
		);
		if ($throttle) {
			$response->throttle(['user' => substr($user, 0, 64)]);
		}
		$this->session->set('loginMessages', [
			[$loginMessage], []
		]);

		return $response;
	}

	/**
	 * Confirm the user password
	 *
	 * @license GNU AGPL version 3 or any later version
	 *
	 * @param string $password The password of the user
	 *
	 * @return DataResponse<Http::STATUS_OK, array{lastLogin: int}, array{}>|DataResponse<Http::STATUS_FORBIDDEN, list<empty>, array{}>
	 *
	 * 200: Password confirmation succeeded
	 * 403: Password confirmation failed
	 */
	#[NoAdminRequired]
	#[BruteForceProtection(action: 'sudo')]
	#[UseSession]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'POST', url: '/login/confirm')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function confirmPassword(string $password): DataResponse {
		$loginName = $this->userSession->getLoginName();
		$loginResult = $this->userManager->checkPassword($loginName, $password);
		if ($loginResult === false) {
			$response = new DataResponse([], Http::STATUS_FORBIDDEN);
			$response->throttle(['loginName' => $loginName]);
			return $response;
		}

		$confirmTimestamp = time();
		$this->session->set('last-password-confirm', $confirmTimestamp);
		$this->throttler->resetDelay($this->request->getRemoteAddress(), 'sudo', ['loginName' => $loginName]);
		return new DataResponse(['lastLogin' => $confirmTimestamp], Http::STATUS_OK);
	}
}

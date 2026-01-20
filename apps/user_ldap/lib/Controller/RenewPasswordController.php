<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Controller;

use OCA\User_LDAP\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Config\IUserConfig;
use OCP\HintException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Util;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class RenewPasswordController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IUserManager $userManager,
		private IConfig $config,
		private IUserConfig $userConfig,
		protected IL10N $l10n,
		private ISession $session,
		private IURLGenerator $urlGenerator,
		private IInitialState $initialState,
	) {
		parent::__construct($appName, $request);
	}

	#[PublicPage]
	#[NoCSRFRequired]
	public function cancel(): RedirectResponse {
		return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('core.login.showLoginForm'));
	}

	#[PublicPage]
	#[NoCSRFRequired]
	#[UseSession]
	public function showRenewPasswordForm(string $user): TemplateResponse|RedirectResponse {
		if (!$this->userConfig->getValueBool($user, 'user_ldap', 'needsPasswordReset')) {
			return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('core.login.showLoginForm'));
		}

		$renewPasswordMessages = $this->session->get('renewPasswordMessages');
		$errors = [];
		$messages = [];
		if (is_array($renewPasswordMessages)) {
			[$errors, $messages] = $renewPasswordMessages;
		}
		$this->session->remove('renewPasswordMessages');

		$this->initialState->provideInitialState('renewPasswordParameters',
			[
				'user' => $user,
				'errors' => $errors,
				'messages' => $messages,
				'cancelRenewUrl' => $this->urlGenerator->linkToRouteAbsolute('core.login.showLoginForm'),
				'tryRenewPasswordUrl' => $this->urlGenerator->linkToRouteAbsolute('user_ldap.renewPassword.tryRenewPassword'),
			],
		);

		Util::addStyle(Application::APP_ID, 'renewPassword');
		Util::addScript(Application::APP_ID, 'renewPassword');
		return new TemplateResponse(
			Application::APP_ID,
			'renewpassword',
			renderAs: 'guest',
		);
	}

	#[PublicPage]
	#[UseSession]
	public function tryRenewPassword(?string $user, string $oldPassword, ?string $newPassword): RedirectResponse {
		if ($user !== null && !$this->userConfig->getValueBool($user, 'user_ldap', 'needsPasswordReset')) {
			return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('core.login.showLoginForm'));
		}
		$args = !is_null($user) ? ['user' => $user] : [];
		$loginResult = $this->userManager->checkPassword($user, $oldPassword);
		if ($loginResult === false) {
			$this->session->set('renewPasswordMessages', [
				['invalidpassword'], []
			]);
			return new RedirectResponse($this->urlGenerator->linkToRoute('user_ldap.renewPassword.showRenewPasswordForm', $args));
		}

		try {
			if (!is_null($newPassword) && \OC_User::setPassword($user, $newPassword)) {
				$this->session->set('loginMessages', [
					[], [$this->l10n->t('Please login with the new password')]
				]);
				$this->userConfig->setValueBool($user, 'user_ldap', 'needsPasswordReset', false);
				return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm', $args));
			} else {
				$this->session->set('renewPasswordMessages', [
					['internalexception'], []
				]);
			}
		} catch (HintException $e) {
			$this->session->set('renewPasswordMessages', [
				[], [$e->getHint()]
			]);
		}

		return new RedirectResponse($this->urlGenerator->linkToRoute('user_ldap.renewPassword.showRenewPasswordForm', $args));
	}

	#[PublicPage]
	#[NoCSRFRequired]
	#[UseSession]
	public function showLoginFormInvalidPassword(?string $user): RedirectResponse {
		$args = !is_null($user) ? ['user' => $user] : [];
		$this->session->set('loginMessages', [
			['invalidpassword'], []
		]);
		return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm', $args));
	}
}

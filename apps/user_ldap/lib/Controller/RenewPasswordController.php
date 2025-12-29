<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Config\IUserConfig;
use OCP\HintException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;

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
		$parameters = [];
		$renewPasswordMessages = $this->session->get('renewPasswordMessages');
		$errors = [];
		$messages = [];
		if (is_array($renewPasswordMessages)) {
			[$errors, $messages] = $renewPasswordMessages;
		}
		$this->session->remove('renewPasswordMessages');
		foreach ($errors as $value) {
			$parameters[$value] = true;
		}

		$parameters['messages'] = $messages;
		$parameters['user'] = $user;

		$parameters['canResetPassword'] = true;
		$parameters['resetPasswordLink'] = $this->config->getSystemValue('lost_password_link', '');
		if (!$parameters['resetPasswordLink']) {
			$userObj = $this->userManager->get($user);
			if ($userObj instanceof IUser) {
				$parameters['canResetPassword'] = $userObj->canChangePassword();
			}
		}
		$parameters['cancelLink'] = $this->urlGenerator->linkToRouteAbsolute('core.login.showLoginForm');

		return new TemplateResponse(
			$this->appName, 'renewpassword', $parameters, 'guest'
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

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework;

use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;

/**
 * Base controller for interactive public shares
 *
 * It will verify if the user is properly authenticated to the share. If not the
 * user will be redirected to an authentication page.
 *
 * Use this for a controller that is to be called directly by a user. So the
 * normal public share page for files/calendars etc.
 *
 * @since 14.0.0
 */
abstract class AuthPublicShareController extends PublicShareController {
	/** @var IURLGenerator */
	protected $urlGenerator;

	/**
	 * @since 14.0.0
	 */
	public function __construct(string $appName,
		IRequest $request,
		ISession $session,
		IURLGenerator $urlGenerator) {
		parent::__construct($appName, $request, $session);

		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Show the authentication page
	 * The form has to submit to the authenticate method route
	 *
	 * @since 14.0.0
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	public function showAuthenticate(): TemplateResponse {
		return new TemplateResponse('core', 'publicshareauth', [], 'guest');
	}

	/**
	 * The template to show when authentication failed
	 *
	 * @since 14.0.0
	 */
	protected function showAuthFailed(): TemplateResponse {
		return new TemplateResponse('core', 'publicshareauth', ['wrongpw' => true], 'guest');
	}

	/**
	 * The template to show after user identification
	 *
	 * @since 24.0.0
	 */
	protected function showIdentificationResult(bool $success): TemplateResponse {
		return new TemplateResponse('core', 'publicshareauth', ['identityOk' => $success], 'guest');
	}

	/**
	 * Validates that the provided identity is allowed to receive a temporary password
	 *
	 * @since 24.0.0
	 */
	protected function validateIdentity(?string $identityToken = null): bool {
		return false;
	}

	/**
	 * Generates a password
	 *
	 * @since 24.0.0
	 */
	protected function generatePassword(): void {
	}

	/**
	 * Verify the password
	 *
	 * @since 24.0.0
	 */
	protected function verifyPassword(string $password): bool {
		return false;
	}

	/**
	 * Function called after failed authentication
	 *
	 * You can use this to do some logging for example
	 *
	 * @since 14.0.0
	 */
	protected function authFailed() {
	}

	/**
	 * Function called after successful authentication
	 *
	 * You can use this to do some logging for example
	 *
	 * @since 14.0.0
	 */
	protected function authSucceeded() {
	}

	/**
	 * Authenticate the share
	 *
	 * @since 14.0.0
	 */
	#[BruteForceProtection(action: 'publicLinkAuth')]
	#[PublicPage]
	#[UseSession]
	final public function authenticate(string $password = '', string $passwordRequest = 'no', string $identityToken = '') {
		// Already authenticated
		if ($this->isAuthenticated()) {
			return $this->getRedirect();
		}

		// Is user requesting a temporary password?
		if ($passwordRequest == '') {
			if ($this->validateIdentity($identityToken)) {
				$this->generatePassword();
				$response = $this->showIdentificationResult(true);
				return $response;
			} else {
				$response = $this->showIdentificationResult(false);
				$response->throttle();
				return $response;
			}
		}

		if (!$this->verifyPassword($password)) {
			$this->authFailed();
			$response = $this->showAuthFailed();
			$response->throttle();
			return $response;
		}

		$this->session->regenerateId(true, true);
		$response = $this->getRedirect();

		$this->session->set('public_link_authenticated_token', $this->getToken());
		$this->session->set('public_link_authenticated_password_hash', $this->getPasswordHash());

		$this->authSucceeded();

		return $response;
	}

	/**
	 * Default landing page
	 *
	 * @since 14.0.0
	 */
	abstract public function showShare(): TemplateResponse;

	/**
	 * @since 14.0.0
	 */
	final public function getAuthenticationRedirect(string $redirect): RedirectResponse {
		return new RedirectResponse(
			$this->urlGenerator->linkToRoute($this->getRoute('showAuthenticate'), ['token' => $this->getToken(), 'redirect' => $redirect])
		);
	}


	/**
	 * @since 14.0.0
	 */
	private function getRoute(string $function): string {
		$app = strtolower($this->appName);
		$class = (new \ReflectionClass($this))->getShortName();
		if (str_ends_with($class, 'Controller')) {
			$class = substr($class, 0, -10);
		}
		return $app . '.' . $class . '.' . $function;
	}

	/**
	 * @since 14.0.0
	 */
	private function getRedirect(): RedirectResponse {
		//Get all the stored redirect parameters:
		$params = $this->session->get('public_link_authenticate_redirect');

		$route = $this->getRoute('showShare');

		if ($params === null) {
			$params = [
				'token' => $this->getToken(),
			];
		} else {
			$params = json_decode($params, true);
			if (isset($params['_route'])) {
				$route = $params['_route'];
				unset($params['_route']);
			}

			// If the token doesn't match the rest of the arguments can't be trusted either
			if (isset($params['token']) && $params['token'] !== $this->getToken()) {
				$params = [
					'token' => $this->getToken(),
				];
			}

			// We need a token
			if (!isset($params['token'])) {
				$params = [
					'token' => $this->getToken(),
				];
			}
		}

		return new RedirectResponse($this->urlGenerator->linkToRoute($route, $params));
	}
}

<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Cornelius Kölbel <cornelius.koelbel@netknights.it>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Controller;

use OC\Authentication\TwoFactorAuth\Manager;
use OC_User;
use OC_Util;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Authentication\TwoFactorAuth\IProvidesCustomCSP;
use OCP\Authentication\TwoFactorAuth\TwoFactorException;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;

class TwoFactorChallengeController extends Controller {

	/** @var Manager */
	private $twoFactorManager;

	/** @var IUserSession */
	private $userSession;

	/** @var ISession */
	private $session;

	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param Manager $twoFactorManager
	 * @param IUserSession $userSession
	 * @param ISession $session
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct($appName, IRequest $request, Manager $twoFactorManager, IUserSession $userSession,
		ISession $session, IURLGenerator $urlGenerator) {
		parent::__construct($appName, $request);
		$this->twoFactorManager = $twoFactorManager;
		$this->userSession = $userSession;
		$this->session = $session;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @return string
	 */
	protected function getLogoutUrl() {
		return OC_User::getLogoutUrl($this->urlGenerator);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $redirect_url
	 * @return TemplateResponse
	 */
	public function selectChallenge($redirect_url) {
		$user = $this->userSession->getUser();
		$providers = $this->twoFactorManager->getProviders($user);
		$backupProvider = $this->twoFactorManager->getBackupProvider($user);

		$data = [
			'providers' => $providers,
			'backupProvider' => $backupProvider,
			'redirect_url' => $redirect_url,
			'logout_url' => $this->getLogoutUrl(),
		];
		return new TemplateResponse($this->appName, 'twofactorselectchallenge', $data, 'guest');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @UseSession
	 *
	 * @param string $challengeProviderId
	 * @param string $redirect_url
	 * @return TemplateResponse|RedirectResponse
	 */
	public function showChallenge($challengeProviderId, $redirect_url) {
		$user = $this->userSession->getUser();
		$provider = $this->twoFactorManager->getProvider($user, $challengeProviderId);
		if (is_null($provider)) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('core.TwoFactorChallenge.selectChallenge'));
		}

		$backupProvider = $this->twoFactorManager->getBackupProvider($user);
		if (!is_null($backupProvider) && $backupProvider->getId() === $provider->getId()) {
			// Don't show the backup provider link if we're already showing that provider's challenge
			$backupProvider = null;
		}

		$errorMessage = '';
		$error = false;
		if ($this->session->exists('two_factor_auth_error')) {
			$this->session->remove('two_factor_auth_error');
			$error = true;
			$errorMessage = $this->session->get("two_factor_auth_error_message");
			$this->session->remove('two_factor_auth_error_message');
		}
		$tmpl = $provider->getTemplate($user);
		$tmpl->assign('redirect_url', $redirect_url);
		$data = [
			'error' => $error,
			'error_message' => $errorMessage,
			'provider' => $provider,
			'backupProvider' => $backupProvider,
			'logout_url' => $this->getLogoutUrl(),
			'redirect_url' => $redirect_url,
			'template' => $tmpl->fetchPage(),
		];
		$response = new TemplateResponse($this->appName, 'twofactorshowchallenge', $data, 'guest');
		if ($provider instanceof IProvidesCustomCSP) {
			$response->setContentSecurityPolicy($provider->getCSP());
		}
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @UseSession
	 *
	 * @UserRateThrottle(limit=5, period=100)
	 *
	 * @param string $challengeProviderId
	 * @param string $challenge
	 * @param string $redirect_url
	 * @return RedirectResponse
	 */
	public function solveChallenge($challengeProviderId, $challenge, $redirect_url = null) {
		$user = $this->userSession->getUser();
		$provider = $this->twoFactorManager->getProvider($user, $challengeProviderId);
		if (is_null($provider)) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('core.TwoFactorChallenge.selectChallenge'));
		}

		try {
			if ($this->twoFactorManager->verifyChallenge($challengeProviderId, $user, $challenge)) {
				if (!is_null($redirect_url)) {
					return new RedirectResponse($this->urlGenerator->getAbsoluteURL(urldecode($redirect_url)));
				}
				return new RedirectResponse(OC_Util::getDefaultPageUrl());
			}
		} catch (TwoFactorException $e) {
			/*
			 * The 2FA App threw an TwoFactorException. Now we display more
			 * information to the user. The exception text is stored in the
			 * session to be used in showChallenge()
			 */
			$this->session->set('two_factor_auth_error_message', $e->getMessage());
		}

		$this->session->set('two_factor_auth_error', true);
		return new RedirectResponse($this->urlGenerator->linkToRoute('core.TwoFactorChallenge.showChallenge', [
			'challengeProviderId' => $provider->getId(),
			'redirect_url' => $redirect_url,
		]));
	}

}

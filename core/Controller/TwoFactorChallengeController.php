<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Cornelius Kölbel <cornelius.koelbel@netknights.it>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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

use OC\Authentication\TwoFactorAuth\Manager;
use OC_User;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\Authentication\TwoFactorAuth\IActivatableAtLogin;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IProvidesCustomCSP;
use OCP\Authentication\TwoFactorAuth\TwoFactorException;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class TwoFactorChallengeController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private Manager $twoFactorManager,
		private IUserSession $userSession,
		private ISession $session,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return string
	 */
	protected function getLogoutUrl() {
		return OC_User::getLogoutUrl($this->urlGenerator);
	}

	/**
	 * @param IProvider[] $providers
	 */
	private function splitProvidersAndBackupCodes(array $providers): array {
		$regular = [];
		$backup = null;
		foreach ($providers as $provider) {
			if ($provider->getId() === 'backup_codes') {
				$backup = $provider;
			} else {
				$regular[] = $provider;
			}
		}

		return [$regular, $backup];
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TwoFactorSetUpDoneRequired
	 *
	 * @param string $redirect_url
	 * @return StandaloneTemplateResponse
	 */
	public function selectChallenge($redirect_url) {
		$user = $this->userSession->getUser();
		$providerSet = $this->twoFactorManager->getProviderSet($user);
		$allProviders = $providerSet->getProviders();
		[$providers, $backupProvider] = $this->splitProvidersAndBackupCodes($allProviders);
		$setupProviders = $this->twoFactorManager->getLoginSetupProviders($user);

		$data = [
			'providers' => $providers,
			'backupProvider' => $backupProvider,
			'providerMissing' => $providerSet->isProviderMissing(),
			'redirect_url' => $redirect_url,
			'logout_url' => $this->getLogoutUrl(),
			'hasSetupProviders' => !empty($setupProviders),
		];
		return new StandaloneTemplateResponse($this->appName, 'twofactorselectchallenge', $data, 'guest');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TwoFactorSetUpDoneRequired
	 *
	 * @param string $challengeProviderId
	 * @param string $redirect_url
	 * @return StandaloneTemplateResponse|RedirectResponse
	 */
	#[UseSession]
	public function showChallenge($challengeProviderId, $redirect_url) {
		$user = $this->userSession->getUser();
		$providerSet = $this->twoFactorManager->getProviderSet($user);
		$provider = $providerSet->getProvider($challengeProviderId);

		if (is_null($provider)) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('core.TwoFactorChallenge.selectChallenge'));
		}

		$backupProvider = $providerSet->getProvider('backup_codes');
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
		$response = new StandaloneTemplateResponse($this->appName, 'twofactorshowchallenge', $data, 'guest');
		if ($provider instanceof IProvidesCustomCSP) {
			$response->setContentSecurityPolicy($provider->getCSP());
		}
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TwoFactorSetUpDoneRequired
	 *
	 * @UserRateThrottle(limit=5, period=100)
	 *
	 * @param string $challengeProviderId
	 * @param string $challenge
	 * @param string $redirect_url
	 * @return RedirectResponse
	 */
	#[UseSession]
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
				return new RedirectResponse($this->urlGenerator->linkToDefaultPageUrl());
			}
		} catch (TwoFactorException $e) {
			/*
			 * The 2FA App threw an TwoFactorException. Now we display more
			 * information to the user. The exception text is stored in the
			 * session to be used in showChallenge()
			 */
			$this->session->set('two_factor_auth_error_message', $e->getMessage());
		}

		$ip = $this->request->getRemoteAddress();
		$uid = $user->getUID();
		$this->logger->warning("Two-factor challenge failed: $uid (Remote IP: $ip)");
		$this->session->set('two_factor_auth_error', true);
		return new RedirectResponse($this->urlGenerator->linkToRoute('core.TwoFactorChallenge.showChallenge', [
			'challengeProviderId' => $provider->getId(),
			'redirect_url' => $redirect_url,
		]));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function setupProviders(): StandaloneTemplateResponse {
		$user = $this->userSession->getUser();
		$setupProviders = $this->twoFactorManager->getLoginSetupProviders($user);

		$data = [
			'providers' => $setupProviders,
			'logout_url' => $this->getLogoutUrl(),
		];

		return new StandaloneTemplateResponse($this->appName, 'twofactorsetupselection', $data, 'guest');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function setupProvider(string $providerId) {
		$user = $this->userSession->getUser();
		$providers = $this->twoFactorManager->getLoginSetupProviders($user);

		$provider = null;
		foreach ($providers as $p) {
			if ($p->getId() === $providerId) {
				$provider = $p;
				break;
			}
		}

		if ($provider === null) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('core.TwoFactorChallenge.selectChallenge'));
		}

		/** @var IActivatableAtLogin $provider */
		$tmpl = $provider->getLoginSetup($user)->getBody();
		$data = [
			'provider' => $provider,
			'logout_url' => $this->getLogoutUrl(),
			'template' => $tmpl->fetchPage(),
		];
		$response = new StandaloneTemplateResponse($this->appName, 'twofactorsetupchallenge', $data, 'guest');
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @todo handle the extreme edge case of an invalid provider ID and redirect to the provider selection page
	 */
	public function confirmProviderSetup(string $providerId) {
		return new RedirectResponse($this->urlGenerator->linkToRoute(
			'core.TwoFactorChallenge.showChallenge',
			[
				'challengeProviderId' => $providerId,
			]
		));
	}
}

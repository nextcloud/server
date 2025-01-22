<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\Authentication\TwoFactorAuth\Manager;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IURLGenerator;
use function array_pop;
use function count;

class TwoFactorCommand extends ALoginCommand {

	public function __construct(
		private Manager $twoFactorManager,
		private MandatoryTwoFactor $mandatoryTwoFactor,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function process(LoginData $loginData): LoginResult {
		$loginDataUser = $loginData->getUser();
		if (!$this->twoFactorManager->isTwoFactorAuthenticated($loginDataUser)) {
			return $this->processNextOrFinishSuccessfully($loginData);
		}

		$this->twoFactorManager->prepareTwoFactorLogin($loginDataUser, $loginData->isRememberLogin());

		$providerSet = $this->twoFactorManager->getProviderSet($loginDataUser);
		$loginProviders = $this->twoFactorManager->getLoginSetupProviders($loginDataUser);
		$providers = $providerSet->getPrimaryProviders();
		if (empty($providers)
			&& !$providerSet->isProviderMissing()
			&& !empty($loginProviders)
			&& $this->mandatoryTwoFactor->isEnforcedFor($loginDataUser)) {
			// No providers set up, but 2FA is enforced and setup providers are available
			$url = 'core.TwoFactorChallenge.setupProviders';
			$urlParams = [];
		} elseif (!$providerSet->isProviderMissing() && count($providers) === 1) {
			// Single provider (and no missing ones), hence we can redirect to that provider's challenge page directly
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

		if ($loginData->getRedirectUrl() !== null) {
			$urlParams['redirect_url'] = $loginData->getRedirectUrl();
		}

		return LoginResult::success(
			$loginData,
			$this->urlGenerator->linkToRoute($url, $urlParams)
		);
	}
}

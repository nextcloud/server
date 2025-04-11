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
	/** @var Manager */
	private $twoFactorManager;

	/** @var MandatoryTwoFactor */
	private $mandatoryTwoFactor;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(Manager $twoFactorManager,
		MandatoryTwoFactor $mandatoryTwoFactor,
		IURLGenerator $urlGenerator) {
		$this->twoFactorManager = $twoFactorManager;
		$this->mandatoryTwoFactor = $mandatoryTwoFactor;
		$this->urlGenerator = $urlGenerator;
	}

	public function process(LoginData $loginData): LoginResult {
		if (!$this->twoFactorManager->isTwoFactorAuthenticated($loginData->getUser())) {
			return $this->processNextOrFinishSuccessfully($loginData);
		}

		$this->twoFactorManager->prepareTwoFactorLogin($loginData->getUser(), $loginData->isRememberLogin());

		$providerSet = $this->twoFactorManager->getProviderSet($loginData->getUser());
		$loginProviders = $this->twoFactorManager->getLoginSetupProviders($loginData->getUser());
		$providers = $providerSet->getPrimaryProviders();
		if (empty($providers)
			&& !$providerSet->isProviderMissing()
			&& !empty($loginProviders)
			&& $this->mandatoryTwoFactor->isEnforcedFor($loginData->getUser())) {
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

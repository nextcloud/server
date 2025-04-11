<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Personal\Security;

use Exception;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OC\Authentication\TwoFactorAuth\ProviderLoader;
use OCA\TwoFactorBackupCodes\Provider\BackupCodesProvider;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IProvidesPersonalSettings;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use function array_filter;
use function array_map;
use function is_null;

class TwoFactor implements ISettings {

	/** @var ProviderLoader */
	private $providerLoader;

	/** @var MandatoryTwoFactor */
	private $mandatoryTwoFactor;

	public function __construct(
		ProviderLoader $providerLoader,
		MandatoryTwoFactor $mandatoryTwoFactor,
		private IUserSession $userSession,
		private IConfig $config,
		private ?string $userId,
	) {
		$this->providerLoader = $providerLoader;
		$this->mandatoryTwoFactor = $mandatoryTwoFactor;
	}

	public function getForm(): TemplateResponse {
		return new TemplateResponse('settings', 'settings/personal/security/twofactor', [
			'twoFactorProviderData' => $this->getTwoFactorProviderData(),
		]);
	}

	public function getSection(): ?string {
		if (!$this->shouldShow()) {
			return null;
		}
		return 'security';
	}

	public function getPriority(): int {
		return 15;
	}

	private function shouldShow(): bool {
		$user = $this->userSession->getUser();
		if (is_null($user)) {
			// Actually impossible, but still …
			return false;
		}

		// Anyone who's supposed to use 2FA should see 2FA settings
		if ($this->mandatoryTwoFactor->isEnforcedFor($user)) {
			return true;
		}

		// If there is at least one provider with personal settings but it's not
		// the backup codes provider, then these settings should show.
		try {
			$providers = $this->providerLoader->getProviders($user);
		} catch (Exception $e) {
			// Let's hope for the best
			return true;
		}
		foreach ($providers as $provider) {
			if ($provider instanceof IProvidesPersonalSettings
				&& !($provider instanceof BackupCodesProvider)) {
				return true;
			}
		}
		return false;
	}

	private function getTwoFactorProviderData(): array {
		$user = $this->userSession->getUser();
		if (is_null($user)) {
			// Actually impossible, but still …
			return [];
		}

		return [
			'providers' => array_map(function (IProvidesPersonalSettings $provider) use ($user) {
				return [
					'provider' => $provider,
					'settings' => $provider->getPersonalSettings($user)
				];
			}, array_filter($this->providerLoader->getProviders($user), function (IProvider $provider) {
				return $provider instanceof IProvidesPersonalSettings;
			}))
		];
	}
}

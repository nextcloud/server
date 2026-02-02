<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OC\Authentication\TwoFactorAuth\ProviderLoader;
use OC\Authentication\TwoFactorAuth\ProviderSet;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class TwoFactorConfiguration implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private ProviderLoader $providerLoader,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Second factor configuration');
	}

	public function getCategory(): string {
		return 'security';
	}

	public function run(): SetupResult {
		$providers = $this->providerLoader->getProviders();
		$providerSet = new ProviderSet($providers, false);
		$primaryProviders = $providerSet->getPrimaryProviders();
		if (count($primaryProviders) === 0) {
			return SetupResult::warning($this->l10n->t('This instance has no second factor provider available.'));
		} else {
			return SetupResult::success(
				$this->l10n->t(
					'Second factor providers are available: %s.',
					[
						implode(', ', array_map(
							fn ($p) => '"' . $p->getDisplayName() . '"',
							$primaryProviders)
						)
					]
				)
			);
		}
	}
}

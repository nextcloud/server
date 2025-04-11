<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class DefaultPhoneRegionSet implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Default phone region');
	}

	public function getCategory(): string {
		return 'config';
	}

	public function run(): SetupResult {
		if ($this->config->getSystemValueString('default_phone_region', '') !== '') {
			return SetupResult::success($this->config->getSystemValueString('default_phone_region', ''));
		} else {
			return SetupResult::info(
				$this->l10n->t('Your installation has no default phone region set. This is required to validate phone numbers in the profile settings without a country code. To allow numbers without a country code, please add "default_phone_region" with the respective ISO 3166-1 code of the region to your config file.'),
				'https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements'
			);
		}
	}
}

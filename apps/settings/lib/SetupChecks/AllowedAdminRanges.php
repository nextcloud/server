<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use IPLib\Factory;
use OC\Security\RemoteIpAddress;
use OCP\IConfig;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class AllowedAdminRanges implements ISetupCheck {
	public function __construct(
		private IConfig $config,
		private IL10N $l10n,
	) {
	}

	public function getCategory(): string {
		return 'system';
	}

	public function getName(): string {
		return $this->l10n->t('Allowed admin IP ranges');
	}

	public function run(): SetupResult {
		$allowedAdminRanges = $this->config->getSystemValue(RemoteIpAddress::SETTING_NAME, false);
		if ($allowedAdminRanges === false) {
			return SetupResult::success($this->l10n->t('Admin IP filtering isn’t applied.'));
		}

		if (!is_array($allowedAdminRanges)) {
			return SetupResult::error(
				$this->l10n->t(
					'Configuration key "%1$s" expects an array (%2$s found). Admin IP range validation will not be applied.',
					[RemoteIpAddress::SETTING_NAME, gettype($allowedAdminRanges)],
				)
			);
		}

		$invalidRanges = array_reduce($allowedAdminRanges, static function (array $carry, mixed $range) {
			if (!is_string($range) || Factory::parseRangeString($range) === null) {
				$carry[] = $range;
			}

			return $carry;
		}, []);
		if (count($invalidRanges) > 0) {
			return SetupResult::warning(
				$this->l10n->t(
					'Configuration key "%1$s" contains invalid IP range(s): "%2$s"',
					[RemoteIpAddress::SETTING_NAME, implode('", "', $invalidRanges)],
				),
			);
		}

		return SetupResult::success($this->l10n->t('Admin IP filtering is correctly configured.'));
	}
}

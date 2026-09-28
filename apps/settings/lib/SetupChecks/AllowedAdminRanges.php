<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OC\Security\Ip\Range;
use OC\Security\Ip\RemoteAddress;
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
		$allowedAdminRanges = $this->config->getSystemValue(RemoteAddress::SETTING_NAME, false);
		if (
			$allowedAdminRanges === false
			|| (is_array($allowedAdminRanges) && empty($allowedAdminRanges))
		) {
			return SetupResult::success($this->l10n->t('Admin IP filtering isn\'t applied.'));
		}

		if (!is_array($allowedAdminRanges)) {
			return SetupResult::error(
				$this->l10n->t(
					'Configuration key "%1$s" expects an array (%2$s found). Admin IP range validation will not be applied.',
					[RemoteAddress::SETTING_NAME, gettype($allowedAdminRanges)],
				)
			);
		}

		$invalidRanges = array_filter($allowedAdminRanges, static fn (mixed $range): bool => !is_string($range) || !Range::isValid($range));
		if (!empty($invalidRanges)) {
			return SetupResult::warning(
				$this->l10n->t(
					'Configuration key "%1$s" contains invalid IP range(s): "%2$s"',
					[RemoteAddress::SETTING_NAME, implode('", "', $invalidRanges)],
				),
			);
		}

		return SetupResult::success($this->l10n->t('Admin IP filtering is correctly configured.'));
	}
}

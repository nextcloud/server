<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpOutdated implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function getCategory(): string {
		return 'security';
	}

	public function getName(): string {
		return $this->l10n->t('PHP version');
	}

	public function run(): SetupResult {
		if (PHP_VERSION_ID < 80200) {
			return SetupResult::warning($this->l10n->t('PHP %s detected. PHP >= 8.2 and <= 8.3 is suggested for optimal performance, security, and stability with this version of Nextcloud. Additionally, the next major release of Nextcloud will likely require >= PHP 8.2.', [PHP_VERSION]), 'https://secure.php.net/supported-versions.php');
		}
		return SetupResult::success($this->l10n->t('You are currently running PHP %s.', [PHP_VERSION]));
	}
}

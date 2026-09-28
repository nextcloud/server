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
	public const DEPRECATED_PHP_VERSION = '8.1';
	public const DEPRECATED_SINCE = '30';
	public const FUTURE_REQUIRED_PHP_VERSION = '8.2';
	public const FUTURE_REQUIRED_STARTING = '32';

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
			return SetupResult::warning($this->l10n->t('You are currently running PHP %1$s. PHP %2$s is deprecated since Nextcloud %3$s. Nextcloud %4$s may require at least PHP %5$s. Please upgrade to one of the officially supported PHP versions provided by the PHP Group as soon as possible.', [
				PHP_VERSION,
				self::DEPRECATED_PHP_VERSION,
				self::DEPRECATED_SINCE,
				self::FUTURE_REQUIRED_STARTING,
				self::FUTURE_REQUIRED_PHP_VERSION,
			]), 'https://secure.php.net/supported-versions.php');
		}
		return SetupResult::success($this->l10n->t('You are currently running PHP %s.', [PHP_VERSION]));
	}
}

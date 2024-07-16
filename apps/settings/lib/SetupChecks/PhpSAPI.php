<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpSAPI implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('PHP SAPI');
	}

	public function getCategory(): string {
		return 'php';
	}

	public function run(): SetupResult {
		$sapi_type = php_sapi_name();

		if ($sapi_type === 'fpm-fcgi') {
			$sapi_max_children_reached = fpm_get_status()['max-children-reached']; // since last restart
			if ($sapi_max_children_reached === 1) {
				$sapi_max_children_reached_actual = fpm_get_status()['max-active-processes'];
				return SetupResult::error($this->l10n->t('Your PHP-FPM pool reached it\'s maximum number of allowed processes (' . $sapi_max_children_reached_actual . ') at least once since your last restart. You may want to increase your pm.max_children value in your PHP-FPM pool configuration to avoid problems such as Gateway Timeouts, client connection errors, and slow performance.'), $this->urlGenerator->linkToDocs('admin-php-fpm'));
			}
		}

		return SetupResult::success();
	}
}

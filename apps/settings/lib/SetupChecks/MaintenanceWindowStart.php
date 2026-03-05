<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class MaintenanceWindowStart implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IConfig $config,
	) {
	}

	public function getCategory(): string {
		return 'system';
	}

	public function getName(): string {
		return $this->l10n->t('Maintenance window start');
	}

	public function run(): SetupResult {
		$configValue = $this->config->getSystemValue('maintenance_window_start', null);
		if ($configValue === null) {
			return SetupResult::warning(
				$this->l10n->t('Server has no maintenance window start time configured. This means resource intensive daily background jobs will also be executed during your main usage time. We recommend to set it to a time of low usage, so users are less impacted by the load caused from these heavy tasks.'),
				$this->urlGenerator->linkToDocs('admin-background-jobs')
			);
		}

		$startValue = (int)$configValue;
		$endValue = ($startValue + 6) % 24;
		return SetupResult::success(
			str_replace(
				['{start}', '{end}'],
				[$startValue, $endValue],
				$this->l10n->t('Maintenance window to execute heavy background jobs is between {start}:00 UTC and {end}:00 UTC')
			)
		);
	}
}

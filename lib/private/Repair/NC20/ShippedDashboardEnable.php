<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair\NC20;

use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ShippedDashboardEnable implements IRepairStep {
	public function __construct(
		private readonly IConfig $config,
		private IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Remove old dashboard app config data';
	}

	#[\Override]
	public function run(IOutput $output): void {
		$version = $this->appConfig->getValue('dashboard', 'version', '7.0.0');
		if (version_compare($version, '7.0.0', '<')) {
			$this->appConfig->deleteApp('dashboard');
			$output->info('Removed old dashboard app config');
		}
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC20;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ShippedDashboardEnable implements IRepairStep {
	public function __construct(
		private IConfig $config,
	) {
	}

	public function getName() {
		return 'Remove old dashboard app config data';
	}

	public function run(IOutput $output) {
		$version = $this->config->getAppValue('dashboard', 'version', '7.0.0');
		if (version_compare($version, '7.0.0', '<')) {
			$this->config->deleteAppValues('dashboard');
			$output->info('Removed old dashboard app config');
		}
	}
}

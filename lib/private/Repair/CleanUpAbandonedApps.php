<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CleanUpAbandonedApps implements IRepairStep {
	protected const ABANDONED_APPS = ['accessibility', 'files_videoplayer'];
	private IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getName(): string {
		return 'Clean up abandoned apps';
	}

	public function run(IOutput $output): void {
		foreach (self::ABANDONED_APPS as $app) {
			// only remove global app values
			// user prefs of accessibility are dealt with in Theming migration
			// videoplayer did not have user prefs
			$this->config->deleteAppValues($app);
		}
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair;

use OC\Config\ConfigManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ConfigKeyMigration implements IRepairStep {
	public function __construct(
		private ConfigManager $configManager,
	) {
	}

	public function getName(): string {
		return 'Migrate config keys';
	}

	public function run(IOutput $output) {
		$this->configManager->migrateConfigLexiconKeys();
		$this->configManager->updateLexiconEntries('core');
	}
}

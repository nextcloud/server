<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC20;

use OCP\Encryption\IManager;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class EncryptionMigration implements IRepairStep {
	public function __construct(
		private IConfig $config,
		private IManager $manager,
	) {
	}

	public function getName(): string {
		return 'Check encryption key format';
	}

	private function shouldRun(): bool {
		$versionFromBeforeUpdate = $this->config->getSystemValueString('version', '0.0.0.0');
		return version_compare($versionFromBeforeUpdate, '20.0.0.1', '<=');
	}

	public function run(IOutput $output): void {
		if (!$this->shouldRun()) {
			return;
		}

		$masterKeyId = $this->config->getAppValue('encryption', 'masterKeyId');
		if ($this->manager->isEnabled() || !empty($masterKeyId)) {
			if ($this->config->getSystemValue('encryption.key_storage_migrated', '') === '') {
				$this->config->setSystemValue('encryption.key_storage_migrated', false);
			}
		}
	}
}

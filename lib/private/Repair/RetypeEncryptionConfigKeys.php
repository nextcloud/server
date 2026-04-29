<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair;

use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RetypeEncryptionConfigKeys implements IRepairStep {
	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Re-type encryption appconfig keys to boolean';
	}

	#[\Override]
	public function run(IOutput $output): void {
		$keys = [
			['core', 'encryption_enabled', false],
			['encryption', 'encryptHomeStorage', true],
		];

		foreach ($keys as [$app, $key, $defaultBool]) {
			try {
				$type = $this->appConfig->getValueType($app, $key);
			} catch (AppConfigUnknownKeyException) {
				continue;
			}

			if (($type & IAppConfig::VALUE_BOOL) === IAppConfig::VALUE_BOOL) {
				$output->info("$app.$key is already typed as boolean, skipping.");
				continue;
			}

			$raw = strtolower(trim($this->appConfig->getValueString($app, $key, $defaultBool ? '1' : '0')));
			$bool = in_array($raw, ['1', 'true', 'yes', 'on'], true);

			$this->appConfig->deleteKey($app, $key);
			$this->appConfig->setValueBool($app, $key, $bool);
			$output->info("Re-typed $app.$key from string '$raw' to boolean " . ($bool ? 'true' : 'false') . '.');
		}
	}
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

class AddAppConfigLazyMigration implements IRepairStep {
	/**
	 * Just add config values that needs to be migrated to lazy loading
	 */
	private static array $lazyAppConfig = [
		'core' => [
			'oc.integritycheck.checker',
		],
	];

	public function __construct(
		private IAppConfig $appConfig,
		private LoggerInterface $logger,
	) {
	}

	public function getName() {
		return 'migrate lazy config values';
	}

	public function run(IOutput $output) {
		$c = 0;
		foreach (self::$lazyAppConfig as $appId => $configKeys) {
			foreach ($configKeys as $configKey) {
				$c += (int)$this->appConfig->updateLazy($appId, $configKey, true);
			}
		}

		$this->logger->notice('core/BackgroundJobs/AppConfigLazyMigration: ' . $c . ' config values updated');
	}
}

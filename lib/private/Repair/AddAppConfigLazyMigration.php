<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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

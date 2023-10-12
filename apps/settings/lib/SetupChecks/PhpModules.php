<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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
namespace OCA\Settings\SetupChecks;

use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpModules implements ISetupCheck {
	protected const REQUIRED_MODULES = [
		'ctype',
		'curl',
		'dom',
		'fileinfo',
		'gd',
		'json',
		'mbstring',
		'openssl',
		'posix',
		'session',
		'xml',
		'xmlreader',
		'xmlwriter',
		'zip',
		'zlib',
	];
	protected const RECOMMENDED_MODULES = [
		'intl',
		'sysvsem',
		'exif',
		'sodium',
		'bz2',
	];

	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('PHP modules');
	}

	public function getCategory(): string {
		return 'system';
	}

	public function run(): SetupResult {
		$missingRecommendedModules = $this->getMissingModules(self::RECOMMENDED_MODULES);
		$missingRequiredModules = $this->getMissingModules(self::REQUIRED_MODULES);
		if (!empty($missingRequiredModules)) {
			return SetupResult::error($this->l10n->t('This instance is missing some required PHP modules. It is required to install them: %s', implode(', ', $missingRequiredModules)));
		} elseif (!empty($missingRecommendedModules)) {
			return SetupResult::info($this->l10n->t('This instance is missing some recommended PHP modules. For improved performance and better compatibility it is highly recommended to install them: %s', implode(', ', $missingRecommendedModules)));
		} else {
			return SetupResult::success();
		}
	}

	/**
	 * Checks for potential PHP modules that would improve the instance
	 *
	 * @param string[] $modules modules to test
	 * @return string[] A list of PHP modules which are missing
	 */
	protected function getMissingModules(array $modules): array {
		return array_values(array_filter(
			$modules,
			fn (string $module) => !extension_loaded($module),
		));
	}
}

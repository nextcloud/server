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

use bantu\IniGetWrapper\IniGetWrapper;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpOpcacheSetup implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IniGetWrapper $iniGetWrapper,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('PHP opcache');
	}

	public function getCategory(): string {
		return 'php';
	}

	/**
	 * Checks whether a PHP OPcache is properly set up
	 * @return array{'warning'|'error',list<string>} The level and the list of OPcache setup recommendations
	 */
	protected function getOpcacheSetupRecommendations(): array {
		$level = 'warning';

		// If the module is not loaded, return directly to skip inapplicable checks
		if (!extension_loaded('Zend OPcache')) {
			return ['error',[$this->l10n->t('The PHP OPcache module is not loaded. For better performance it is recommended to load it into your PHP installation.')]];
		}

		$recommendations = [];

		// Check whether Nextcloud is allowed to use the OPcache API
		$isPermitted = true;
		$permittedPath = (string)$this->iniGetWrapper->getString('opcache.restrict_api');
		if ($permittedPath !== '' && !str_starts_with(\OC::$SERVERROOT, rtrim($permittedPath, '/'))) {
			$isPermitted = false;
		}

		if (!$this->iniGetWrapper->getBool('opcache.enable')) {
			$recommendations[] = $this->l10n->t('OPcache is disabled. For better performance, it is recommended to apply "opcache.enable=1" to your PHP configuration.');
			$level = 'error';
		} elseif ($this->iniGetWrapper->getBool('opcache.file_cache_only')) {
			$recommendations[] = $this->l10n->t('The shared memory based OPcache is disabled. For better performance, it is recommended to apply "opcache.file_cache_only=0" to your PHP configuration and use the file cache as second level cache only.');
		} else {
			// Check whether opcache_get_status has been explicitly disabled an in case skip usage based checks
			$disabledFunctions = $this->iniGetWrapper->getString('disable_functions');
			if (isset($disabledFunctions) && str_contains($disabledFunctions, 'opcache_get_status')) {
				return [$level, $recommendations];
			}

			$status = opcache_get_status(false);

			if ($status === false) {
				$recommendations[] = $this->l10n->t('OPcache is not working as it should, opcache_get_status() returns false, please check configuration.');
				$level = 'error';
			}

			// Recommend to raise value, if more than 90% of max value is reached
			if (
				empty($status['opcache_statistics']['max_cached_keys']) ||
				($status['opcache_statistics']['num_cached_keys'] / $status['opcache_statistics']['max_cached_keys'] > 0.9)
			) {
				$recommendations[] = $this->l10n->t('The maximum number of OPcache keys is nearly exceeded. To assure that all scripts can be kept in the cache, it is recommended to apply "opcache.max_accelerated_files" to your PHP configuration with a value higher than "%s".', [($this->iniGetWrapper->getNumeric('opcache.max_accelerated_files') ?: 'currently')]);
			}

			if (
				empty($status['memory_usage']['free_memory']) ||
				($status['memory_usage']['used_memory'] / $status['memory_usage']['free_memory'] > 9)
			) {
				$recommendations[] = $this->l10n->t('The OPcache buffer is nearly full. To assure that all scripts can be hold in cache, it is recommended to apply "opcache.memory_consumption" to your PHP configuration with a value higher than "%s".', [($this->iniGetWrapper->getNumeric('opcache.memory_consumption') ?: 'currently')]);
			}

			$interned_strings_buffer = $this->iniGetWrapper->getNumeric('opcache.interned_strings_buffer') ?? 0;
			$memory_consumption = $this->iniGetWrapper->getNumeric('opcache.memory_consumption') ?? 0;
			if (
				// Do not recommend to raise the interned strings buffer size above a quarter of the total OPcache size
				($interned_strings_buffer < ($memory_consumption / 4)) &&
				(
					empty($status['interned_strings_usage']['free_memory']) ||
					($status['interned_strings_usage']['used_memory'] / $status['interned_strings_usage']['free_memory'] > 9)
				)
			) {
				$recommendations[] = $this->l10n->t('The OPcache interned strings buffer is nearly full. To assure that repeating strings can be effectively cached, it is recommended to apply "opcache.interned_strings_buffer" to your PHP configuration with a value higher than "%s".', [($this->iniGetWrapper->getNumeric('opcache.interned_strings_buffer') ?: 'currently')]);
			}
		}

		// Check for saved comments only when OPcache is currently disabled. If it was enabled, opcache.save_comments=0 would break Nextcloud in the first place.
		if (!$this->iniGetWrapper->getBool('opcache.save_comments')) {
			$recommendations[] = $this->l10n->t('OPcache is configured to remove code comments. With OPcache enabled, "opcache.save_comments=1" must be set for Nextcloud to function.');
			$level = 'error';
		}

		if (!$isPermitted) {
			$recommendations[] = $this->l10n->t('Nextcloud is not allowed to use the OPcache API. With OPcache enabled, it is highly recommended to include all Nextcloud directories with "opcache.restrict_api" or unset this setting to disable OPcache API restrictions, to prevent errors during Nextcloud core or app upgrades.');
			$level = 'error';
		}

		return [$level, $recommendations];
	}

	public function run(): SetupResult {
		// Skip OPcache checks if running from CLI
		if (\OC::$CLI && !$this->iniGetWrapper->getBool('opcache.enable_cli')) {
			return SetupResult::success($this->l10n->t('Checking from CLI, OPcache checks have been skipped.'));
		}

		[$level,$recommendations] = $this->getOpcacheSetupRecommendations();
		if (!empty($recommendations)) {
			return match($level) {
				'error' => SetupResult::error(
					$this->l10n->t('The PHP OPcache module is not properly configured. %s.', implode("\n", $recommendations)),
					$this->urlGenerator->linkToDocs('admin-php-opcache')
				),
				default => SetupResult::warning(
					$this->l10n->t('The PHP OPcache module is not properly configured. %s.', implode("\n", $recommendations)),
					$this->urlGenerator->linkToDocs('admin-php-opcache')
				),
			};
		} else {
			return SetupResult::success($this->l10n->t('Correctly configured'));
		}
	}
}

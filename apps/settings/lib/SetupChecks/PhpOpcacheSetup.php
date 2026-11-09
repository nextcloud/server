<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('PHP opcache');
	}

	#[\Override]
	public function getCategory(): string {
		return 'php';
	}

	/**
	 * Checks whether PHP OPcache is properly set up.
	 * @return array{'warning'|'error',list<string>} The level and the list of OPcache setup recommendations
	 */
	protected function getOpcacheSetupRecommendations(): array {
		$level = 'warning';

		if (!extension_loaded('Zend OPcache')) {
			return ['error', [$this->l10n->t('The PHP OPcache module is not loaded. For better performance it is recommended to load it into your PHP installation.')]];
		}

		$recommendations = [];

		$permittedPath = (string)$this->iniGetWrapper->getString('opcache.restrict_api');
		$isPermitted = $permittedPath === ''
			|| str_starts_with(\OC::$SERVERROOT, rtrim($permittedPath, '/'));

		if (!$isPermitted) {
			$recommendations[] = $this->l10n->t(
				'Nextcloud is not allowed to use the OPcache API. With OPcache enabled, it is highly recommended to include all Nextcloud directories with "opcache.restrict_api" or unset this setting to disable OPcache API restrictions, to prevent errors during Nextcloud core or app upgrades.'
			);
			$level = 'error';
		}

		if (!$this->iniGetWrapper->getBool('opcache.save_comments')) {
			$recommendations[] = $this->l10n->t(
				'OPcache is configured to remove code comments. With OPcache enabled, "opcache.save_comments=1" must be set for Nextcloud to function.'
			);
			$level = 'error';
		}

		if (!$this->iniGetWrapper->getBool('opcache.enable')) {
			$recommendations[] = $this->l10n->t(
				'OPcache is disabled. For better performance, it is recommended to apply "opcache.enable=1" to your PHP configuration.'
			);
			return ['error', $recommendations];
		}

		if ($this->iniGetWrapper->getBool('opcache.file_cache_only')) {
			$recommendations[] = $this->l10n->t(
				'The shared memory based OPcache is disabled. For better performance, it is recommended to apply "opcache.file_cache_only=0" to your PHP configuration and use the file cache as second level cache only.'
			);
			return [$level, $recommendations];
		}

		if ($this->iniGetWrapper->getBool('opcache.validate_timestamps') !== false
			&& (int)($this->iniGetWrapper->getNumeric('opcache.revalidate_freq') ?? 2) === 0) {
			$recommendations[] = $this->l10n->t(
				'OPcache checks all scripts for changes on every request. For better performance, it is recommended to apply `opcache.revalidate_freq=1` to your PHP configuration.'
			);
		}

		$disabledFunctions = array_map(
			'trim',
			explode(',', $this->iniGetWrapper->getString('disable_functions') ?? '')
		);

		if (in_array('opcache_get_status', $disabledFunctions, true)) {
			$recommendations[] = $this->l10n->t(
				'Nextcloud cannot retrieve OPcache usage statistics because "opcache_get_status" is listed in the PHP "disable_functions" setting. OPcache usage checks have been skipped.'
			);
			return [$level, $recommendations];
		}

		$status = opcache_get_status(false);

		if ($status === false) {
			$recommendations[] = $this->l10n->t(
				'OPcache is not working as it should, opcache_get_status() returns false, please check configuration.'
			);
			return ['error', $recommendations];
		}

		$memoryConsumption = $this->iniGetWrapper->getNumeric('opcache.memory_consumption');

		// cache_full can indicate either key-table or shared-memory exhaustion.
		// PHP considers the key table full when num_cached_keys equals max_cached_keys.
		// Recommend a value above max_cached_keys because opcache.max_accelerated_files
		// is rounded up to a supported prime: https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.max-accelerated-files
		// Otherwise, recommend more shared memory; exhaustion does not require zero free bytes.
		// Memory occupied by invalidated scripts can contribute to exhaustion, but reaching
		// opcache.max_wasted_percentage alone does not trigger a restart.
		// Some allocations can fail without setting cache_full, so this check is not exhaustive.
		if ($status['cache_full'] === true) {
			if ($status['opcache_statistics']['num_cached_keys'] === $status['opcache_statistics']['max_cached_keys']) {
				$recommendations[] = $this->l10n->t(
					'The maximum number of OPcache keys has been reached. To allow all scripts to be cached, increase "opcache.max_accelerated_files" in your PHP configuration to a value higher than "%s".',
					[$status['opcache_statistics']['max_cached_keys']],
				);
			} elseif ($memoryConsumption !== null) {
				$recommendations[] = $this->l10n->t(
					'OPcache has reported insufficient shared memory for caching. To allow all scripts to be cached, increase "opcache.memory_consumption" in your PHP configuration to a value higher than "%s".',
					[$memoryConsumption],
				);
			} else {
				$recommendations[] = $this->l10n->t(
					'OPcache has reported insufficient shared memory for caching. To allow all scripts to be cached, increase "opcache.memory_consumption" in your PHP configuration. Its current value could not be determined.'
				);
			}
		}

		$internedStringsBuffer = $this->iniGetWrapper->getNumeric('opcache.interned_strings_buffer');

		if ($internedStringsBuffer === 0 || $internedStringsBuffer === 0.0) {
			// No dedicated shared interned-strings buffer is configured.
			return [$level, $recommendations];
		}

		if (!isset(
			$status['interned_strings_usage']['free_memory'],
			$status['interned_strings_usage']['used_memory']
		)) {
			$recommendations[] = $this->l10n->t(
				'OPcache interned strings usage statistics are unavailable. The interned strings buffer usage check could not be completed.'
			);
			return [$level, $recommendations];
		}

		$internedStringsFreeMemory = $status['interned_strings_usage']['free_memory'];
		$internedStringsUsedMemory = $status['interned_strings_usage']['used_memory'];

		// Avoid division by zero and recommend an increase when more than 90% is used.
		$internedStringsNearlyFull = $internedStringsFreeMemory === 0
			|| ($internedStringsUsedMemory / $internedStringsFreeMemory > 9);

		if ($internedStringsNearlyFull) {
			if ($internedStringsBuffer === null || $memoryConsumption === null) {
				$recommendations[] = $this->l10n->t(
					'The OPcache interned strings buffer is nearly full, but the configured buffer size or total OPcache memory size could not be determined. Check "opcache.interned_strings_buffer" and "opcache.memory_consumption" in your PHP configuration.'
				);
			} elseif ($internedStringsBuffer < ($memoryConsumption / 4)) {
				// Do not recommend increasing the buffer when it already occupies at least a quarter of total OPcache memory.
				$recommendations[] = $this->l10n->t(
					'The OPcache interned strings buffer is nearly full. To allow repeating strings to be cached effectively, increase "opcache.interned_strings_buffer" in your PHP configuration to a value higher than "%s".',
					[$internedStringsBuffer],
				);
			}
		}

		return [$level, $recommendations];
	}

	#[\Override]
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

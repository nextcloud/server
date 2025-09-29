<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config;

use OC\App\AppManager;
use OC\AppConfig;
use OC\Installer;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Config\IUserConfig;
use OCP\Config\Lexicon\Preset;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * tools to manage the Preset feature
 */
class PresetManager {
	private const PRESET_CONFIGKEY = 'config_preset';

	private ?AppManager $appManager = null;
	private ?Installer $installer = null;

	private ?Preset $configLexiconPreset = null;

	public function __construct(
		private readonly IConfig $config,
		private readonly ConfigManager $configManager,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * store in config.php the new preset
	 * refresh cached preset
	 */
	public function setLexiconPreset(Preset $preset): void {
		$this->config->setSystemValue(self::PRESET_CONFIGKEY, $preset->value);
		$this->configLexiconPreset = $preset;
		$this->configManager->clearConfigCaches();
		$this->refreshPresetApps();
	}

	/**
	 * returns currently selected Preset
	 */
	public function getLexiconPreset(): Preset {
		if ($this->configLexiconPreset === null) {
			$this->configLexiconPreset = Preset::tryFrom($this->config->getSystemValueInt(self::PRESET_CONFIGKEY, 0)) ?? Preset::NONE;
		}

		return $this->configLexiconPreset;
	}

	/**
	 * get lexicon config entries affected by Preset and its default values
	 *
	 * **Warning** This method MUST be considered resource-needy!
	 *
	 * @return array<string, list<array{config: string, defaults: array{CLUB: null|string, FAMILY: null|string, LARGE: null|string, MEDIUM: null|string, NONE: null|string, PRIVATE: null|string, SCHOOL: null|string, SHARED: null|string, SMALL: null|string, UNIVERSITY: null|string}, entry: array{definition: string, deprecated: bool, key: string, lazy: bool, note: string, type: 'ARRAY'|'BOOL'|'FLOAT'|'INT'|'MIXED'|'STRING'}, value?: mixed}>>
	 */
	public function retrieveLexiconPreset(?string $appId = null): array {
		if ($appId === null) {
			$apps = [];
			foreach (['core'] + Server::get(IAppManager::class)->getEnabledApps() as $app) {
				$preset = $this->retrieveLexiconPreset($app);
				$apps[$app] = $preset[$app];
			}
			return $apps;
		}

		return [
			$appId => array_merge(
				$this->extractLexiconPresetFromConfigClass($appId, 'app', Server::get(IAppConfig::class)),
				$this->extractLexiconPresetFromConfigClass($appId, 'user', Server::get(IUserConfig::class))
			),
		];
	}

	/**
	 * @param string $appId
	 *
	 * @return list<array{config: string, defaults: array{CLUB: null|string, FAMILY: null|string, LARGE: null|string, MEDIUM: null|string, NONE: null|string, PRIVATE: null|string, SCHOOL: null|string, SHARED: null|string, SMALL: null|string, UNIVERSITY: null|string}, entry: array{definition: string, deprecated: bool, key: string, lazy: bool, note: string, type: 'ARRAY'|'BOOL'|'FLOAT'|'INT'|'MIXED'|'STRING'}, value?: mixed}>
	 */
	private function extractLexiconPresetFromConfigClass(
		string $appId,
		string $configType,
		AppConfig|UserConfig $config,
	): array {
		$presets = [];
		$lexicon = $config->getConfigDetailsFromLexicon($appId);
		foreach ($lexicon['entries'] as $entry) {
			$defaults = [];
			foreach (Preset::cases() as $case) {
				// for each case, we need to use a fresh IAppConfig with clear cache
				// cloning to avoid conflict while emulating preset
				$newConfig = clone $config;
				if ($newConfig instanceof AppConfig) {
					// needed to ignore cache and rebuild default
					$newConfig->clearCache();
				}
				if ($newConfig instanceof UserConfig) {
					// in the case of IUserConfig, clear all users' cache
					$newConfig->clearCacheAll();
				}

				$newLexicon = $newConfig->getLexiconEntry($appId, $entry->getKey());
				$defaults[$case->name] = $newLexicon?->getDefault($case);
			}

			// compare all value from $defaults, if more than 1 exist we have a preset
			$uniqueness = array_unique($defaults);
			if (count($uniqueness) < 2) {
				continue;
			}

			$details = [
				'config' => $configType,
				'entry' => [
					'key' => $entry->getKey(),
					'type' => $entry->getValueType()->name,
					'definition' => $entry->getDefinition(),
					'lazy' => $entry->isLazy(),
					'deprecated' => $entry->isDeprecated(),
					'note' => $entry->getNote(),
				],
				'defaults' => $defaults
			];

			try {
				// not interested if a users config value is already set
				if ($config instanceof AppConfig) {
					$details['value'] = $config->getDetails($appId, $entry->getKey())['value'];
				}
			} catch (AppConfigUnknownKeyException) {
			}

			$presets[] = $details;
		}

		return $presets;
	}

	/**
	 * Enable and/or Disable a list of apps based on the currently selected Preset
	 */
	public function refreshPresetApps(): void {
		$this->loadAppManager();

		$apps = $this->getPresetApps($this->getLexiconPreset());
		foreach ($apps['disabled'] ?? [] as $app) {
			try {
				$this->appManager->disableApp($app);
			} catch (\Exception $e) {
				$this->logger->warning('could not disable app', ['exception' => $e]);
			}
		}

		foreach ($apps['enabled'] ?? [] as $app) {
			$this->installApp($app);
		}
	}

	/**
	 * some parts cannot be initiated at __construct() time
	 */
	private function loadAppManager(): void {
		if ($this->appManager === null) {
			$this->appManager = Server::get(IAppManager::class);
		}
		if ($this->installer === null) {
			$this->installer = Server::get(Installer::class);
		}
	}

	/**
	 * download, install and enable app.
	 * generate warning entry in logs in case of failure.
	 */
	private function installApp(string $appId): void {
		$this->loadAppManager();
		if (!$this->installer->isDownloaded($appId)) {
			try {
				$this->installer->downloadApp($appId);
			} catch (\Exception $e) {
				$this->logger->warning('could not download app', ['appId' => $appId, 'exception' => $e]);
				return;
			}
		}

		try {
			$this->installer->installApp($appId, true);
		} catch (\Exception $e) {
			$this->logger->warning('could not install app', ['appId' => $appId, 'exception' => $e]);
			return;
		}

		try {
			$this->appManager->enableApp($appId);
		} catch (AppPathNotFoundException $e) {
			$this->logger->warning('could not enable app', ['appId' => $appId, 'exception' => $e]);
			return;
		}
	}

	/**
	 * return list of apps that are enabled/disabled when switching current Preset
	 *
	 * @return array<string, array{disabled: list<string>, enabled: list<string>}>
	 */
	public function retrieveLexiconPresetApps(): array {
		$apps = [];
		foreach (Preset::cases() as $case) {
			$apps[$case->name] = $this->getPresetApps($case);
		}

		return $apps;
	}

	/**
	 * get listing of enabled/disabled app from Preset
	 *
	 * @return array{enabled: list<string>, disabled: list<string>}
	 */
	private function getPresetApps(Preset $preset): array {
		return match ($preset) {
			Preset::CLUB, Preset::FAMILY, Preset::SCHOOL, Preset::UNIVERSITY, Preset::SMALL, Preset::MEDIUM, Preset::LARGE => ['enabled' => ['user_status', 'guests'], 'disabled' => []],
			Preset::SHARED => ['enabled' => ['external'], 'disabled' => ['user_status']],
			default => ['enabled' => [], 'disabled' => []],
		};
	}
}

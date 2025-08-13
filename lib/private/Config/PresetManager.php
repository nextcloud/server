<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config;

use OC\AppConfig;
use OCP\App\IAppManager;
use OCP\Config\Lexicon\Preset;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Server;

/**
 * tools to maintains configurations
 */
class PresetManager {
	private const PRESET_CONFIGKEY = 'config_preset';

	private ?Preset $configLexiconPreset = null;

	public function __construct(
		private readonly IConfig $config,
		private readonly ConfigManager $configManager,
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
	 * @return array<string, list<array{defaults: array{CLUB: null|string, FAMILY: null|string, LARGE: null|string, MEDIUM: null|string, NONE: null|string, PRIVATE: null|string, SCHOOL: null|string, SHARED: null|string, SMALL: null|string, UNIVERSITY: null|string}, entry: array{definition: string, deprecated: bool, key: string, lazy: bool, note: string, type: 'ARRAY'|'BOOL'|'FLOAT'|'INT'|'MIXED'|'STRING'}, value?: mixed}>>
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

		/** @var AppConfig|null $appConfig */
		$appConfig = Server::get(IAppConfig::class);
		$lexicon = $appConfig->getConfigDetailsFromLexicon($appId);
		$presets = [];
		foreach ($lexicon['entries'] as $entry) {
			$defaults = [];
			foreach (Preset::cases() as $case) {
				// for each case, we need to use a fresh IAppConfig with clear cache
				// cloning to avoid conflict while emulating preset
				$newConfig = clone $appConfig;
				$newConfig->clearCache(); // needed to ignore cache and rebuild default
				$newLexicon = $newConfig->getLexiconEntry($appId, $entry->getKey());
				$defaults[$case->name] = $newLexicon?->getDefault($case);
			}

			// compare all value from $defaults, if more than 1 exist we have a preset
			$uniqueness = array_unique($defaults);
			if (count($uniqueness) < 2) {
				continue;
			}

			$details = [
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
				$details['value'] = $appConfig->getDetails($appId, $entry->getKey())['value'];
			} catch (AppConfigUnknownKeyException) {
			}

			$presets[] = $details;
		}

		return [$appId => $presets];
	}
}

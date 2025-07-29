<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config;

use OCP\Config\Lexicon\Preset;
use OCP\IConfig;

/**
 * tools to maintains configurations
 *
 * @since 32.0.0
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

	public function getLexiconPreset(): Preset {
		if ($this->configLexiconPreset === null) {
			$this->configLexiconPreset = Preset::tryFrom($this->config->getSystemValueInt(self::PRESET_CONFIGKEY, 0)) ?? Preset::NONE;
		}

		return $this->configLexiconPreset;
	}
}

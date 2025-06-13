<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config;

use OC\Config\Model\PresetDefault;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * tools to work on preset
 *
 * @since 32.0.0
 */
class PresetManager {
	public const CONFIG_PRESET = 'config.preset';
	private ?PresetDefault $presetDefault = null;
	public function __construct(
		private readonly IConfig $config,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @since 32.0.0
	 * @return PresetDefault
	 */
	public function getPreset(): PresetDefault {
		if ($this->presetDefault === null) {
			$this->loadPresetFile();
		}

		return $this->presetDefault;
	}

	/**
	 * @param string|null $presetFile
	 * @since 32.0.0
	 */
	public function loadPresetFile(?string $presetFile = null): void {
		$this->presetDefault = new PresetDefault($presetFile ?? $this->getPresetFilepath());
	}

	/**
	 * @param string $presetFile
	 * @since 32.0.0
	 * @return bool
	 */
	public function parsePresetFile(string $presetFile): bool {
		// TOOD: loadPresetFile, verify value type with configlexicon
		return true;
	}

	private function getPresetFilepath(): string {
		$preset = $this->config->getSystemValueString(self::CONFIG_PRESET, 'default');
		// TODO: sanitize $preset ?
		return \OC::$SERVERROOT . '/core/preset/' . $preset . '.php';
	}
}

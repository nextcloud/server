<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config;

use OC\App\AppManager;
use OC\Installer;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Config\Lexicon\Preset;
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
	 * get listing of enabled/disabled app from Preset
	 *
	 * @return array{enabled: list<string>, disabled: list<string>}
	 */
	private function getPresetApps(Preset $preset): array {
		return match ($preset) {
			Preset::CLUB, Preset::FAMILY, Preset::SCHOOL, Preset::UNIVERSITY, Preset::SMALL, Preset::MEDIUM, Preset::LARGE => ['enabled' => ['user_status', 'intros', 'guests'], 'disabled' => []],
			Preset::SHARED => ['enabled' => ['intros', 'external'], 'disabled' => ['user_status']],
			default => ['enabled' => [], 'disabled' => []],
		};
	}
}

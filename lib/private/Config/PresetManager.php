<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config;

use NCU\Config\Lexicon\Preset;
use OC\App\AppManager;
use OC\Core\Command\App\Install;
use OC\Installer;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * tools to maintains configurations
 *
 * @since 32.0.0
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

	public function getLexiconPreset(): Preset {
		if ($this->configLexiconPreset === null) {
			$this->configLexiconPreset = Preset::tryFrom($this->config->getSystemValueInt(self::PRESET_CONFIGKEY, 0)) ?? Preset::NONE;
		}

		return $this->configLexiconPreset;
	}

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

	private function loadAppManager(): void {
		if ($this->appManager === null) {
			$this->appManager = \OCP\Server::get(IAppManager::class);
		}
		if ($this->installer === null) {
			$this->installer = \OCP\Server::get(Installer::class);
		}
	}

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
			Preset::LARGE => ['enabled' => ['globalsiteselector', 'intros'], 'disabled' => []],
			Preset::CLUB, Preset::FAMILY, Preset::EDUCATION, Preset::SMALL, Preset::MEDIUM => ['enabled' => ['intros'], 'disabled' => []],
			Preset::SHARED => ['enabled' => ['intros', 'external'], 'disabled' => []],
			default => ['enabled' => [], 'disabled' => ['intros']],
		};
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Service;

use OCA\Viewer\AppInfo\Application;
use OCP\Config\IUserConfig;
use OCP\IUserSession;

/**
 * The settings a user can change in the viewer, the way the Files app keeps
 * its own. A setting is either a number within a range, or a toggle.
 */
class UserConfig {
	public const ALLOWED_CONFIGS = [
		[
			// Seconds the slideshow shows each file for
			'key' => 'slideshow_delay',
			'default' => 5,
			'min' => 1,
			'max' => 60,
		],
		[
			// Volume of videos and audio, in percent
			'key' => 'volume',
			'default' => 100,
			'min' => 0,
			'max' => 100,
		],
		[
			// Whether videos and audio start muted
			'key' => 'muted',
			'default' => false,
		],
	];

	public function __construct(
		private IUserConfig $userConfig,
		private IUserSession $userSession,
	) {
	}

	/**
	 * Get the list of all allowed user config keys
	 *
	 * @return list<string>
	 */
	public function getAllowedConfigKeys(): array {
		return array_column(self::ALLOWED_CONFIGS, 'key');
	}

	/**
	 * Set a user config
	 *
	 * @throws \InvalidArgumentException for an unknown key, or a value it does not accept
	 * @throws \RuntimeException when no user is logged in
	 */
	public function setConfig(string $key, int|bool $value): void {
		$config = $this->getConfig($key);
		if (is_bool($config['default'])) {
			if (!is_bool($value)) {
				throw new \InvalidArgumentException('Invalid config value');
			}
			$this->userConfig->setValueBool($this->getUserId(), Application::APP_ID, $key, $value);
			return;
		}

		if (!is_int($value) || $value < $config['min'] || $value > $config['max']) {
			throw new \InvalidArgumentException('Invalid config value');
		}
		$this->userConfig->setValueInt($this->getUserId(), Application::APP_ID, $key, $value);
	}

	/**
	 * Get the current user configs, the defaults for what they never set
	 *
	 * @return array<string, int|bool>
	 * @throws \RuntimeException when no user is logged in
	 */
	public function getConfigs(): array {
		$userId = $this->getUserId();
		$configs = [];
		foreach (self::ALLOWED_CONFIGS as $config) {
			$configs[$config['key']] = is_bool($config['default'])
				? $this->userConfig->getValueBool($userId, Application::APP_ID, $config['key'], $config['default'])
				: $this->userConfig->getValueInt($userId, Application::APP_ID, $config['key'], $config['default']);
		}
		return $configs;
	}

	/**
	 * @return array{key: string, default: int, min: int, max: int}|array{key: string, default: bool}
	 * @throws \InvalidArgumentException for an unknown key
	 */
	private function getConfig(string $key): array {
		foreach (self::ALLOWED_CONFIGS as $config) {
			if ($config['key'] === $key) {
				return $config;
			}
		}
		throw new \InvalidArgumentException('Unknown config key');
	}

	private function getUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new \RuntimeException('No user logged in');
		}
		return $user->getUID();
	}
}

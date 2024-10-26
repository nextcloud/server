<?php
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Service;

use OCA\Files\AppInfo\Application;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;

class UserConfig {
	public const ALLOWED_CONFIGS = [
		[
			// Whether to crop the files previews or not in the files list
			'key' => 'crop_image_previews',
			'default' => true,
			'allowed' => [true, false],
		],
		[
			// Whether to show the hidden files or not in the files list
			'key' => 'show_hidden',
			'default' => false,
			'allowed' => [true, false],
		],
		[
			// Whether to sort favorites first in the list or not
			'key' => 'sort_favorites_first',
			'default' => true,
			'allowed' => [true, false],
		],
		[
			// Whether to sort folders before files in the list or not
			'key' => 'sort_folders_first',
			'default' => true,
			'allowed' => [true, false],
		],
		[
			// Whether to show the files list in grid view or not
			'key' => 'grid_view',
			'default' => false,
			'allowed' => [true, false],
		],
		[
			// Whether to show the folder tree
			'key' => 'folder_tree',
			'default' => true,
			'allowed' => [true, false],
		],
	];
	protected ?IUser $user = null;

	public function __construct(
		protected IConfig $config,
		IUserSession $userSession,
	) {
		$this->user = $userSession->getUser();
	}

	/**
	 * Get the list of all allowed user config keys
	 * @return string[]
	 */
	public function getAllowedConfigKeys(): array {
		return array_map(function ($config) {
			return $config['key'];
		}, self::ALLOWED_CONFIGS);
	}

	/**
	 * Get the list of allowed config values for a given key
	 *
	 * @param string $key a valid config key
	 * @return array
	 */
	private function getAllowedConfigValues(string $key): array {
		foreach (self::ALLOWED_CONFIGS as $config) {
			if ($config['key'] === $key) {
				return $config['allowed'];
			}
		}
		return [];
	}

	/**
	 * Get the default config value for a given key
	 *
	 * @param string $key a valid config key
	 * @return string|bool
	 */
	private function getDefaultConfigValue(string $key) {
		foreach (self::ALLOWED_CONFIGS as $config) {
			if ($config['key'] === $key) {
				return $config['default'];
			}
		}
		return '';
	}

	/**
	 * Set a user config
	 *
	 * @param string $key
	 * @param string|bool $value
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	public function setConfig(string $key, $value): void {
		if ($this->user === null) {
			throw new \Exception('No user logged in');
		}

		if (!in_array($key, $this->getAllowedConfigKeys())) {
			throw new \InvalidArgumentException('Unknown config key');
		}

		if (!in_array($value, $this->getAllowedConfigValues($key))) {
			throw new \InvalidArgumentException('Invalid config value');
		}

		if (is_bool($value)) {
			$value = $value ? '1' : '0';
		}

		$this->config->setUserValue($this->user->getUID(), Application::APP_ID, $key, $value);
	}

	/**
	 * Get the current user configs array
	 *
	 * @return array
	 */
	public function getConfigs(): array {
		if ($this->user === null) {
			throw new \Exception('No user logged in');
		}

		$userId = $this->user->getUID();
		$userConfigs = array_map(function (string $key) use ($userId) {
			$value = $this->config->getUserValue($userId, Application::APP_ID, $key, $this->getDefaultConfigValue($key));
			// If the default is expected to be a boolean, we need to cast the value
			if (is_bool($this->getDefaultConfigValue($key)) && is_string($value)) {
				return $value === '1';
			}
			return $value;
		}, $this->getAllowedConfigKeys());

		return array_combine($this->getAllowedConfigKeys(), $userConfigs);
	}
}

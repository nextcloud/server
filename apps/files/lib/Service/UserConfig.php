<?php
/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
namespace OCA\Files\Service;

use OCA\Files\AppInfo\Application;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;

class UserConfig {
	const ALLOWED_CONFIGS = [
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
	];

	protected IConfig $config;
	protected ?IUser $user = null;

	public function __construct(IConfig $config, IUserSession $userSession) {
		$this->config = $config;
		$this->user = $userSession->getUser();
	}

	/**
	 * Get the list of all allowed user config keys
	 * @return string[]
	 */
	public function getAllowedConfigKeys(): array {
		return array_map(function($config) {
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
		$userConfigs = array_map(function(string $key) use ($userId) {
			$value = $this->config->getUserValue($userId, Application::APP_ID, $key, $this->getDefaultConfigValue($key));
			// If the default is expected to be a boolean, we need to cast the value
			if (is_bool($this->getDefaultConfigValue($key))) {
				return $value === '1';
			}
			return $value;
		}, $this->getAllowedConfigKeys());

		return array_combine($this->getAllowedConfigKeys(), $userConfigs);
	}
}

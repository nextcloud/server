<?php
/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
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

class ViewConfig {
	public const CONFIG_KEY = 'files_views_configs';
	public const ALLOWED_CONFIGS = [
		[
			// The default sorting key for the files list view
			'key' => 'sorting_mode',
			// null by default as views can provide default sorting key
			// and will fallback to it if user hasn't change it
			'default' => null,
		],
		[
			// The default sorting direction for the files list view
			'key' => 'sorting_direction',
			'default' => 'asc',
			'allowed' => ['asc', 'desc'],
		],
		[
			// If the navigation entry for this view is expanded or not
			'key' => 'expanded',
			'default' => true,
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
				return $config['allowed'] ?? [];
			}
		}
		return [];
	}

	/**
	 * Get the default config value for a given key
	 *
	 * @param string $key a valid config key
	 * @return string|bool|null
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
	 * @param string $view
	 * @param string $key
	 * @param string|bool $value
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	public function setConfig(string $view, string $key, $value): void {
		if ($this->user === null) {
			throw new \Exception('No user logged in');
		}

		if (!$view) {
			throw new \Exception('Unknown view');
		}

		if (!in_array($key, $this->getAllowedConfigKeys())) {
			throw new \InvalidArgumentException('Unknown config key');
		}
	
		if (!in_array($value, $this->getAllowedConfigValues($key))
			&& !empty($this->getAllowedConfigValues($key))) {
			throw new \InvalidArgumentException('Invalid config value');
		}

		// Cast boolean values
		if (is_bool($this->getDefaultConfigValue($key))) {
			$value = $value === '1';
		}

		$config = $this->getConfigs();
		$config[$view][$key] = $value;

		$this->config->setUserValue($this->user->getUID(), Application::APP_ID, self::CONFIG_KEY, json_encode($config));
	}

	/**
	 * Get the current user configs array for a given view
	 *
	 * @return array
	 */
	public function getConfig(string $view): array {
		if ($this->user === null) {
			throw new \Exception('No user logged in');
		}

		$userId = $this->user->getUID();
		$configs = json_decode($this->config->getUserValue($userId, Application::APP_ID, self::CONFIG_KEY, '[]'), true);
		
		if (!isset($configs[$view])) {
			$configs[$view] = [];
		}

		// Extend undefined values with defaults
		return array_reduce(self::ALLOWED_CONFIGS, function ($carry, $config) use ($view, $configs) {
			$key = $config['key'];
			$carry[$key] = $configs[$view][$key] ?? $this->getDefaultConfigValue($key);
			return $carry;
		}, []);
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
		$configs = json_decode($this->config->getUserValue($userId, Application::APP_ID, self::CONFIG_KEY, '[]'), true);
		$views = array_keys($configs);
		
		return array_reduce($views, function ($carry, $view) use ($configs) {
			$carry[$view] = $this->getConfig($view);
			return $carry;
		}, []);
	}
}

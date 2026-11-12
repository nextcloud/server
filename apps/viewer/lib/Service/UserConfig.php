<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Service;

use OCA\Viewer\AppInfo\Application;
use OCA\Viewer\ConfigLexicon;
use OCP\Config\IUserConfig;
use OCP\Config\Lexicon\Entry;
use OCP\Config\ValueType;
use OCP\IUserSession;

/**
 * The settings a user can change in the viewer, as its config lexicon
 * declares them, the way the Files app keeps its own.
 */
class UserConfig {
	/** The numbers a setting accepts, which the lexicon cannot say */
	private const RANGES = [
		ConfigLexicon::SLIDESHOW_DELAY => [1, 60],
		ConfigLexicon::VOLUME => [0, 100],
	];

	public function __construct(
		private IUserConfig $userConfig,
		private IUserSession $userSession,
		private ConfigLexicon $lexicon,
	) {
	}

	/**
	 * Set a user config
	 *
	 * @throws \InvalidArgumentException for an unknown key, or a value it does not accept
	 * @throws \RuntimeException when no user is logged in
	 */
	public function setConfig(string $key, int|bool $value): void {
		$entry = $this->getEntry($key);
		if ($entry->getValueType() === ValueType::BOOL) {
			if (!is_bool($value)) {
				throw new \InvalidArgumentException('Invalid config value');
			}
			$this->userConfig->setValueBool($this->getUserId(), Application::APP_ID, $key, $value);
			return;
		}

		[$min, $max] = self::RANGES[$key];
		if (!is_int($value) || $value < $min || $value > $max) {
			throw new \InvalidArgumentException('Invalid config value');
		}
		$this->userConfig->setValueInt($this->getUserId(), Application::APP_ID, $key, $value);
	}

	/**
	 * Get the current user configs, the lexicon's defaults for what they never set
	 *
	 * @return array<string, int|bool>
	 * @throws \RuntimeException when no user is logged in
	 */
	public function getConfigs(): array {
		$userId = $this->getUserId();
		$configs = [];
		foreach ($this->lexicon->getUserConfigs() as $entry) {
			$key = $entry->getKey();
			$configs[$key] = $entry->getValueType() === ValueType::BOOL
				? $this->userConfig->getValueBool($userId, Application::APP_ID, $key)
				: $this->userConfig->getValueInt($userId, Application::APP_ID, $key);
		}
		return $configs;
	}

	/**
	 * @throws \InvalidArgumentException for an unknown key
	 */
	private function getEntry(string $key): Entry {
		foreach ($this->lexicon->getUserConfigs() as $entry) {
			if ($entry->getKey() === $key) {
				return $entry;
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

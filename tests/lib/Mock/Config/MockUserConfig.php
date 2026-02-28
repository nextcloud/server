<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Mock\Config;

use Generator;
use OCP\Config\IUserConfig;
use OCP\Config\ValueType;

class MockUserConfig implements IUserConfig {
	public function __construct(
		public array $config = [],
	) {
	}

	public function getUserIds(string $appId = ''): array {
		return array_keys($this->config);
	}

	public function getApps(string $userId): array {
		return array_keys($this->config[$userId] ?? []);
	}

	public function getKeys(string $userId, string $app): array {
		if (isset($this->config[$userId][$app])) {
			return array_keys($this->config[$userId][$app]);
		} else {
			return [];
		}
	}

	public function hasKey(string $userId, string $app, string $key, ?bool $lazy = false): bool {
		return isset($this->config[$userId][$app][$key]);
	}

	public function isSensitive(string $userId, string $app, string $key, ?bool $lazy = false): bool {
		throw new \Exception('not implemented');
	}

	public function isIndexed(string $userId, string $app, string $key, ?bool $lazy = false): bool {
		throw new \Exception('not implemented');
	}

	public function isLazy(string $userId, string $app, string $key): bool {
		throw new \Exception('not implemented');
	}

	public function getValues(string $userId, string $app, string $prefix = '', bool $filtered = false): array {
		throw new \Exception('not implemented');
	}

	public function getAllValues(string $userId, bool $filtered = false): array {
		throw new \Exception('not implemented');
	}

	public function getValuesByApps(string $userId, string $key, bool $lazy = false, ?ValueType $typedAs = null): array {
		throw new \Exception('not implemented');
	}

	public function getValuesByUsers(string $app, string $key, ?ValueType $typedAs = null, ?array $userIds = null): array {
		throw new \Exception('not implemented');
	}

	public function searchUsersByValueString(string $app, string $key, string $value, bool $caseInsensitive = false): Generator {
		throw new \Exception('not implemented');
	}

	public function searchUsersByValueInt(string $app, string $key, int $value): Generator {
		throw new \Exception('not implemented');
	}

	public function searchUsersByValues(string $app, string $key, array $values): Generator {
		throw new \Exception('not implemented');
	}

	public function searchUsersByValueBool(string $app, string $key, bool $value): Generator {
		throw new \Exception('not implemented');
	}

	public function getValueString(string $userId, string $app, string $key, string $default = '', bool $lazy = false): string {
		if (isset($this->config[$userId][$app])) {
			return (string)$this->config[$userId][$app][$key];
		} else {
			return $default;
		}
	}

	public function getValueInt(string $userId, string $app, string $key, int $default = 0, bool $lazy = false): int {
		if (isset($this->config[$userId][$app])) {
			return (int)$this->config[$userId][$app][$key];
		} else {
			return $default;
		}
	}

	public function getValueFloat(string $userId, string $app, string $key, float $default = 0, bool $lazy = false): float {
		if (isset($this->config[$userId][$app])) {
			return (float)$this->config[$userId][$app][$key];
		} else {
			return $default;
		}
	}

	public function getValueBool(string $userId, string $app, string $key, bool $default = false, bool $lazy = false): bool {
		if (isset($this->config[$userId][$app])) {
			return (bool)$this->config[$userId][$app][$key];
		} else {
			return $default;
		}
	}

	public function getValueArray(string $userId, string $app, string $key, array $default = [], bool $lazy = false): array {
		if (isset($this->config[$userId][$app])) {
			return $this->config[$userId][$app][$key];
		} else {
			return $default;
		}
	}

	public function getValueType(string $userId, string $app, string $key, ?bool $lazy = null): ValueType {
		throw new \Exception('not implemented');
	}

	public function getValueFlags(string $userId, string $app, string $key, bool $lazy = false): int {
		throw new \Exception('not implemented');
	}

	public function setValueString(string $userId, string $app, string $key, string $value, bool $lazy = false, int $flags = 0): bool {
		$this->config[$userId][$app][$key] = $value;
		return true;
	}

	public function setValueInt(string $userId, string $app, string $key, int $value, bool $lazy = false, int $flags = 0): bool {
		$this->config[$userId][$app][$key] = $value;
		return true;
	}

	public function setValueFloat(string $userId, string $app, string $key, float $value, bool $lazy = false, int $flags = 0): bool {
		$this->config[$userId][$app][$key] = $value;
		return true;
	}

	public function setValueBool(string $userId, string $app, string $key, bool $value, bool $lazy = false): bool {
		$this->config[$userId][$app][$key] = $value;
		return true;
	}

	public function setValueArray(string $userId, string $app, string $key, array $value, bool $lazy = false, int $flags = 0): bool {
		$this->config[$userId][$app][$key] = $value;
		return true;
	}

	public function updateSensitive(string $userId, string $app, string $key, bool $sensitive): bool {
		throw new \Exception('not implemented');
	}

	public function updateGlobalSensitive(string $app, string $key, bool $sensitive): void {
		throw new \Exception('not implemented');
	}

	public function updateIndexed(string $userId, string $app, string $key, bool $indexed): bool {
		throw new \Exception('not implemented');
	}

	public function updateGlobalIndexed(string $app, string $key, bool $indexed): void {
		throw new \Exception('not implemented');
	}

	public function updateLazy(string $userId, string $app, string $key, bool $lazy): bool {
		throw new \Exception('not implemented');
	}

	public function updateGlobalLazy(string $app, string $key, bool $lazy): void {
		throw new \Exception('not implemented');
	}

	public function getDetails(string $userId, string $app, string $key): array {
		throw new \Exception('not implemented');
	}

	public function deleteUserConfig(string $userId, string $app, string $key): void {
		unset($this->config[$userId][$app][$key]);
	}

	public function deleteKey(string $app, string $key): void {
		throw new \Exception('not implemented');
	}

	public function deleteApp(string $app): void {
		throw new \Exception('not implemented');
	}

	public function deleteAllUserConfig(string $userId): void {
		unset($this->config[$userId]);
	}

	public function clearCache(string $userId, bool $reload = false): void {
		throw new \Exception('not implemented');
	}

	public function clearCacheAll(): void {
		throw new \Exception('not implemented');
	}
}

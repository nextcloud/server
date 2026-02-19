<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Mock\Config;

use OCP\Exceptions\AppConfigIncorrectTypeException;
use OCP\IAppConfig;

class MockAppConfig implements IAppConfig {
	public function __construct(
		public array $config = [],
	) {
	}

	public function hasKey(string $app, string $key, ?bool $lazy = false): bool {
		return isset($this->config[$app][$key]);
	}

	public function getValues($app, $key): array {
		throw new \Exception('not implemented');
	}

	public function getFilteredValues($app): array {
		throw new \Exception('not implemented');
	}

	public function getApps(): array {
		return array_keys($this->config);
	}

	public function getKeys(string $app): array {
		return array_keys($this->config[$app] ?? []);
	}

	public function isSensitive(string $app, string $key, ?bool $lazy = false): bool {
		throw new \Exception('not implemented');
	}

	public function isLazy(string $app, string $key): bool {
		throw new \Exception('not implemented');
	}

	public function getAllValues(string $app, string $prefix = '', bool $filtered = false): array {
		throw new \Exception('not implemented');
	}

	public function searchValues(string $key, bool $lazy = false, ?int $typedAs = null): array {
		throw new \Exception('not implemented');
	}

	public function getValueString(string $app, string $key, string $default = '', bool $lazy = false): string {
		return (string)(($this->config[$app] ?? [])[$key] ?? $default);
	}

	public function getValueInt(string $app, string $key, int $default = 0, bool $lazy = false): int {
		return (int)(($this->config[$app] ?? [])[$key] ?? $default);
	}

	public function getValueFloat(string $app, string $key, float $default = 0, bool $lazy = false): float {
		return (float)(($this->config[$app] ?? [])[$key] ?? $default);
	}

	public function getValueBool(string $app, string $key, bool $default = false, bool $lazy = false): bool {
		return (bool)(($this->config[$app] ?? [])[$key] ?? $default);
	}

	public function getValueArray(string $app, string $key, array $default = [], bool $lazy = false): array {
		return ($this->config[$app] ?? [])[$key] ?? $default;
	}

	public function getValueType(string $app, string $key, ?bool $lazy = null): int {
		throw new \Exception('not implemented');
	}

	public function setValueString(string $app, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool {
		$this->config[$app][$key] = $value;
		return true;
	}

	public function setValueInt(string $app, string $key, int $value, bool $lazy = false, bool $sensitive = false): bool {
		$this->config[$app][$key] = $value;
		return true;
	}

	public function setValueFloat(string $app, string $key, float $value, bool $lazy = false, bool $sensitive = false): bool {
		$this->config[$app][$key] = $value;
		return true;
	}

	public function setValueBool(string $app, string $key, bool $value, bool $lazy = false): bool {
		$this->config[$app][$key] = $value;
		return true;
	}

	public function setValueArray(string $app, string $key, array $value, bool $lazy = false, bool $sensitive = false): bool {
		$this->config[$app][$key] = $value;
		return true;
	}

	public function updateSensitive(string $app, string $key, bool $sensitive): bool {
		throw new \Exception('not implemented');
	}

	public function updateLazy(string $app, string $key, bool $lazy): bool {
		throw new \Exception('not implemented');
	}

	public function getDetails(string $app, string $key): array {
		throw new \Exception('not implemented');
	}

	public function convertTypeToInt(string $type): int {
		return match (strtolower($type)) {
			'mixed' => IAppConfig::VALUE_MIXED,
			'string' => IAppConfig::VALUE_STRING,
			'integer' => IAppConfig::VALUE_INT,
			'float' => IAppConfig::VALUE_FLOAT,
			'boolean' => IAppConfig::VALUE_BOOL,
			'array' => IAppConfig::VALUE_ARRAY,
			default => throw new AppConfigIncorrectTypeException('Unknown type ' . $type)
		};
	}

	public function convertTypeToString(int $type): string {
		$type &= ~self::VALUE_SENSITIVE;

		return match ($type) {
			IAppConfig::VALUE_MIXED => 'mixed',
			IAppConfig::VALUE_STRING => 'string',
			IAppConfig::VALUE_INT => 'integer',
			IAppConfig::VALUE_FLOAT => 'float',
			IAppConfig::VALUE_BOOL => 'boolean',
			IAppConfig::VALUE_ARRAY => 'array',
			default => throw new AppConfigIncorrectTypeException('Unknown numeric type ' . $type)
		};
	}

	public function deleteKey(string $app, string $key): void {
		if ($this->hasKey($app, $key)) {
			unset($this->config[$app][$key]);
		}
	}

	public function deleteApp(string $app): void {
		if (isset($this->config[$app])) {
			unset($this->config[$app]);
		}
	}

	public function clearCache(bool $reload = false): void {
	}

	public function searchKeys(string $app, string $prefix = '', bool $lazy = false): array {
		throw new \Exception('not implemented');
	}

	public function getKeyDetails(string $app, string $key): array {
		throw new \Exception('not implemented');
	}

	public function getAppInstalledVersions(bool $onlyEnabled = false): array {
		throw new \Exception('not implemented');
	}
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test;

use OCP\Config\ValueType;
use OCP\Exceptions\AppConfigIncorrectTypeException;
use OCP\IAppConfig;

/**
 * This is a fake AppConfig class used for testing purposes.
 * It allows to test the AppConfig class without relying on the actual database or cache implementations.
 */
class FakeAppConfig implements IAppConfig {

	public function __construct(
		/** @var array<string,
		 * 		array<string,
		 * 			array{
		 * 				value: mixed,
		 * 				type: ?ValueType,
		 * 				lazy: ?bool,
		 * 				sensitive: ?bool
		 * 			}
		 * 		>
		 * > $appConfig */
		private array $appConfig = [],
		/** @var array<string,
		 * 		array<string,
		 * 			array{
		 * 				lazy: ?bool,
		 * 				valueType: ?ValueType,
		 * 				valueTypeName: ?string,
		 * 				sensitive: ?bool,
		 * 				internal: ?bool,
		 * 				default: ?mixed,
		 * 				definition: ?string,
		 * 				note: ?string
		 * 			}
		 * 		>
		 * > $lexicon */
		private array $lexicon = [],
	) {
	}

	#[\Override]
	public function getApps(): array {
		return array_keys($this->appConfig);
	}

	#[\Override]
	public function getKeys(string $app): array {
		return array_keys($this->appConfig[$app] ?? []);
	}

	#[\Override]
	public function searchKeys(string $app, string $prefix = '', bool $lazy = false): array {
		return array_filter($this->getKeys($app), fn (string $key) => str_starts_with($key, $prefix));
	}

	#[\Override]
	public function hasKey(string $app, string $key, ?bool $lazy = false): bool {
		return isset($this->appConfig[$app][$key]);
	}

	#[\Override]
	public function isSensitive(string $app, string $key, ?bool $lazy = false): bool {
		return $this->appConfig[$app][$key]['sensitive'] ?? false;
	}

	#[\Override]
	public function isLazy(string $app, string $key): bool {
		return $this->appConfig[$app][$key]['lazy'];
	}

	#[\Override]
	public function getAllValues(string $app, string $prefix = '', bool $filtered = false): array {
		return array_map(fn (string $key) => $this->appConfig[$app][$key]['value'], $this->searchKeys($app, $prefix));
	}

	#[\Override]
	public function searchValues(string $key, bool $lazy = false, ?int $typedAs = null): array {
		$values = [];
		foreach ($this->getApps() as $app) {
			foreach ($this->searchKeys($app, $key, $lazy) as $appKey) {
				if ($appKey === $key) {
					$values[$app] = $this->appConfig[$app][$appKey]['value'];
				}
			}
		}
		return $values;
	}

	private function getValue(string $app, string $key, mixed $default): mixed {
		return $this->appConfig[$app][$key]['value'] ?? $default;
	}

	#[\Override]
	public function getValueString(string $app, string $key, string $default = '', bool $lazy = false): string {
		return (string)$this->getValue($app, $key, $default);
	}

	#[\Override]
	public function getValueInt(string $app,string $key,int $default = 0,bool $lazy = false): int {
		return (int)$this->getValue($app, $key, $default);
	}

	#[\Override]
	public function getValueFloat(string $app, string $key, float $default = 0, bool $lazy = false): float {
		return (float)$this->getValue($app, $key, $default);
	}

	#[\Override]
	public function getValueBool(string $app, string $key, bool $default = false, bool $lazy = false): bool {
		return (bool)$this->getValue($app, $key, $default);
	}

	#[\Override]
	public function getValueArray(string $app, string $key, array $default = [], bool $lazy = false): array {
		return (array)$this->getValue($app, $key, $default);
	}

	#[\Override]
	public function getValueType(string $app, string $key, ?bool $lazy = null): int {
		return (int)$this->appConfig[$app][$key]['type'];
	}

	private function setValue(string $app, string $key, mixed $value, int $type, bool $lazy, bool $sensitive): bool {
		$this->appConfig[$app][$key] = [
			'value' => $value,
			'type' => $type,
			'lazy' => $lazy,
			'sensitive' => $sensitive,
		];
		return true;
	}

	#[\Override]
	public function setValueString(string $app, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool {
		return $this->setValue($app, $key, $value, self::VALUE_STRING, $lazy, $sensitive);}

	#[\Override]
	public function setValueInt(string $app, string $key, int $value, bool $lazy = false, bool $sensitive = false): bool {
		return $this->setValue($app, $key, $value, self::VALUE_INT, $lazy, $sensitive);
	}

	#[\Override]
	public function setValueFloat(string $app, string $key, float $value, bool $lazy = false, bool $sensitive = false): bool {
		return $this->setValue($app, $key, $value, self::VALUE_FLOAT, $lazy, $sensitive);
	}

	#[\Override]
	public function setValueBool(string $app, string $key, bool $value, bool $lazy = false): bool {
		return $this->setValue($app, $key, $value, self::VALUE_BOOL, $lazy, false);
	}

	#[\Override]
	public function setValueArray(string $app, string $key, array $value, bool $lazy = false, bool $sensitive = false): bool {
		return $this->setValue($app, $key, $value, self::VALUE_ARRAY, $lazy, $sensitive);
	}

	#[\Override]
	public function updateSensitive(string $app, string $key, bool $sensitive): bool {
		$this->appConfig[$app][$key]['sensitive'] = $sensitive;
		return true;
	}

	#[\Override]
	public function updateLazy(string $app, string $key, bool $lazy): bool {
		$this->appConfig[$app][$key]['lazy'] = $lazy;
		return true;
	}

	#[\Override]
	public function getDetails(string $app, string $key): array {
		return [
			'app' => $app,
			'key' => $key,
			'typeString' => $this->convertTypeToString($this->getValueType($app, $key)),
			'value' => $this->appConfig[$app][$key]['value'] ?? $this->lexicon[$app][$key]['default'],
			'type' => $this->appConfig[$app][$key]['type'] ?? $this->lexicon[$app][$key]['valueType'] ?? IAppConfig::VALUE_STRING,
			'lazy' => $this->appConfig[$app][$key]['lazy'] ?? $this->lexicon[$app][$key]['lazy'] ?? false,
			'sensitive' => $this->appConfig[$app][$key]['sensitive'] ?? $this->lexicon[$app][$key]['sensitive'] ?? false,
		];
	}

	#[\Override]
	public function getKeyDetails(string $app, string $key): array {
		return [
			'app' => $app,
			'key' => $key,
			'lazy' => $this->lexicon[$app][$key]['lazy'] ?? false,
			'valueType' => $this->lexicon[$app][$key]['valueType'] ?? IAppConfig::VALUE_STRING,
			'valueTypeName' => $this->lexicon[$app][$key]['valueTypeName'] ?? $this->convertTypeToString($this->lexicon[$app][$key]['valueType'] ?? IAppConfig::VALUE_STRING),
			'sensitive' => $this->lexicon[$app][$key]['sensitive'] ?? false,
			'internal' => $this->lexicon[$app][$key]['internal'] ?? false,
			'default' => $this->lexicon[$app][$key]['default'] ?? null,
			'definition' => $this->lexicon[$app][$key]['definition'] ?? null,
			'note' => $this->lexicon[$app][$key]['note'] ?? null,
		];
	}

	#[\Override]
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

	#[\Override]
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

	#[\Override]
	public function deleteKey(string $app, string $key): void {
		$this->appConfig[$app][$key] = null;
	}

	#[\Override]
	public function deleteApp(string $app): void {
		$this->appConfig[$app] = null;
	}

	#[\Override]
	public function clearCache(bool $reload = false): void {
		$this->appConfig = [];
	}

	#[\Override]
	public function getValues($app, $key) {
		if (($app !== false) === ($key !== false)) {
			return false;
		}

		$key = ($key === false) ? '' : $key;
		if (!$app) {
			return $this->searchValues($key, false, self::VALUE_MIXED);
		} else {
			return $this->getAllValues($app, $key);
		}
	}

	#[\Override]
	public function getFilteredValues($app) {
		return $this->getAllValues($app, filtered: true);
	}

	#[\Override]
	public function getAppInstalledVersions(bool $onlyEnabled = false): array {
		$appVersionsCache = $this->searchValues('installed_version', false, IAppConfig::VALUE_STRING);

		if ($onlyEnabled) {
			return array_filter(
				$appVersionsCache,
				fn (string $app): bool => $this->getValueString($app, 'enabled', 'no') !== 'no',
				ARRAY_FILTER_USE_KEY
			);
		}

		return $appVersionsCache;
	}
}

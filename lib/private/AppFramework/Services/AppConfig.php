<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Services;

use InvalidArgumentException;
use JsonException;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IConfig;

class AppConfig implements IAppConfig {
	public function __construct(
		private IConfig $config,
		/** @var \OC\AppConfig */
		private \OCP\IAppConfig $appConfig,
		private string $appName,
	) {
	}

	/**
	 * @inheritDoc
	 *
	 * @return string[] list of stored config keys
	 * @since 20.0.0
	 */
	public function getAppKeys(): array {
		return $this->appConfig->getKeys($this->appName);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param bool|null $lazy TRUE to search within lazy loaded config, NULL to search within all config
	 *
	 * @return bool TRUE if key exists
	 * @since 29.0.0
	 */
	public function hasAppKey(string $key, ?bool $lazy = false): bool {
		return $this->appConfig->hasKey($this->appName, $key, $lazy);
	}

	/**
	 * @param string $key config key
	 * @param bool|null $lazy TRUE to search within lazy loaded config, NULL to search within all config
	 *
	 * @return bool
	 * @throws AppConfigUnknownKeyException if config key is not known
	 * @since 29.0.0
	 */
	public function isSensitive(string $key, ?bool $lazy = false): bool {
		return $this->appConfig->isSensitive($this->appName, $key, $lazy);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 *
	 * @return bool TRUE if config is lazy loaded
	 * @throws AppConfigUnknownKeyException if config key is not known
	 * @see \OCP\IAppConfig for details about lazy loading
	 * @since 29.0.0
	 */
	public function isLazy(string $key): bool {
		return $this->appConfig->isLazy($this->appName, $key);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config keys prefix to search
	 * @param bool $filtered TRUE to hide sensitive config values. Value are replaced by {@see IConfig::SENSITIVE_VALUE}
	 *
	 * @return array<string, string|int|float|bool|array> [configKey => configValue]
	 * @since 29.0.0
	 */
	public function getAllAppValues(string $key = '', bool $filtered = false): array {
		return $this->appConfig->getAllValues($this->appName, $key, $filtered);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key the key of the value, under which will be saved
	 * @param string $value the value that should be stored
	 * @since 20.0.0
	 * @deprecated 29.0.0 use {@see setAppValueString()}
	 */
	public function setAppValue(string $key, string $value): void {
		/** @psalm-suppress InternalMethod */
		$this->appConfig->setValueMixed($this->appName, $key, $value);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function setAppValueString(
		string $key,
		string $value,
		bool $lazy = false,
		bool $sensitive = false,
	): bool {
		return $this->appConfig->setValueString($this->appName, $key, $value, $lazy, $sensitive);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param int $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function setAppValueInt(
		string $key,
		int $value,
		bool $lazy = false,
		bool $sensitive = false,
	): bool {
		return $this->appConfig->setValueInt($this->appName, $key, $value, $lazy, $sensitive);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param float $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function setAppValueFloat(
		string $key,
		float $value,
		bool $lazy = false,
		bool $sensitive = false,
	): bool {
		return $this->appConfig->setValueFloat($this->appName, $key, $value, $lazy, $sensitive);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param bool $value config value
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function setAppValueBool(
		string $key,
		bool $value,
		bool $lazy = false,
	): bool {
		return $this->appConfig->setValueBool($this->appName, $key, $value, $lazy);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param array $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @throws JsonException
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function setAppValueArray(
		string $key,
		array $value,
		bool $lazy = false,
		bool $sensitive = false,
	): bool {
		return $this->appConfig->setValueArray($this->appName, $key, $value, $lazy, $sensitive);
	}

	/**
	 * @param string $key
	 * @param string $default
	 *
	 * @since 20.0.0
	 * @deprecated 29.0.0 use {@see getAppValueString()}
	 * @return string
	 */
	public function getAppValue(string $key, string $default = ''): string {
		/** @psalm-suppress InternalMethod */
		/** @psalm-suppress UndefinedInterfaceMethod */
		return $this->appConfig->getValueMixed($this->appName, $key, $default);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param string $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return string stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws AppConfigTypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function getAppValueString(string $key, string $default = '', bool $lazy = false): string {
		return $this->appConfig->getValueString($this->appName, $key, $default, $lazy);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param int $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return int stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws AppConfigTypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function getAppValueInt(string $key, int $default = 0, bool $lazy = false): int {
		return $this->appConfig->getValueInt($this->appName, $key, $default, $lazy);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param float $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return float stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws AppConfigTypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function getAppValueFloat(string $key, float $default = 0, bool $lazy = false): float {
		return $this->appConfig->getValueFloat($this->appName, $key, $default, $lazy);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param bool $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return bool stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws AppConfigTypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function getAppValueBool(string $key, bool $default = false, bool $lazy = false): bool {
		return $this->appConfig->getValueBool($this->appName, $key, $default, $lazy);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param array $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return array stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws AppConfigTypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function getAppValueArray(string $key, array $default = [], bool $lazy = false): array {
		return $this->appConfig->getValueArray($this->appName, $key, $default, $lazy);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @since 20.0.0
	 */
	public function deleteAppValue(string $key): void {
		$this->appConfig->deleteKey($this->appName, $key);
	}

	/**
	 * @inheritDoc
	 *
	 * @since 20.0.0
	 */
	public function deleteAppValues(): void {
		$this->appConfig->deleteApp($this->appName);
	}

	public function setUserValue(string $userId, string $key, string $value, ?string $preCondition = null): void {
		$this->config->setUserValue($userId, $this->appName, $key, $value, $preCondition);
	}

	public function getUserValue(string $userId, string $key, string $default = ''): string {
		return $this->config->getUserValue($userId, $this->appName, $key, $default);
	}

	public function deleteUserValue(string $userId, string $key): void {
		$this->config->deleteUserValue($userId, $this->appName, $key);
	}

	/**
	 * Returns the installed versions of all apps
	 *
	 * @return array<string, string>
	 */
	public function getAppInstalledVersions(bool $onlyEnabled = false): array {
		return $this->appConfig->getAppInstalledVersions($onlyEnabled);
	}
}

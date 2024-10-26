<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Services;

use OCP\Exceptions\AppConfigUnknownKeyException;

/**
 * Wrapper for AppConfig for the AppFramework
 *
 * @since 20.0.0
 */
interface IAppConfig {
	/**
	 * Get all keys stored for this app
	 *
	 * @return string[] the keys stored for the app
	 * @since 20.0.0
	 */
	public function getAppKeys(): array;

	/**
	 * Check if a key exists in the list of stored config values.
	 *
	 * @param string $key config key
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return bool TRUE if key exists
	 * @since 29.0.0
	 */
	public function hasAppKey(string $key, ?bool $lazy = false): bool;

	/**
	 * best way to see if a value is set as sensitive (not displayed in report)
	 *
	 * @param string $key config key
	 * @param bool|null $lazy search within lazy loaded config
	 *
	 * @return bool TRUE if value is sensitive
	 * @throws AppConfigUnknownKeyException if config key is not known
	 * @since 29.0.0
	 */
	public function isSensitive(string $key, ?bool $lazy = false): bool;

	/**
	 * Returns if the config key stored in database is lazy loaded
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $key config key
	 *
	 * @return bool TRUE if config is lazy loaded
	 * @throws AppConfigUnknownKeyException if config key is not known
	 * @see IAppConfig for details about lazy loading
	 * @since 29.0.0
	 */
	public function isLazy(string $key): bool;

	/**
	 * List all config values from an app with config key starting with $key.
	 * Returns an array with config key as key, stored value as value.
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $key config keys prefix to search, can be empty.
	 * @param bool $filtered filter sensitive config values
	 *
	 * @return array<string, string|int|float|bool|array> [configKey => configValue]
	 * @since 29.0.0
	 */
	public function getAllAppValues(string $key = '', bool $filtered = false): array;

	/**
	 * Writes a new app wide value
	 *
	 * @param string $key the key of the value, under which will be saved
	 * @param string $value the value that should be stored
	 * @return void
	 * @since 20.0.0
	 * @deprecated 29.0.0 use {@see setAppValueString()}
	 */
	public function setAppValue(string $key, string $value): void;

	/**
	 * Store a config key and its value in database
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function setAppValueString(string $key, string $value, bool $lazy = false, bool $sensitive = false): bool;

	/**
	 * Store a config key and its value in database
	 *
	 * When handling huge value around and/or above 2,147,483,647, a debug log will be generated
	 * on 64bits system, as php int type reach its limit (and throw an exception) on 32bits when using huge numbers.
	 *
	 * When using huge numbers, it is advised to use {@see \OCP\Util::numericToNumber()} and {@see setValueString()}
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $key config key
	 * @param int $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function setAppValueInt(string $key, int $value, bool $lazy = false, bool $sensitive = false): bool;

	/**
	 * Store a config key and its value in database.
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $key config key
	 * @param float $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function setAppValueFloat(string $key, float $value, bool $lazy = false, bool $sensitive = false): bool;

	/**
	 * Store a config key and its value in database
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $key config key
	 * @param bool $value config value
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function setAppValueBool(string $key, bool $value, bool $lazy = false): bool;

	/**
	 * Store a config key and its value in database
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $key config key
	 * @param array $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function setAppValueArray(string $key, array $value, bool $lazy = false, bool $sensitive = false): bool;

	/**
	 * Looks up an app wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param string $default the default value to be returned if the value isn't set
	 *
	 * @return string the saved value
	 * @since 20.0.0
	 * @deprecated 29.0.0 use {@see getAppValueString()}
	 */
	public function getAppValue(string $key, string $default = ''): string;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $key config key
	 * @param string $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return string stored config value or $default if not set in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function getAppValueString(string $key, string $default = '', bool $lazy = false): string;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $key config key
	 * @param int $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return int stored config value or $default if not set in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function getAppValueInt(string $key, int $default = 0, bool $lazy = false): int;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $key config key
	 * @param float $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return float stored config value or $default if not set in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function getAppValueFloat(string $key, float $default = 0, bool $lazy = false): float;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $key config key
	 * @param bool $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return bool stored config value or $default if not set in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function getAppValueBool(string $key, bool $default = false, bool $lazy = false): bool;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $key config key
	 * @param array $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return array stored config value or $default if not set in database
	 * @since 29.0.0
	 * @see \OCP\IAppConfig for explanation about lazy loading
	 */
	public function getAppValueArray(string $key, array $default = [], bool $lazy = false): array;

	/**
	 * Delete an app wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @return void
	 * @since 20.0.0
	 */
	public function deleteAppValue(string $key): void;

	/**
	 * Removes all keys in appconfig belonging to the app
	 *
	 * @return void
	 * @since 20.0.0
	 */
	public function deleteAppValues(): void;

	/**
	 * Set a user defined value
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $key the key under which the value is being stored
	 * @param string $value the value that you want to store
	 * @param string $preCondition only update if the config value was previously the value passed as $preCondition
	 * @throws \OCP\PreConditionNotMetException if a precondition is specified and is not met
	 * @throws \UnexpectedValueException when trying to store an unexpected value
	 * @since 20.0.0
	 */
	public function setUserValue(string $userId, string $key, string $value, ?string $preCondition = null): void;

	/**
	 * Shortcut for getting a user defined value
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $key the key under which the value is being stored
	 * @param mixed $default the default value to be returned if the value isn't set
	 * @return string
	 * @since 20.0.0
	 */
	public function getUserValue(string $userId, string $key, string $default = ''): string;

	/**
	 * Delete a user value
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $key the key under which the value is being stored
	 * @since 20.0.0
	 */
	public function deleteUserValue(string $userId, string $key): void;
}

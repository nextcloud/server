<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP;

use OCP\Exceptions\AppConfigUnknownKeyException;

/**
 * This class provides an easy way for apps to store config values in the
 * database.
 *
 * **Note:** since 29.0.0, it supports **lazy loading**
 *
 * ### What is lazy loading ?
 * In order to avoid loading useless config values into memory for each request,
 * only non-lazy values are now loaded.
 *
 * Once a value that is lazy is requested, all lazy values will be loaded.
 *
 * Similarly, some methods from this class are marked with a warning about ignoring
 * lazy loading. Use them wisely and only on parts of the code that are called
 * during specific requests or actions to avoid loading the lazy values all the time.
 *
 * @since 7.0.0
 * @since 29.0.0 - Supporting types and lazy loading
 */
interface IAppConfig {
	/** @since 29.0.0 */
	public const VALUE_SENSITIVE = 1;
	/** @since 29.0.0 */
	public const VALUE_MIXED = 2;
	/** @since 29.0.0 */
	public const VALUE_STRING = 4;
	/** @since 29.0.0 */
	public const VALUE_INT = 8;
	/** @since 29.0.0 */
	public const VALUE_FLOAT = 16;
	/** @since 29.0.0 */
	public const VALUE_BOOL = 32;
	/** @since 29.0.0 */
	public const VALUE_ARRAY = 64;

	/**
	 * Get list of all apps that have at least one config value stored in database
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @return string[] list of app ids
	 * @since 7.0.0
	 */
	public function getApps(): array;

	/**
	 * Returns all keys stored in database, related to an app.
	 * Please note that the values are not returned.
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $app id of the app
	 *
	 * @return string[] list of stored config keys
	 * @since 29.0.0
	 */
	public function getKeys(string $app): array;

	/**
	 * Check if a key exists in the list of stored config values.
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return bool TRUE if key exists
	 * @since 29.0.0 Added the $lazy argument
	 * @since 7.0.0
	 */
	public function hasKey(string $app, string $key, ?bool $lazy = false): bool;

	/**
	 * best way to see if a value is set as sensitive (not displayed in report)
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool|null $lazy search within lazy loaded config
	 *
	 * @return bool TRUE if value is sensitive
	 * @throws AppConfigUnknownKeyException if config key is not known
	 * @since 29.0.0
	 */
	public function isSensitive(string $app, string $key, ?bool $lazy = false): bool;

	/**
	 * Returns if the config key stored in database is lazy loaded
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @return bool TRUE if config is lazy loaded
	 * @throws AppConfigUnknownKeyException if config key is not known
	 * @see IAppConfig for details about lazy loading
	 * @since 29.0.0
	 */
	public function isLazy(string $app, string $key): bool;

	/**
	 * List all config values from an app with config key starting with $key.
	 * Returns an array with config key as key, stored value as value.
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $app id of the app
	 * @param string $prefix config keys prefix to search, can be empty.
	 * @param bool $filtered filter sensitive config values
	 *
	 * @return array<string, string> [configKey => configValue]
	 * @since 29.0.0
	 */
	public function getAllValues(string $app, string $prefix = '', bool $filtered = false): array;

	/**
	 * List all apps storing a specific config key and its stored value.
	 * Returns an array with appId as key, stored value as value.
	 *
	 * @param string $key config key
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return array<string, string|int|float|bool|array> [appId => configValue]
	 * @since 29.0.0
	 */
	public function searchValues(string $key, bool $lazy = false): array;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return string stored config value or $default if not set in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see getValueInt()
	 * @see getValueFloat()
	 * @see getValueBool()
	 * @see getValueArray()
	 */
	public function getValueString(string $app, string $key, string $default = '', bool $lazy = false): string;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param int $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return int stored config value or $default if not set in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueFloat()
	 * @see getValueBool()
	 * @see getValueArray()
	 */
	public function getValueInt(string $app, string $key, int $default = 0, bool $lazy = false): int;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param float $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return float stored config value or $default if not set in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueInt()
	 * @see getValueBool()
	 * @see getValueArray()
	 */
	public function getValueFloat(string $app, string $key, float $default = 0, bool $lazy = false): float;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return bool stored config value or $default if not set in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueInt()
	 * @see getValueFloat()
	 * @see getValueArray()
	 */
	public function getValueBool(string $app, string $key, bool $default = false, bool $lazy = false): bool;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param array $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return array stored config value or $default if not set in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueInt()
	 * @see getValueFloat()
	 * @see getValueBool()
	 */
	public function getValueArray(string $app, string $key, array $default = [], bool $lazy = false): array;

	/**
	 * returns the type of config value
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @return int
	 * @throws AppConfigUnknownKeyException
	 * @since 29.0.0
	 * @see VALUE_STRING
	 * @see VALUE_INT
	 * @see VALUE_FLOAT
	 * @see VALUE_BOOL
	 * @see VALUE_ARRAY
	 */
	public function getValueType(string $app, string $key): int;

	/**
	 * Store a config key and its value in database
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see setValueInt()
	 * @see setValueFloat()
	 * @see setValueBool()
	 * @see setValueArray()
	 */
	public function setValueString(string $app, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool;

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
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param int $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueFloat()
	 * @see setValueBool()
	 * @see setValueArray()
	 */
	public function setValueInt(string $app, string $key, int $value, bool $lazy = false, bool $sensitive = false): bool;

	/**
	 * Store a config key and its value in database.
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param float $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueInt()
	 * @see setValueBool()
	 * @see setValueArray()
	 */
	public function setValueFloat(string $app, string $key, float $value, bool $lazy = false, bool $sensitive = false): bool;

	/**
	 * Store a config key and its value in database
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $value config value
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueInt()
	 * @see setValueFloat()
	 * @see setValueArray()
	 */
	public function setValueBool(string $app, string $key, bool $value, bool $lazy = false): bool;

	/**
	 * Store a config key and its value in database
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param array $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueInt()
	 * @see setValueFloat()
	 * @see setValueBool()
	 */
	public function setValueArray(string $app, string $key, array $value, bool $lazy = false, bool $sensitive = false): bool;

	/**
	 * switch sensitive status of a config value
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $sensitive TRUE to set as sensitive, FALSE to unset
	 *
	 * @return bool TRUE if database update were necessary
	 * @since 29.0.0
	 */
	public function updateSensitive(string $app, string $key, bool $sensitive): bool;

	/**
	 * switch lazy loading status of a config value
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $lazy TRUE to set as lazy loaded, FALSE to unset
	 *
	 * @return bool TRUE if database update was necessary
	 * @since 29.0.0
	 */
	public function updateLazy(string $app, string $key, bool $lazy): bool;

	/**
	 * returns an array contains details about a config value
	 *
	 * ```
	 * [
	 *   "app" => "myapp",
	 *   "key" => "mykey",
	 *   "value" => "its_value",
	 *   "lazy" => false,
	 *   "type" => 4,
	 *   "typeString" => "string",
	 *   'sensitive' => true
	 * ]
	 * ```
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @return array
	 * @throws AppConfigUnknownKeyException if config key is not known in database
	 * @since 29.0.0
	 */
	public function getDetails(string $app, string $key): array;

	/**
	 * Convert string like 'string', 'integer', 'float', 'bool' or 'array' to
	 * to bitflag {@see VALUE_STRING}, {@see VALUE_INT}, {@see VALUE_FLOAT},
	 * {@see VALUE_BOOL} and {@see VALUE_ARRAY}
	 *
	 * @param string $type
	 *
	 * @return int
	 * @since 29.0.0
	 */
	public function convertTypeToInt(string $type): int;

	/**
	 * Convert bitflag {@see VALUE_STRING}, {@see VALUE_INT}, {@see VALUE_FLOAT},
	 * {@see VALUE_BOOL} and {@see VALUE_ARRAY} to human-readable string
	 *
	 * @param int $type
	 *
	 * @return string
	 * @since 29.0.0
	 */
	public function convertTypeToString(int $type): string;

	/**
	 * Delete single config key from database.
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @since 29.0.0
	 */
	public function deleteKey(string $app, string $key): void;

	/**
	 * delete all config keys linked to an app
	 *
	 * @param string $app id of the app
	 * @since 29.0.0
	 */
	public function deleteApp(string $app): void;

	/**
	 * Clear the cache.
	 *
	 * The cache will be rebuilt only the next time a config value is requested.
	 *
	 * @param bool $reload set to TRUE to refill cache instantly after clearing it
	 * @since 29.0.0
	 */
	public function clearCache(bool $reload = false): void;

	/**
	 * get multiply values, either the app or key can be used as wildcard by setting it to false
	 *
	 * @param string|false $key
	 * @param string|false $app
	 *
	 * @return array|false
	 * @since 7.0.0
	 * @deprecated 29.0.0 Use {@see getAllValues()} or {@see searchValues()}
	 */
	public function getValues($app, $key);

	/**
	 * get all values of the app or and filters out sensitive data
	 *
	 * @param string $app
	 *
	 * @return array
	 * @since 12.0.0
	 * @deprecated 29.0.0 Use {@see getAllValues()} or {@see searchValues()}
	 */
	public function getFilteredValues($app);
}

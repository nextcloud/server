<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Config;

use Generator;
use NCU\Config\Exceptions\IncorrectTypeException;
use NCU\Config\Exceptions\UnknownKeyException;

/**
 * This class provides an easy way for apps to store user config in the
 * database.
 * Supports **lazy loading**
 *
 * ### What is lazy loading ?
 * In order to avoid loading useless user config into memory for each request,
 * only non-lazy values are now loaded.
 *
 * Once a value that is lazy is requested, all lazy values will be loaded.
 *
 * Similarly, some methods from this class are marked with a warning about ignoring
 * lazy loading. Use them wisely and only on parts of the code that are called
 * during specific requests or actions to avoid loading the lazy values all the time.
 *
 * @experimental 31.0.0
 */
interface IUserConfig {
	/**
	 * @experimental 31.0.0
	 */
	public const FLAG_SENSITIVE = 1;   // value is sensitive
	/**
	 * @experimental 31.0.0
	 */
	public const FLAG_INDEXED = 2;    // value should be indexed

	/**
	 * Get list of all userIds with config stored in database.
	 * If $appId is specified, will only limit the search to this value
	 *
	 * **WARNING:** ignore any cache and get data directly from database.
	 *
	 * @param string $appId optional id of app
	 *
	 * @return list<string> list of userIds
	 *
	 * @experimental 31.0.0
	 */
	public function getUserIds(string $appId = ''): array;

	/**
	 * Get list of all apps that have at least one config
	 * value related to $userId stored in database
	 *
	 * **WARNING:** ignore lazy filtering, all user config are loaded from database
	 *
	 * @param string $userId id of the user
	 *
	 * @return list<string> list of app ids
	 *
	 * @experimental 31.0.0
	 */
	public function getApps(string $userId): array;

	/**
	 * Returns all keys stored in database, related to user+app.
	 * Please note that the values are not returned.
	 *
	 * **WARNING:** ignore lazy filtering, all user config are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 *
	 * @return list<string> list of stored config keys
	 *
	 * @experimental 31.0.0
	 */
	public function getKeys(string $userId, string $app): array;

	/**
	 * Check if a key exists in the list of stored config values.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return bool TRUE if key exists
	 *
	 * @experimental 31.0.0
	 */
	public function hasKey(string $userId, string $app, string $key, ?bool $lazy = false): bool;

	/**
	 * best way to see if a value is set as sensitive (not displayed in report)
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool|null $lazy search within lazy loaded config
	 *
	 * @return bool TRUE if value is sensitive
	 * @throws UnknownKeyException if config key is not known
	 *
	 * @experimental 31.0.0
	 */
	public function isSensitive(string $userId, string $app, string $key, ?bool $lazy = false): bool;

	/**
	 * best way to see if a value is set as indexed (so it can be search)
	 *
	 * @see self::searchUsersByValueString()
	 * @see self::searchUsersByValueInt()
	 * @see self::searchUsersByValueBool()
	 * @see self::searchUsersByValues()
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool|null $lazy search within lazy loaded config
	 *
	 * @return bool TRUE if value is sensitive
	 * @throws UnknownKeyException if config key is not known
	 *
	 * @experimental 31.0.0
	 */
	public function isIndexed(string $userId, string $app, string $key, ?bool $lazy = false): bool;

	/**
	 * Returns if the config key stored in database is lazy loaded
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @return bool TRUE if config is lazy loaded
	 * @throws UnknownKeyException if config key is not known
	 * @see IUserConfig for details about lazy loading
	 *
	 * @experimental 31.0.0
	 */
	public function isLazy(string $userId, string $app, string $key): bool;

	/**
	 * List all config values from an app with config key starting with $key.
	 * Returns an array with config key as key, stored value as value.
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $prefix config keys prefix to search, can be empty.
	 * @param bool $filtered filter sensitive config values
	 *
	 * @return array<string, string|int|float|bool|array> [key => value]
	 *
	 * @experimental 31.0.0
	 */
	public function getValues(string $userId, string $app, string $prefix = '', bool $filtered = false): array;

	/**
	 * List all config values of a user.
	 * Returns an array with config key as key, stored value as value.
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param bool $filtered filter sensitive config values
	 *
	 * @return array<string, string|int|float|bool|array> [key => value]
	 *
	 * @experimental 31.0.0
	 */
	public function getAllValues(string $userId, bool $filtered = false): array;

	/**
	 * List all apps storing a specific config key and its stored value.
	 * Returns an array with appId as key, stored value as value.
	 *
	 * @param string $userId id of the user
	 * @param string $key config key
	 * @param bool $lazy search within lazy loaded config
	 * @param ValueType|null $typedAs enforce type for the returned values
	 *
	 * @return array<string, string|int|float|bool|array> [appId => value]
	 *
	 * @experimental 31.0.0
	 */
	public function getValuesByApps(string $userId, string $key, bool $lazy = false, ?ValueType $typedAs = null): array;

	/**
	 * List all users storing a specific config key and its stored value.
	 * Returns an array with userId as key, stored value as value.
	 *
	 * **WARNING:** no caching, generate a fresh request
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param ValueType|null $typedAs enforce type for the returned values
	 * @param array|null $userIds limit the search to a list of user ids
	 *
	 * @return array<string, string|int|float|bool|array> [userId => value]
	 *
	 * @experimental 31.0.0
	 */
	public function getValuesByUsers(string $app, string $key, ?ValueType $typedAs = null, ?array $userIds = null): array;

	/**
	 * List all users storing a specific config key/value pair.
	 * Returns a list of user ids.
	 *
	 * **WARNING:** no caching, generate a fresh request
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $caseInsensitive non-case-sensitive search, only works if $value is a string
	 *
	 * @return Generator<string>
	 *
	 * @experimental 31.0.0
	 */
	public function searchUsersByValueString(string $app, string $key, string $value, bool $caseInsensitive = false): Generator;

	/**
	 * List all users storing a specific config key/value pair.
	 * Returns a list of user ids.
	 *
	 * **WARNING:** no caching, generate a fresh request
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param int $value config value
	 *
	 * @return Generator<string>
	 *
	 * @experimental 31.0.0
	 */
	public function searchUsersByValueInt(string $app, string $key, int $value): Generator;

	/**
	 * List all users storing a specific config key/value pair.
	 * Returns a list of user ids.
	 *
	 * **WARNING:** no caching, generate a fresh request
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param array $values list of possible config values
	 *
	 * @return Generator<string>
	 *
	 * @experimental 31.0.0
	 */
	public function searchUsersByValues(string $app, string $key, array $values): Generator;

	/**
	 * List all users storing a specific config key/value pair.
	 * Returns a list of user ids.
	 *
	 * **WARNING:** no caching, generate a fresh request
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $value config value
	 *
	 * @return Generator<string>
	 *
	 * @experimental 31.0.0
	 */
	public function searchUsersByValueBool(string $app, string $key, bool $value): Generator;

	/**
	 * Get user config assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return string stored config value or $default if not set in database
	 *
	 * @experimental 31.0.0
	 *
	 * @see IUserConfig for explanation about lazy loading
	 * @see getValueInt()
	 * @see getValueFloat()
	 * @see getValueBool()
	 * @see getValueArray()
	 */
	public function getValueString(string $userId, string $app, string $key, string $default = '', bool $lazy = false): string;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param int $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return int stored config value or $default if not set in database
	 *
	 * @experimental 31.0.0
	 *
	 * @see IUserConfig for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueFloat()
	 * @see getValueBool()
	 * @see getValueArray()
	 */
	public function getValueInt(string $userId, string $app, string $key, int $default = 0, bool $lazy = false): int;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param float $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return float stored config value or $default if not set in database
	 *
	 * @experimental 31.0.0
	 *
	 * @see IUserConfig for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueInt()
	 * @see getValueBool()
	 * @see getValueArray()
	 */
	public function getValueFloat(string $userId, string $app, string $key, float $default = 0, bool $lazy = false): float;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return bool stored config value or $default if not set in database
	 *
	 * @experimental 31.0.0
	 *
	 * @see IUserPrefences for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueInt()
	 * @see getValueFloat()
	 * @see getValueArray()
	 */
	public function getValueBool(string $userId, string $app, string $key, bool $default = false, bool $lazy = false): bool;

	/**
	 * Get config value assigned to a config key.
	 * If config key is not found in database, default value is returned.
	 * If config key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param array $default default value`
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return array stored config value or $default if not set in database
	 *
	 * @experimental 31.0.0
	 *
	 * @see IUserConfig for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueInt()
	 * @see getValueFloat()
	 * @see getValueBool()
	 */
	public function getValueArray(string $userId, string $app, string $key, array $default = [], bool $lazy = false): array;

	/**
	 * returns the type of config value
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *              unless lazy is set to false
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool|null $lazy
	 *
	 * @return ValueType type of the value
	 * @throws UnknownKeyException if config key is not known
	 * @throws IncorrectTypeException if config value type is not known
	 *
	 * @experimental 31.0.0
	 */
	public function getValueType(string $userId, string $app, string $key, ?bool $lazy = null): ValueType;

	/**
	 * returns a bitflag related to config value
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *              unless lazy is set to false
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $lazy lazy loading
	 *
	 * @return int a bitflag in relation to the config value
	 * @throws UnknownKeyException if config key is not known
	 * @throws IncorrectTypeException if config value type is not known
	 *
	 * @experimental 31.0.0
	 */
	public function getValueFlags(string $userId, string $app, string $key, bool $lazy = false): int;

	/**
	 * Store a config key and its value in database
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 *
	 * @experimental 31.0.0
	 *
	 * @see IUserConfig for explanation about lazy loading
	 * @see setValueInt()
	 * @see setValueFloat()
	 * @see setValueBool()
	 * @see setValueArray()
	 */
	public function setValueString(string $userId, string $app, string $key, string $value, bool $lazy = false, int $flags = 0): bool;

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
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param int $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 *
	 * @experimental 31.0.0
	 *
	 * @see IUserConfig for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueFloat()
	 * @see setValueBool()
	 * @see setValueArray()
	 */
	public function setValueInt(string $userId, string $app, string $key, int $value, bool $lazy = false, int $flags = 0): bool;

	/**
	 * Store a config key and its value in database.
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param float $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 *
	 * @experimental 31.0.0
	 *
	 * @see IUserConfig for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueInt()
	 * @see setValueBool()
	 * @see setValueArray()
	 */
	public function setValueFloat(string $userId, string $app, string $key, float $value, bool $lazy = false, int $flags = 0): bool;

	/**
	 * Store a config key and its value in database
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $value config value
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 *
	 * @experimental 31.0.0
	 *
	 * @see IUserConfig for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueInt()
	 * @see setValueFloat()
	 * @see setValueArray()
	 */
	public function setValueBool(string $userId, string $app, string $key, bool $value, bool $lazy = false): bool;

	/**
	 * Store a config key and its value in database
	 *
	 * If config key is already known with the exact same config value, the database is not updated.
	 * If config key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If config value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param array $value config value
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 *
	 * @experimental 31.0.0
	 *
	 * @see IUserConfig for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueInt()
	 * @see setValueFloat()
	 * @see setValueBool()
	 */
	public function setValueArray(string $userId, string $app, string $key, array $value, bool $lazy = false, int $flags = 0): bool;

	/**
	 * switch sensitive status of a config value
	 *
	 * **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $sensitive TRUE to set as sensitive, FALSE to unset
	 *
	 * @return bool TRUE if database update were necessary
	 *
	 * @experimental 31.0.0
	 */
	public function updateSensitive(string $userId, string $app, string $key, bool $sensitive): bool;

	/**
	 * switch sensitive loading status of a config key for all users
	 *
	 * **Warning:** heavy on resources, MUST only be used on occ command or migrations
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $sensitive TRUE to set as sensitive, FALSE to unset
	 *
	 * @experimental 31.0.0
	 */
	public function updateGlobalSensitive(string $app, string $key, bool $sensitive): void;


	/**
	 * switch indexed status of a config value
	 *
	 *  **WARNING:** ignore lazy filtering, all config values are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $indexed TRUE to set as indexed, FALSE to unset
	 *
	 * @return bool TRUE if database update were necessary
	 *
	 * @experimental 31.0.0
	 */
	public function updateIndexed(string $userId, string $app, string $key, bool $indexed): bool;

	/**
	 * switch sensitive loading status of a config key for all users
	 *
	 * **Warning:** heavy on resources, MUST only be used on occ command or migrations
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $indexed TRUE to set as indexed, FALSE to unset
	 *
	 * @experimental 31.0.0
	 */
	public function updateGlobalIndexed(string $app, string $key, bool $indexed): void;

	/**
	 * switch lazy loading status of a config value
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $lazy TRUE to set as lazy loaded, FALSE to unset
	 *
	 * @return bool TRUE if database update was necessary
	 *
	 * @experimental 31.0.0
	 */
	public function updateLazy(string $userId, string $app, string $key, bool $lazy): bool;

	/**
	 * switch lazy loading status of a config key for all users
	 *
	 * **Warning:** heavy on resources, MUST only be used on occ command or migrations
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $lazy TRUE to set as lazy loaded, FALSE to unset
	 *
	 * @experimental 31.0.0
	 */
	public function updateGlobalLazy(string $app, string $key, bool $lazy): void;

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
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @return array
	 * @throws UnknownKeyException if config key is not known in database
	 *
	 * @experimental 31.0.0
	 */
	public function getDetails(string $userId, string $app, string $key): array;

	/**
	 * Delete single config key from database.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @experimental 31.0.0
	 */
	public function deleteUserConfig(string $userId, string $app, string $key): void;

	/**
	 * Delete config values from all users linked to a specific config keys
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @experimental 31.0.0
	 */
	public function deleteKey(string $app, string $key): void;

	/**
	 * delete all config keys linked to an app
	 *
	 * @param string $app id of the app
	 *
	 * @experimental 31.0.0
	 */
	public function deleteApp(string $app): void;

	/**
	 * delete all config keys linked to a user
	 *
	 * @param string $userId id of the user
	 *
	 * @experimental 31.0.0
	 */
	public function deleteAllUserConfig(string $userId): void;

	/**
	 * Clear the cache for a single user
	 *
	 * The cache will be rebuilt only the next time a user config is requested.
	 *
	 * @param string $userId id of the user
	 * @param bool $reload set to TRUE to refill cache instantly after clearing it
	 *
	 * @experimental 31.0.0
	 */
	public function clearCache(string $userId, bool $reload = false): void;

	/**
	 * Clear the cache for all users.
	 * The cache will be rebuilt only the next time a user config is requested.
	 *
	 * @experimental 31.0.0
	 */
	public function clearCacheAll(): void;
}

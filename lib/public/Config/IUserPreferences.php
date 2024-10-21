<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Config;

use Generator;
use OCP\Config\Exceptions\IncorrectTypeException;
use OCP\Config\Exceptions\UnknownKeyException;

/**
 * This class provides an easy way for apps to store user preferences in the
 * database.
 * Supports **lazy loading**
 *
 * ### What is lazy loading ?
 * In order to avoid loading useless user preferences into memory for each request,
 * only non-lazy values are now loaded.
 *
 * Once a value that is lazy is requested, all lazy values will be loaded.
 *
 * Similarly, some methods from this class are marked with a warning about ignoring
 * lazy loading. Use them wisely and only on parts of the code that are called
 * during specific requests or actions to avoid loading the lazy values all the time.
 *
 * @since 31.0.0
 */

interface IUserPreferences {
	/** @since 31.0.0 */
	public const FLAG_SENSITIVE = 1;   // value is sensitive
	/** @since 31.0.0 */
	public const FLAG_INDEXED = 2;    // value should be indexed

	/**
	 * Get list of all userIds with preferences stored in database.
	 * If $appId is specified, will only limit the search to this value
	 *
	 * **WARNING:** ignore any cache and get data directly from database.
	 *
	 * @param string $appId optional id of app
	 *
	 * @return list<string> list of userIds
	 * @since 31.0.0
	 */
	public function getUserIds(string $appId = ''): array;

	/**
	 * Get list of all apps that have at least one preference
	 * value related to $userId stored in database
	 *
	 * **WARNING:** ignore lazy filtering, all user preferences are loaded from database
	 *
	 * @param string $userId id of the user
	 *
	 * @return list<string> list of app ids
	 * @since 31.0.0
	 */
	public function getApps(string $userId): array;

	/**
	 * Returns all keys stored in database, related to user+app.
	 * Please note that the values are not returned.
	 *
	 * **WARNING:** ignore lazy filtering, all user preferences are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 *
	 * @return list<string> list of stored preference keys
	 * @since 31.0.0
	 */
	public function getKeys(string $userId, string $app): array;

	/**
	 * Check if a key exists in the list of stored preference values.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $lazy search within lazy loaded preferences
	 *
	 * @return bool TRUE if key exists
	 * @since 31.0.0
	 */
	public function hasKey(string $userId, string $app, string $key, ?bool $lazy = false): bool;

	/**
	 * best way to see if a value is set as sensitive (not displayed in report)
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool|null $lazy search within lazy loaded preferences
	 *
	 * @return bool TRUE if value is sensitive
	 * @throws UnknownKeyException if preference key is not known
	 * @since 31.0.0
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
	 * @param string $key preference key
	 * @param bool|null $lazy search within lazy loaded preferences
	 *
	 * @return bool TRUE if value is sensitive
	 * @throws UnknownKeyException if preference key is not known
	 * @since 31.0.0
	 */
	public function isIndexed(string $userId, string $app, string $key, ?bool $lazy = false): bool;

	/**
	 * Returns if the preference key stored in database is lazy loaded
	 *
	 * **WARNING:** ignore lazy filtering, all preference values are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 *
	 * @return bool TRUE if preference is lazy loaded
	 * @throws UnknownKeyException if preference key is not known
	 * @see IUserPreferences for details about lazy loading
	 * @since 31.0.0
	 */
	public function isLazy(string $userId, string $app, string $key): bool;

	/**
	 * List all preference values from an app with preference key starting with $key.
	 * Returns an array with preference key as key, stored value as value.
	 *
	 * **WARNING:** ignore lazy filtering, all preference values are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $prefix preference keys prefix to search, can be empty.
	 * @param bool $filtered filter sensitive preference values
	 *
	 * @return array<string, string|int|float|bool|array> [key => value]
	 * @since 31.0.0
	 */
	public function getValues(string $userId, string $app, string $prefix = '', bool $filtered = false): array;

	/**
	 * List all preference values of a user.
	 * Returns an array with preference key as key, stored value as value.
	 *
	 * **WARNING:** ignore lazy filtering, all preference values are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param bool $filtered filter sensitive preference values
	 *
	 * @return array<string, string|int|float|bool|array> [key => value]
	 * @since 31.0.0
	 */
	public function getAllValues(string $userId, bool $filtered = false): array;

	/**
	 * List all apps storing a specific preference key and its stored value.
	 * Returns an array with appId as key, stored value as value.
	 *
	 * @param string $userId id of the user
	 * @param string $key preference key
	 * @param bool $lazy search within lazy loaded preferences
	 * @param ValueType|null $typedAs enforce type for the returned values
	 *
	 * @return array<string, string|int|float|bool|array> [appId => value]
	 * @since 31.0.0
	 */
	public function getValuesByApps(string $userId, string $key, bool $lazy = false, ?ValueType $typedAs = null): array;

	/**
	 * List all users storing a specific preference key and its stored value.
	 * Returns an array with userId as key, stored value as value.
	 *
	 * **WARNING:** no caching, generate a fresh request
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param ValueType|null $typedAs enforce type for the returned values
	 * @param array|null $userIds limit the search to a list of user ids
	 *
	 * @return array<string, string|int|float|bool|array> [userId => value]
	 * @since 31.0.0
	 */
	public function getValuesByUsers(string $app, string $key, ?ValueType $typedAs = null, ?array $userIds = null): array;

	/**
	 * List all users storing a specific preference key/value pair.
	 * Returns a list of user ids.
	 *
	 * **WARNING:** no caching, generate a fresh request
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param string $value preference value
	 * @param bool $caseInsensitive non-case-sensitive search, only works if $value is a string
	 *
	 * @return Generator<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValueString(string $app, string $key, string $value, bool $caseInsensitive = false): Generator;

	/**
	 * List all users storing a specific preference key/value pair.
	 * Returns a list of user ids.
	 *
	 * **WARNING:** no caching, generate a fresh request
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param int $value preference value
	 *
	 * @return Generator<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValueInt(string $app, string $key, int $value): Generator;

	/**
	 * List all users storing a specific preference key/value pair.
	 * Returns a list of user ids.
	 *
	 * **WARNING:** no caching, generate a fresh request
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param array $values list of possible preference values
	 *
	 * @return Generator<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValues(string $app, string $key, array $values): Generator;

	/**
	 * List all users storing a specific preference key/value pair.
	 * Returns a list of user ids.
	 *
	 * **WARNING:** no caching, generate a fresh request
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $value preference value
	 *
	 * @return Generator<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValueBool(string $app, string $key, bool $value): Generator;

	/**
	 * Get user preference assigned to a preference key.
	 * If preference key is not found in database, default value is returned.
	 * If preference key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param string $default default value
	 * @param bool $lazy search within lazy loaded preferences
	 *
	 * @return string stored preference value or $default if not set in database
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 * @see getValueInt()
	 * @see getValueFloat()
	 * @see getValueBool()
	 * @see getValueArray()
	 */
	public function getValueString(string $userId, string $app, string $key, string $default = '', bool $lazy = false): string;

	/**
	 * Get preference value assigned to a preference key.
	 * If preference key is not found in database, default value is returned.
	 * If preference key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param int $default default value
	 * @param bool $lazy search within lazy loaded preferences
	 *
	 * @return int stored preference value or $default if not set in database
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueFloat()
	 * @see getValueBool()
	 * @see getValueArray()
	 */
	public function getValueInt(string $userId, string $app, string $key, int $default = 0, bool $lazy = false): int;

	/**
	 * Get preference value assigned to a preference key.
	 * If preference key is not found in database, default value is returned.
	 * If preference key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param float $default default value
	 * @param bool $lazy search within lazy loaded preferences
	 *
	 * @return float stored preference value or $default if not set in database
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueInt()
	 * @see getValueBool()
	 * @see getValueArray()
	 */
	public function getValueFloat(string $userId, string $app, string $key, float $default = 0, bool $lazy = false): float;

	/**
	 * Get preference value assigned to a preference key.
	 * If preference key is not found in database, default value is returned.
	 * If preference key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $default default value
	 * @param bool $lazy search within lazy loaded preferences
	 *
	 * @return bool stored preference value or $default if not set in database
	 * @since 31.0.0
	 * @see IUserPrefences for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueInt()
	 * @see getValueFloat()
	 * @see getValueArray()
	 */
	public function getValueBool(string $userId, string $app, string $key, bool $default = false, bool $lazy = false): bool;

	/**
	 * Get preference value assigned to a preference key.
	 * If preference key is not found in database, default value is returned.
	 * If preference key is set as lazy loaded, the $lazy argument needs to be set to TRUE.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param array $default default value`
	 * @param bool $lazy search within lazy loaded preferences
	 *
	 * @return array stored preference value or $default if not set in database
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueInt()
	 * @see getValueFloat()
	 * @see getValueBool()
	 */
	public function getValueArray(string $userId, string $app, string $key, array $default = [], bool $lazy = false): array;

	/**
	 * returns the type of preference value
	 *
	 * **WARNING:** ignore lazy filtering, all preference values are loaded from database
	 *              unless lazy is set to false
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool|null $lazy
	 *
	 * @return ValueType type of the value
	 * @throws UnknownKeyException if preference key is not known
	 * @throws IncorrectTypeException if preferences value type is not known
	 * @since 31.0.0
	 */
	public function getValueType(string $userId, string $app, string $key, ?bool $lazy = null): ValueType;

	/**
	 * returns a bitflag related to preference value
	 *
	 * **WARNING:** ignore lazy filtering, all preference values are loaded from database
	 *              unless lazy is set to false
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $lazy lazy loading
	 *
	 * @return int a bitflag in relation to the preference value
	 * @throws UnknownKeyException if preference key is not known
	 * @throws IncorrectTypeException if preferences value type is not known
	 * @since 31.0.0
	 */
	public function getValueFlags(string $userId, string $app, string $key, bool $lazy = false): int;

	/**
	 * Store a preference key and its value in database
	 *
	 * If preference key is already known with the exact same preference value, the database is not updated.
	 * If preference key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If preference value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param string $value preference value
	 * @param bool $sensitive if TRUE value will be hidden when listing preference values.
	 * @param bool $lazy set preference as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 * @see setValueInt()
	 * @see setValueFloat()
	 * @see setValueBool()
	 * @see setValueArray()
	 */
	public function setValueString(string $userId, string $app, string $key, string $value, bool $lazy = false, int $flags = 0): bool;

	/**
	 * Store a preference key and its value in database
	 *
	 * When handling huge value around and/or above 2,147,483,647, a debug log will be generated
	 * on 64bits system, as php int type reach its limit (and throw an exception) on 32bits when using huge numbers.
	 *
	 * When using huge numbers, it is advised to use {@see \OCP\Util::numericToNumber()} and {@see setValueString()}
	 *
	 * If preference key is already known with the exact same preference value, the database is not updated.
	 * If preference key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If preference value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param int $value preference value
	 * @param bool $sensitive if TRUE value will be hidden when listing preference values.
	 * @param bool $lazy set preference as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueFloat()
	 * @see setValueBool()
	 * @see setValueArray()
	 */
	public function setValueInt(string $userId, string $app, string $key, int $value, bool $lazy = false, int $flags = 0): bool;

	/**
	 * Store a preference key and its value in database.
	 *
	 * If preference key is already known with the exact same preference value, the database is not updated.
	 * If preference key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If preference value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param float $value preference value
	 * @param bool $sensitive if TRUE value will be hidden when listing preference values.
	 * @param bool $lazy set preference as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueInt()
	 * @see setValueBool()
	 * @see setValueArray()
	 */
	public function setValueFloat(string $userId, string $app, string $key, float $value, bool $lazy = false, int $flags = 0): bool;

	/**
	 * Store a preference key and its value in database
	 *
	 * If preference key is already known with the exact same preference value, the database is not updated.
	 * If preference key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If preference value was previously stored as lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $value preference value
	 * @param bool $lazy set preference as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueInt()
	 * @see setValueFloat()
	 * @see setValueArray()
	 */
	public function setValueBool(string $userId, string $app, string $key, bool $value, bool $lazy = false): bool;

	/**
	 * Store a preference key and its value in database
	 *
	 * If preference key is already known with the exact same preference value, the database is not updated.
	 * If preference key is not supposed to be read during the boot of the cloud, it is advised to set it as lazy loaded.
	 *
	 * If preference value was previously stored as sensitive or lazy loaded, status cannot be altered without using {@see deleteKey()} first
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param array $value preference value
	 * @param bool $sensitive if TRUE value will be hidden when listing preference values.
	 * @param bool $lazy set preference as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueInt()
	 * @see setValueFloat()
	 * @see setValueBool()
	 */
	public function setValueArray(string $userId, string $app, string $key, array $value, bool $lazy = false, int $flags = 0): bool;

	/**
	 * switch sensitive status of a preference value
	 *
	 * **WARNING:** ignore lazy filtering, all preference values are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $sensitive TRUE to set as sensitive, FALSE to unset
	 *
	 * @return bool TRUE if database update were necessary
	 * @since 31.0.0
	 */
	public function updateSensitive(string $userId, string $app, string $key, bool $sensitive): bool;

	/**
	 * switch sensitive loading status of a preference key for all users
	 *
	 * **Warning:** heavy on resources, MUST only be used on occ command or migrations
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $sensitive TRUE to set as sensitive, FALSE to unset
	 *
	 * @since 31.0.0
	 */
	public function updateGlobalSensitive(string $app, string $key, bool $sensitive): void;


	/**
	 * switch indexed status of a preference value
	 *
	 *  **WARNING:** ignore lazy filtering, all preference values are loaded from database
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $indexed TRUE to set as indexed, FALSE to unset
	 *
	 * @return bool TRUE if database update were necessary
	 * @since 31.0.0
	 */
	public function updateIndexed(string $userId, string $app, string $key, bool $indexed): bool;

	/**
	 * switch sensitive loading status of a preference key for all users
	 *
	 * **Warning:** heavy on resources, MUST only be used on occ command or migrations
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $indexed TRUE to set as indexed, FALSE to unset
	 * @since 31.0.0
	 */
	public function updateGlobalIndexed(string $app, string $key, bool $indexed): void;

	/**
	 * switch lazy loading status of a preference value
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $lazy TRUE to set as lazy loaded, FALSE to unset
	 *
	 * @return bool TRUE if database update was necessary
	 * @since 31.0.0
	 */
	public function updateLazy(string $userId, string $app, string $key, bool $lazy): bool;

	/**
	 * switch lazy loading status of a preference key for all users
	 *
	 * **Warning:** heavy on resources, MUST only be used on occ command or migrations
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $lazy TRUE to set as lazy loaded, FALSE to unset
	 * @since 31.0.0
	 */
	public function updateGlobalLazy(string $app, string $key, bool $lazy): void;

	/**
	 * returns an array contains details about a preference value
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
	 * @param string $key preference key
	 *
	 * @return array
	 * @throws UnknownKeyException if preference key is not known in database
	 * @since 31.0.0
	 */
	public function getDetails(string $userId, string $app, string $key): array;

	/**
	 * Delete single preference key from database.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 *
	 * @since 31.0.0
	 */
	public function deletePreference(string $userId, string $app, string $key): void;

	/**
	 * Delete preference values from all users linked to a specific preference keys
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 *
	 * @since 31.0.0
	 */
	public function deleteKey(string $app, string $key): void;

	/**
	 * delete all preference keys linked to an app
	 *
	 * @param string $app id of the app
	 * @since 31.0.0
	 */
	public function deleteApp(string $app): void;

	/**
	 * delete all preference keys linked to a user
	 *
	 * @param string $userId id of the user
	 * @since 31.0.0
	 */
	public function deleteAllPreferences(string $userId): void;

	/**
	 * Clear the cache for a single user
	 *
	 * The cache will be rebuilt only the next time a user preference is requested.
	 *
	 * @param string $userId id of the user
	 * @param bool $reload set to TRUE to refill cache instantly after clearing it
	 *
	 * @since 31.0.0
	 */
	public function clearCache(string $userId, bool $reload = false): void;

	/**
	 * Clear the cache for all users.
	 * The cache will be rebuilt only the next time a user preference is requested.
	 *
	 * @since 31.0.0
	 */
	public function clearCacheAll(): void;
}

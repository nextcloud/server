<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Maxence Lange <maxence@artificial-owl.com>
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


namespace OCP\AppFramework\Services;

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

//
//	/**
//	 * set default values for any config values related to your app
//	 *
//	 * @param array $default
//	 *
//	 * @since 25.0.0
//	 */
//	public function setAppDefaultValues(array $default): void;
//
//	/**
//	 * set default values for any config values related to users
//	 *
//	 * @param array $default
//	 *
//	 * @since 25.0.0
//	 */
//	public function setUserDefaultValues(array $default): void;


	/**
	 * Writes a new app wide value
	 *
	 * @param string $key the key of the value, under which will be saved
	 * @param string $value the value that should be stored
	 *
	 * @return void
	 * @since 20.0.0
	 */
	public function setAppValue(string $key, string $value): void;

	/**
	 * store an app wide value as integer
	 *
	 * @param string $key
	 * @param int $value
	 *
	 * @since 25.0.0
	 */
	public function setAppValueInt(string $key, int $value): void;

	/**
	 * store an app wide value as bool
	 *
	 * @param string $key
	 * @param bool $value
	 *
	 * @since 25.0.0
	 */
	public function setAppValueBool(string $key, bool $value): void;

	/**
	 * store an app wide value as array
	 *
	 * @param string $key
	 * @param array $value
	 *
	 * @since 25.0.0
	 */
	public function setAppValueArray(string $key, array $value): void;

	/**
	 * Looks up an app wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param string $default the default value to be returned if the value isn't set
	 *
	 * string $default is deprecated since 25.0.0:
	 * @see setAppDefaultValues();
	 *
	 * @since 20.0.0
	 */
	public function getAppValue(string $key, string $default = ''): string;

	/**
	 * looks up app wide defined value as integer
	 *
	 * @param string $key
	 *
	 * @return int
	 * @since 25.0.0
	 */
	public function getAppValueInt(string $key): int;

	/**
	 * looks up app wide defined value as bool
	 *
	 * @param string $key
	 *
	 * @return bool
	 * @since 25.0.0
	 */
	public function getAppValueBool(string $key): bool;

	/**
	 * looks up app wide defined value as array
	 *
	 * @param string $key
	 *
	 * @return array
	 * @since 25.0.0
	 */
	public function getAppValueArray(string $key): array;

	/**
	 * Delete an app wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 *
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
	 * @param string $preCondition only update if the config value was previously the value passed as
	 *     $preCondition
	 *
	 * @throws \OCP\PreConditionNotMetException if a precondition is specified and is not met
	 * @throws \UnexpectedValueException when trying to store an unexpected value
	 * @since 20.0.0
	 */
	public function setUserValue(
		string $userId,
		string $key,
		string $value,
		?string $preCondition = null
	): void;

	/**
	 * Set a user defined value as integer
	 *
	 * @param string $userId
	 * @param string $key
	 * @param int $value
	 * @param int|null $preCondition if current user config value for $key from database is not the one
	 * specified as $preCondition, the method will fail silently
	 *
	 * @since 25.0.0
	 */
	public function setUserValueInt(
		string $userId,
		string $key,
		int $value,
		?int $preCondition = null
	): void;

	/**
	 * Set a user defined value as bool
	 *
	 * @param string $userId
	 * @param string $key
	 * @param bool $value
	 * @param bool|null $preCondition
	 */
	public function setUserValueBool(
		string $userId,
		string $key,
		bool $value,
		?bool $preCondition = null
	): void;

	/**
	 * Set a user defined value as array
	 *
	 * @param string $userId
	 * @param string $key
	 * @param array $value
	 * @param array|null $preCondition
	 */
	public function setUserValueArray(
		string $userId,
		string $key,
		array $value,
		?array $preCondition = null
	): void;


	/**
	 * Shortcut for getting a user defined value
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $key the key under which the value is being stored
	 * @param mixed $default the default value to be returned if the value isn't set
	 *
	 * string $default is deprecated since 25.0.0:
	 * @see setUserDefaultValues();
	 *
	 * @return string
	 * @since 20.0.0
	 */
	public function getUserValue(string $userId, string $key, string $default = ''): string;

	/**
	 * get user defined value as integer
	 *
	 * @param string $userId
	 * @param string $key
	 *
	 * @return int
	 * @since 25.0.0
	 */
	public function getUserValueInt(string $userId, string $key): int;

	/**
	 * get user defined value as integer
	 *
	 * @param string $userId
	 * @param string $key
	 *
	 * @return bool
	 * @since 25.0.0
	 */
	public function getUserValueBool(string $userId, string $key): bool;

	/**
	 * get user defined value as integer
	 *
	 * @param string $userId
	 * @param string $key
	 *
	 * @return array
	 * @since 25.0.0
	 */
	public function getUserValueArray(string $userId, string $key): array;

	/**
	 * Delete a user value
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $key the key under which the value is being stored
	 *
	 * @since 20.0.0
	 */
	public function deleteUserValue(string $userId, string $key): void;
}

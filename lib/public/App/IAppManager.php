<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\App;

use OCP\IGroup;
use OCP\IUser;

/**
 * Interface IAppManager
 *
 * @warning This interface shouldn't be included with dependency injection in
 *          classes used for installing Nextcloud.
 *
 * @since 8.0.0
 */
interface IAppManager {
	/**
	 * @since 30.0.0
	 */
	public const BACKEND_CALDAV = 'caldav';

	/**
	 * Returns the app information from "appinfo/info.xml" for an app
	 *
	 * @param string|null $lang
	 * @return array|null
	 * @since 14.0.0
	 * @since 31.0.0 Usage of $path is discontinued and throws an \InvalidArgumentException, use {@see self::getAppInfoByPath} instead.
	 */
	public function getAppInfo(string $appId, bool $path = false, $lang = null);

	/**
	 * Returns the app information from a given path ending with "/appinfo/info.xml"
	 *
	 * @since 31.0.0
	 */
	public function getAppInfoByPath(string $path, ?string $lang = null): ?array;

	/**
	 * Returns the app information from "appinfo/info.xml".
	 *
	 * @param string $appId
	 * @param bool $useCache
	 * @return string
	 * @since 14.0.0
	 */
	public function getAppVersion(string $appId, bool $useCache = true): string;

	/**
	 * Returns the installed version of all apps
	 *
	 * @return array<string, string>
	 * @since 32.0.0
	 */
	public function getAppInstalledVersions(bool $onlyEnabled = false): array;

	/**
	 * Returns the app icon or null if none is found
	 *
	 * @param string $appId
	 * @param bool $dark Enable to request a dark icon variant, default is a white icon
	 * @return string|null
	 * @since 29.0.0
	 */
	public function getAppIcon(string $appId, bool $dark = false): ?string;

	/**
	 * Check if an app is enabled for user
	 *
	 * @param string $appId
	 * @param \OCP\IUser|null $user (optional) if not defined, the currently loggedin user will be used
	 * @return bool
	 * @since 8.0.0
	 */
	public function isEnabledForUser($appId, $user = null);

	/**
	 * Check if an app is enabled in the instance
	 *
	 * Notice: This actually checks if the app is enabled and not only if it is installed.
	 *
	 * @param string $appId
	 * @return bool
	 * @since 8.0.0
	 * @deprecated 32.0.0 Use either {@see self::isEnabledForUser} or {@see self::isEnabledForAnyone}
	 */
	public function isInstalled($appId);

	/**
	 * Check if an app is enabled in the instance, either for everyone or for specific groups
	 *
	 * @since 32.0.0
	 */
	public function isEnabledForAnyone(string $appId): bool;

	/**
	 * Check if an app should be enabled by default
	 *
	 * Notice: This actually checks if the app should be enabled by default
	 * and not if currently installed/enabled
	 *
	 * @param string $appId ID of the app
	 * @since 25.0.0
	 */
	public function isDefaultEnabled(string $appId):bool;

	/**
	 * Load an app, if not already loaded
	 * @param string $app app id
	 * @since 27.0.0
	 */
	public function loadApp(string $app): void;

	/**
	 * Check if an app is loaded
	 * @param string $app app id
	 * @since 27.0.0
	 */
	public function isAppLoaded(string $app): bool;

	/**
	 * Enable an app for every user
	 *
	 * @param string $appId
	 * @param bool $forceEnable
	 * @throws AppPathNotFoundException
	 * @since 8.0.0
	 */
	public function enableApp(string $appId, bool $forceEnable = false): void;

	/**
	 * Whether a list of types contains a protected app type
	 *
	 * @param string[] $types
	 * @return bool
	 * @since 12.0.0
	 */
	public function hasProtectedAppType($types);

	/**
	 * Enable an app only for specific groups
	 *
	 * @param string $appId
	 * @param \OCP\IGroup[] $groups
	 * @param bool $forceEnable
	 * @throws \Exception
	 * @since 8.0.0
	 */
	public function enableAppForGroups(string $appId, array $groups, bool $forceEnable = false): void;

	/**
	 * Disable an app for every user
	 *
	 * @param string $appId
	 * @param bool $automaticDisabled
	 * @since 8.0.0
	 */
	public function disableApp($appId, $automaticDisabled = false): void;

	/**
	 * Get the directory for the given app.
	 *
	 * @since 11.0.0
	 * @throws AppPathNotFoundException
	 */
	public function getAppPath(string $appId): string;

	/**
	 * Get the web path for the given app.
	 *
	 * @param string $appId
	 * @return string
	 * @since 18.0.0
	 * @throws AppPathNotFoundException
	 */
	public function getAppWebPath(string $appId): string;

	/**
	 * List all apps enabled for a user
	 *
	 * @param \OCP\IUser $user
	 * @return list<string>
	 * @since 8.1.0
	 */
	public function getEnabledAppsForUser(IUser $user);

	/**
	 * List all installed apps
	 *
	 * @return string[]
	 * @since 8.1.0
	 * @deprecated 32.0.0 Use either {@see self::getEnabledApps} or {@see self::getEnabledAppsForUser}
	 */
	public function getInstalledApps();

	/**
	 * List all apps enabled, either for everyone or for specific groups only
	 *
	 * @return list<string>
	 * @since 32.0.0
	 */
	public function getEnabledApps(): array;

	/**
	 * Clear the cached list of apps when enabling/disabling an app
	 * @since 8.1.0
	 */
	public function clearAppsCache(): void;

	/**
	 * @param string $appId
	 * @return boolean
	 * @since 9.0.0
	 */
	public function isShipped($appId);

	/**
	 * Loads all apps
	 *
	 * @param string[] $types
	 * @return bool
	 *
	 * This function walks through the Nextcloud directory and loads all apps
	 * it can find. A directory contains an app if the file `/appinfo/info.xml`
	 * exists.
	 *
	 * if $types is set to non-empty array, only apps of those types will be loaded
	 * @since 27.0.0
	 */
	public function loadApps(array $types = []): bool;

	/**
	 * Check if an app is of a specific type
	 * @since 27.0.0
	 */
	public function isType(string $app, array $types): bool;

	/**
	 * @return string[]
	 * @since 9.0.0
	 */
	public function getAlwaysEnabledApps();

	/**
	 * @return string[] app IDs
	 * @since 25.0.0
	 */
	public function getDefaultEnabledApps(): array;

	/**
	 * @param \OCP\IGroup $group
	 * @return String[]
	 * @since 17.0.0
	 */
	public function getEnabledAppsForGroup(IGroup $group): array;

	/**
	 * @param String $appId
	 * @return string[]
	 * @since 17.0.0
	 */
	public function getAppRestriction(string $appId): array;

	/**
	 * Returns the id of the user's default app
	 *
	 * If `user` is not passed, the currently logged in user will be used
	 *
	 * @param ?IUser $user User to query default app for
	 * @param bool $withFallbacks Include fallback values if no default app was configured manually
	 *                            Before falling back to predefined default apps,
	 *                            the user defined app order is considered and the first app would be used as the fallback.
	 *
	 * @since 25.0.6
	 * @since 28.0.0 Added optional $withFallbacks parameter
	 * @deprecated 31.0.0
	 * Use @see \OCP\INavigationManager::getDefaultEntryIdForUser() instead
	 */
	public function getDefaultAppForUser(?IUser $user = null, bool $withFallbacks = true): string;

	/**
	 * Get the global default apps with fallbacks
	 *
	 * @return string[] The default applications
	 * @since 28.0.0
	 * @deprecated 31.0.0
	 * Use @see \OCP\INavigationManager::getDefaultEntryIds() instead
	 */
	public function getDefaultApps(): array;

	/**
	 * Set the global default apps with fallbacks
	 *
	 * @param string[] $defaultApps
	 * @throws \InvalidArgumentException If any of the apps is not installed
	 * @since 28.0.0
	 * @deprecated 31.0.0
	 * Use @see \OCP\INavigationManager::setDefaultEntryIds() instead
	 */
	public function setDefaultApps(array $defaultApps): void;

	/**
	 * Check whether the given backend is required by at least one app.
	 *
	 * @param self::BACKEND_* $backend Name of the backend, one of `self::BACKEND_*`
	 * @return bool True if at least one app requires the backend
	 *
	 * @since 30.0.0
	 */
	public function isBackendRequired(string $backend): bool;

	/**
	 * Clean the appId from forbidden characters
	 *
	 * @psalm-taint-escape callable
	 * @psalm-taint-escape cookie
	 * @psalm-taint-escape file
	 * @psalm-taint-escape has_quotes
	 * @psalm-taint-escape header
	 * @psalm-taint-escape html
	 * @psalm-taint-escape include
	 * @psalm-taint-escape ldap
	 * @psalm-taint-escape shell
	 * @psalm-taint-escape sql
	 * @psalm-taint-escape unserialize
	 *
	 * @since 31.0.0
	 */
	public function cleanAppId(string $app): string;

	/**
	 * Get a list of all apps in the apps folder
	 *
	 * @return list<string> an array of app names (string IDs)
	 * @since 31.0.0
	 */
	public function getAllAppsInAppsFolders(): array;
}

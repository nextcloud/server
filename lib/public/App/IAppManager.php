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
 * Central interface for managing Nextcloud apps.
 *
 * Manages app discovery, enablement, loading. metadata, compatibility checks,
 * and lifecycle operations.
 *
 * @warning This interface should NOT be included via DI in classes used for
 *          installing Nextcloud.
 *
 * @since 8.0.0
 */
interface IAppManager {
	/**
	 * @since 30.0.0
	 */
	public const BACKEND_CALDAV = 'caldav';

	/**
	 * Returns parsed app metadata for the app identified by $appId.
	 *
	 * Reads the app's `appinfo/info.xml` and returns the parsed app info,
	 * optionally localized for the given language.
	 *
	 * @param string $appId App ID
	 * @param bool $path Deprecated. Must remain false; passing true throws an \InvalidArgumentException.
	 * @param string|null $lang Language code for localized metadata, or null for the default language
	 * @return AppInfoDefinition|AppInfoXmlDefinition|null
	 * @psalm-return ($lang is null ? (AppInfoXmlDefinition|null) : (AppInfoDefinition|null))
	 * @since 14.0.0
	 * @since 31.0.0 The $path parameter is no longer supported; use {@see self::getAppInfoByPath()} instead.
	 */
	public function getAppInfo(string $appId, bool $path = false, $lang = null);

	/**
	 * Returns parsed app metadata from a specific `appinfo/info.xml` file.
	 *
	 * The path must point to an app's `.../appinfo/info.xml` file.
	 *
	 * @param string $path Absolute path to `appinfo/info.xml`
	 * @param string|null $lang Language code for localized metadata, or null for the default language
	 * @return AppInfoDefinition|AppInfoXmlDefinition|null
	 * @psalm-return ($lang is null ? (AppInfoXmlDefinition|null) : (AppInfoDefinition|null))
	 * @since 31.0.0
	 */
	public function getAppInfoByPath(string $path, ?string $lang = null): ?array;

	/**
	 * Returns the version declared by the app's `appinfo/info.xml`.
	 *
	 * @param string $appId App ID
	 * @param bool $useCache Whether to reuse a cached version value
	 * @return string App version, or "0" if no version is declared
	 * @since 14.0.0
	 */
	public function getAppVersion(string $appId, bool $useCache = true): string;

	/**
	 * Returns the installed version of apps known to the app configuration.
	 *
	 * @param bool $onlyEnabled Limit the result to enabled apps only
	 * @return array<string, string> Map of app ID to installed version
	 * @since 32.0.0
	 */
	public function getAppInstalledVersions(bool $onlyEnabled = false): array;

	/**
	 * Returns a URL to the app icon, or null if none is available.
	 *
	 * Tries app-specific icons first, then falls back to the generic app icon.
	 *
	 * @param string $appId App ID
	 * @param bool $dark Whether to prefer the dark icon variant
	 * @return string|null Public URL to the icon
	 * @since 29.0.0
	 */
	public function getAppIcon(string $appId, bool $dark = false): ?string;

	/**
	 * Checks whether an app is enabled for the given user.
	 *
	 * Apps enabled globally or for one of the user's groups count as enabled.
	 *
	 * @param string $appId App ID
	 * @param IUser|null $user User to check, or null to use the current user
	 * @return bool
	 * @since 8.0.0
	 */
	public function isEnabledForUser($appId, $user = null);

	/**
	 * Checks whether an app is enabled in the instance.
	 *
	 * This is a legacy alias that returns whether the app is enabled globally
	 * or for at least one group; it does not merely check whether the app is installed.
	 *
	 * @param string $appId App ID
	 * @return bool
	 * @since 8.0.0
	 * @deprecated 32.0.0 Use either {@see self::isEnabledForUser()} or {@see self::isEnabledForAnyone()}
	 */
	public function isInstalled($appId);

	/**
	 * Checks whether an app is enabled for anyone in the instance.
	 *
	 * This returns true for apps enabled globally or restricted to specific groups.
	 *
	 * @param string $appId App ID
	 * @return bool
	 * @since 32.0.0
	 */
	public function isEnabledForAnyone(string $appId): bool;

	/**
	 * Checks whether an app is part of the default-enabled app set.
	 *
	 * This indicates whether the app should be enabled by default on a fresh install,
	 * not whether it is currently installed or enabled.
	 *
	 * @param string $appId App ID
	 * @return bool
	 * @since 25.0.0
	 */
	public function isDefaultEnabled(string $appId):bool;

	/**
	 * Loads an app's bootstrap and registers its services, if not already loaded.
	 *
	 * @param string $app App ID
	 * @since 27.0.0
	 */
	public function loadApp(string $app): void;

	/**
	 * Checks whether an app has already been loaded in the current process.
	 *
	 * @param string $app App ID
	 * @return bool
	 * @since 27.0.0
	 */
	public function isAppLoaded(string $app): bool;

	/**
	 * Enables an app globally for all users.
	 *
	 * @param string $appId App ID
	 * @param bool $forceEnable Whether to bypass Nextcloud version requirement checks
	 * @throws AppPathNotFoundException If the app cannot be found
	 * @since 8.0.0
	 */
	public function enableApp(string $appId, bool $forceEnable = false): void;

	/**
	 * Checks whether the given app types contain a protected type.
	 *
	 * Protected apps cannot be enabled for specific groups only.
	 *
	 * @param string[] $types App types to check
	 * @return bool
	 * @since 12.0.0
	 */
	public function hasProtectedAppType($types);

	/**
	 * Enables an app only for the specified groups.
	 *
	 * @param string $appId App ID
	 * @param IGroup[]|string[] $groups Group objects or group IDs
	 * @param bool $forceEnable Whether to bypass Nextcloud version requirement checks
	 * @throws \InvalidArgumentException If the app cannot be enabled for groups
	 * @throws AppPathNotFoundException If the app cannot be found
	 * @since 8.0.0
	 */
	public function enableAppForGroups(string $appId, array $groups, bool $forceEnable = false): void;

	/**
	 * Disables an app globally for all users.
	 *
	 * If $automaticDisabled is true, the previous enabled state is remembered so it can be restored.
	 *
	 * @param string $appId App ID
	 * @param bool $automaticDisabled Whether the app was disabled automatically
	 * @throws \Exception If the app cannot be disabled
	 * @since 8.0.0
	 */
	public function disableApp($appId, $automaticDisabled = false): void;

	/**
	 * Returns the filesystem path to an app directory.
	 *
	 * @param string $appId App ID
	 * @param bool $ignoreCache Whether to bypass the cached app directory lookup
	 * @return string Absolute filesystem path to the app directory
	 * @throws AppPathNotFoundException If the app cannot be found
	 * @since 11.0.0
	 * @since 32.0.0 Added $ignoreCache
	 */
	public function getAppPath(string $appId, bool $ignoreCache = false): string;

	/**
	 * Returns the web-accessible path for the given app.
	 *
	 * @param string $appId App ID
	 * @return string Web path to the app directory
	 * @throws AppPathNotFoundException If the app cannot be found
	 * @since 18.0.0
	 */
	public function getAppWebPath(string $appId): string;

	/**
	 * Returns all apps enabled for the given user.
	 *
	 * Includes apps enabled globally and apps enabled for one of the user's groups.
	 *
	 * @param IUser $user User to inspect
	 * @return list<string> Enabled app IDs
	 * @since 8.1.0
	 */
	public function getEnabledAppsForUser(IUser $user);

	/**
	 * Returns all enabled apps.
	 *
	 * This is a legacy alias for {@see self::getEnabledApps()}.
	 *
	 * @return string[]
	 * @since 8.1.0
	 * @deprecated 32.0.0 Use either {@see self::getEnabledApps()} or {@see self::getEnabledAppsForUser()}
	 */
	public function getInstalledApps();

	/**
	 * Returns all apps that are enabled for anyone.
	 *
	 * This includes apps enabled globally and apps enabled for specific groups.
	 *
	 * @return list<string> Enabled app IDs
	 * @since 32.0.0
	 */
	public function getEnabledApps(): array;

	/**
	 * Clears cached app metadata so it will be reloaded on the next access.
	 *
	 * @since 8.1.0
	 */
	public function clearAppsCache(): void;

	/**
	 * Checks whether an app is shipped with Nextcloud.
	 *
	 * @param string $appId App ID
	 * @return bool
	 * @since 9.0.0
	 */
	public function isShipped($appId);

	/**
	 * Loads all enabled apps, optionally filtered by app type.
	 *
	 * When $types is non-empty, only enabled apps matching at least one of the given
	 * types are loaded.
	 *
	 * @param string[] $types App types to filter by
	 * @return bool True if loading was attempted, false if blocked by maintenance mode
	 * @since 27.0.0
	 */
	public function loadApps(array $types = []): bool;

	/**
	 * Checks whether an app has at least one of the specified types.
	 *
	 * @param string $app App ID
	 * @param string[] $types Types to match against
	 * @return bool
	 * @since 27.0.0
	 */
	public function isType(string $app, array $types): bool;

	/**
	 * Returns apps that are always enabled and cannot be disabled.
	 *
	 * @return string[] App IDs
	 * @since 9.0.0
	 */
	public function getAlwaysEnabledApps();

	/**
	 * Returns apps that are enabled by default on a fresh installation.
	 *
	 * @return string[] App IDs
	 * @since 25.0.0
	 */
	public function getDefaultEnabledApps(): array;

	/**
	 * Returns all apps enabled for the given group.
	 *
	 * @param IGroup $group Group to inspect
	 * @return string[] Enabled app IDs
	 * @since 17.0.0
	 */
	public function getEnabledAppsForGroup(IGroup $group): array;

	/**
	 * Returns the group restriction for an app, if one is configured.
	 *
	 * @param string $appId App ID
	 * @return string[] Group IDs, or an empty array if the app is not group-restricted
	 * @since 17.0.0
	 */
	public function getAppRestriction(string $appId): array;

	/**
	 * Returns the app ID of the user's default app.
	 *
	 * If $user is null, the currently logged-in user is used.
	 *
	 * @param IUser|null $user User to query, or null for the current user
	 * @param bool $withFallbacks Whether to use fallback defaults when no explicit default is configured
	 * @return string Default app ID
	 * @since 25.0.6
	 * @since 28.0.0 Added optional $withFallbacks parameter
	 * @deprecated 31.0.0 Use {@see \OCP\INavigationManager::getDefaultEntryIdForUser()} instead
	 */
	public function getDefaultAppForUser(?IUser $user = null, bool $withFallbacks = true): string;

	/**
	 * Returns the globally configured default apps.
	 *
	 * @return string[] Default app IDs
	 * @since 28.0.0
	 * @deprecated 31.0.0 Use {@see \OCP\INavigationManager::getDefaultEntryIds()} instead
	 */
	public function getDefaultApps(): array;

	/**
	 * Sets the globally configured default apps.
	 *
	 * @param string[] $defaultApps App IDs that should become the default apps
	 * @throws \InvalidArgumentException If any requested app is not available in the navigation entries
	 * @since 28.0.0
	 * @deprecated 31.0.0 Use {@see \OCP\INavigationManager::setDefaultEntryIds()} instead
	 */
	public function setDefaultApps(array $defaultApps): void;

	/**
	 * Checks whether at least one loaded app requires the given backend.
	 *
	 * @param string $backend Backend identifier, such as self::BACKEND_CALDAV
	 * @return bool True if at least one app requires the backend
	 * @since 30.0.0
	 */
	public function isBackendRequired(string $backend): bool;

	/**
	 * Sanitizes an app ID by removing forbidden characters.
	 *
	 * The returned ID contains only lowercase alphanumeric characters, underscores,
	 * and hyphens, with invalid leading/trailing characters removed.
	 *
	 * @param string $app Raw app ID
	 * @return string Sanitized app ID
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
	 * @since 31.0.0
	 */
	public function cleanAppId(string $app): string;

	/**
	 * Returns all app IDs found in the configured apps folders.
	 *
	 * @return list<string> App IDs
	 * @since 31.0.0
	 */
	public function getAllAppsInAppsFolders(): array;

	/**
 	* Runs the upgrade steps for an app after its code has been updated.
	 *
	 * @param string $appId App ID
	 * @return bool True if the upgrade completed successfully
	 * @throws AppPathNotFoundException If the app folder cannot be found
	 * @since 32.0.0
	 */
	public function upgradeApp(string $appId): bool;

	/**
	 * Checks whether the app's installed version differs from the version in `info.xml`.
	 *
	 * @param string $appId App ID
	 * @return bool True if an upgrade is required
	 * @since 32.0.0
	 */
	public function isUpgradeRequired(string $appId): bool;

	/**
	 * Checks whether the given Nextcloud version is compatible with an app's requirements.
	 *
	 * Compatibility is determined from the app's declared minimum and maximum supported
	 * Nextcloud versions. Partial version constraints are supported, so comparing against
	 * `26` or `26.0` will match `26.0.3` when appropriate.
	 *
	 * @param string $serverVersion Nextcloud version to check
	 * @param array $appInfo Parsed app info array
	 * @param bool $ignoreMax Whether to ignore the app's max-version constraint
	 * @return bool True if the app is compatible
	 * @since 32.0.0
	 */
	public function isAppCompatible(string $serverVersion, array $appInfo, bool $ignoreMax = false): bool;
}

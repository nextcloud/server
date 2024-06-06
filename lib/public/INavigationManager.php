<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

/**
 * Manages the ownCloud navigation
 * @since 6.0.0
 *
 * @psalm-type NavigationEntry = array{id: string, order: int, href: string, name: string, app?: string, icon?: string, classes?: string, type?: string}
 */
interface INavigationManager {
	/**
	 * Navigation entries of the app navigation
	 * @since 16.0.0
	 */
	public const TYPE_APPS = 'link';

	/**
	 * Navigation entries of the settings navigation
	 * @since 16.0.0
	 */
	public const TYPE_SETTINGS = 'settings';

	/**
	 * Navigation entries for public page footer navigation
	 * @since 16.0.0
	 */
	public const TYPE_GUEST = 'guest';

	/**
	 * Creates a new navigation entry
	 *
	 * @param array array|\Closure $entry Array containing: id, name, order, icon and href key
	 * 					If a menu entry (type = 'link') is added, you shall also set app to the app that added the entry.
	 *					The use of a closure is preferred, because it will avoid
	 * 					loading the routing of your app, unless required.
	 * @psalm-param NavigationEntry|callable():NavigationEntry $entry
	 * @return void
	 * @since 6.0.0
	 */
	public function add($entry);

	/**
	 * Sets the current navigation entry of the currently running app
	 * @param string $appId id of the app entry to activate (from added $entry)
	 * @return void
	 * @since 6.0.0
	 */
	public function setActiveEntry($appId);

	/**
	 * Get the current navigation entry of the currently running app
	 * @return string
	 * @since 20.0.0
	 */
	public function getActiveEntry();

	/**
	 * Get a list of navigation entries
	 *
	 * @param string $type type of the navigation entries
	 * @return array
	 * @since 14.0.0
	 */
	public function getAll(string $type = self::TYPE_APPS): array;

	/**
	 * Set an unread counter for navigation entries
	 *
	 * @param string $id id of the navigation entry
	 * @param int $unreadCounter Number of unread entries (0 to hide the counter which is the default)
	 * @since 22.0.0
	 */
	public function setUnreadCounter(string $id, int $unreadCounter): void;
}

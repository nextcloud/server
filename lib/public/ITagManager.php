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
 * Factory class creating instances of \OCP\ITags
 *
 * A tag can be e.g. 'Family', 'Work', 'Chore', 'Special Occation' or
 * anything else that is either parsed from a vobject or that the user chooses
 * to add.
 * Tag names are not case-sensitive, but will be saved with the case they
 * are entered in. If a user already has a tag 'family' for a type, and
 * tries to add a tag named 'Family' it will be silently ignored.
 * @since 6.0.0
 */
interface ITagManager {
	/**
	 * Create a new \OCP\ITags instance and load tags from db for the current user.
	 *
	 * @see \OCP\ITags
	 * @param string $type The type identifier e.g. 'contact' or 'event'.
	 * @param array $defaultTags An array of default tags to be used if none are stored.
	 * @param boolean $includeShared Whether to include tags for items shared with this user by others. - always false since 20.0.0
	 * @param string $userId user for which to retrieve the tags, defaults to the currently
	 *                       logged in user
	 * @return \OCP\ITags
	 * @since 6.0.0 - parameter $includeShared and $userId were added in 8.0.0 - $includeShared is always false since 20.0.0
	 */
	public function load($type, $defaultTags = [], $includeShared = false, $userId = null);
}

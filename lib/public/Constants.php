<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

// This file defines common constants used in Nextcloud

namespace OCP;

/**
 * Class Constants
 *
 * @since 8.0.0
 */
class Constants {
	/**
	 * @since 8.0.0
	 */
	public const PERMISSION_READ = 1;

	/**
	 * @since 8.0.0
	 */
	public const PERMISSION_UPDATE = 2;

	/**
	 * CRUDS permissions.
	 * @since 8.0.0
	 */
	public const PERMISSION_CREATE = 4;

	/**
	 * @since 8.0.0
	 */
	public const PERMISSION_DELETE = 8;

	/**
	 * @since 8.0.0
	 */
	public const PERMISSION_SHARE = 16;

	/**
	 * @since 8.0.0
	 */
	public const PERMISSION_ALL = 31;

	/**
	 * @since 8.0.0 - Updated in 9.0.0 to allow all POSIX chars since we no
	 * longer support windows as server platform.
	 */
	public const FILENAME_INVALID_CHARS = '\\/';

	/**
	 * @since 21.0.0 – default value for autocomplete/search results limit,
	 * cf. sharing.maxAutocompleteResults in config.sample.php.
	 */
	public const SHARING_MAX_AUTOCOMPLETE_RESULTS_DEFAULT = 25;
}

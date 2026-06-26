<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Storage;

use OCP\Files\Notify\INotifyHandler;

/**
 * Storage backend that support active notifications
 *
 * @since 9.1.0
 */
interface INotifyStorage {
	/**
	 * @since 9.1.0
	 */
	public const NOTIFY_ADDED = 1;

	/**
	 * @since 9.1.0
	 */
	public const NOTIFY_REMOVED = 2;

	/**
	 * @since 9.1.0
	 */
	public const NOTIFY_MODIFIED = 3;

	/**
	 * @since 9.1.0
	 */
	public const NOTIFY_RENAMED = 4;

	/**
	 * Start the notification handler for this storage
	 *
	 * @return INotifyHandler
	 *
	 * @since 12.0.0
	 */
	public function notify(string $path);
}

<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Notify;

/**
 * Represents a detected change in the storage
 *
 * @since 12.0.0
 */
interface IChange {
	/**
	 * @since 12.0.0
	 */
	public const ADDED = 1;

	/**
	 * @since 12.0.0
	 */
	public const REMOVED = 2;

	/**
	 * @since 12.0.0
	 */
	public const MODIFIED = 3;

	/**
	 * @since 12.0.0
	 */
	public const RENAMED = 4;

	/**
	 * Get the type of the change
	 *
	 * @return int IChange::ADDED, IChange::REMOVED, IChange::MODIFIED or IChange::RENAMED
	 *
	 * @since 12.0.0
	 */
	public function getType();

	/**
	 * Get the path of the file that was changed relative to the root of the storage
	 *
	 * Note, for rename changes this path is the old path for the file
	 *
	 * @return mixed
	 *
	 * @since 12.0.0
	 */
	public function getPath();
}

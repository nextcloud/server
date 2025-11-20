<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Notify;

use OCP\Files\Notify\IChange;

class Change implements IChange {
	/**
	 * @param IChange::ADDED|IChange::REMOVED|IChange::MODIFIED|IChange::RENAMED $type
	 */
	public function __construct(
		private readonly int $type,
		private readonly string $path,
	) {
	}

	/**
	 * Get the type of the change
	 *
	 * @return IChange::ADDED|IChange::REMOVED|IChange::MODIFIED|IChange::RENAMED
	 */
	public function getType(): int {
		return $this->type;
	}

	/**
	 * Get the path of the file that was changed relative to the root of the storage
	 *
	 * Note, for rename changes this path is the old path for the file
	 */
	public function getPath(): string {
		return $this->path;
	}
}

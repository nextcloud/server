<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Notify;

use OCP\Files\Notify\IChange;
use OCP\Files\Notify\IRenameChange;

class RenameChange extends Change implements IRenameChange {
	/**
	 * @param IChange::ADDED|IChange::REMOVED|IChange::MODIFIED|IChange::RENAMED $type
	 */
	public function __construct(
		int $type,
		string $path,
		private readonly string $targetPath,
	) {
		parent::__construct($type, $path);
	}

	/**
	 * Get the new path of the renamed file relative to the storage root
	 */
	public function getTargetPath(): string {
		return $this->targetPath;
	}
}

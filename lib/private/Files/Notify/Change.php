<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Notify;

use OCP\Files\Notify\IChange;

class Change implements IChange {
	/** @var int */
	private $type;

	/** @var string */
	private $path;

	/**
	 * Change constructor.
	 *
	 * @param int $type
	 * @param string $path
	 */
	public function __construct($type, $path) {
		$this->type = $type;
		$this->path = $path;
	}

	/**
	 * Get the type of the change
	 *
	 * @return int IChange::ADDED, IChange::REMOVED, IChange::MODIFIED or IChange::RENAMED
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Get the path of the file that was changed relative to the root of the storage
	 *
	 * Note, for rename changes this path is the old path for the file
	 *
	 * @return mixed
	 */
	public function getPath() {
		return $this->path;
	}
}

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

	public function getType() {
		return $this->type;
	}

	public function getPath() {
		return $this->path;
	}
}

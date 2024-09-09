<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Notify;

use OCP\Files\Notify\IRenameChange;

class RenameChange extends Change implements IRenameChange {
	/** @var string */
	private $targetPath;

	/**
	 * Change constructor.
	 *
	 * @param int $type
	 * @param string $path
	 * @param string $targetPath
	 */
	public function __construct($type, $path, $targetPath) {
		parent::__construct($type, $path);
		$this->targetPath = $targetPath;
	}

	public function getTargetPath() {
		return $this->targetPath;
	}
}

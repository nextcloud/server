<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Log;

/**
 * Trait RotationTrait
 *
 *
 * @since 14.0.0
 */
trait RotationTrait {
	/**
	 * @var string
	 * @since 14.0.0
	 */
	protected $filePath;

	/**
	 * @var int
	 * @since 14.0.0
	 */
	protected $maxSize;

	/**
	 * @return string the resulting new filepath
	 * @since 14.0.0
	 */
	protected function rotate():string {
		$rotatedFile = $this->filePath . '.1';
		rename($this->filePath, $rotatedFile);
		return $rotatedFile;
	}

	/**
	 * @return bool
	 * @since 14.0.0
	 */
	protected function shouldRotateBySize():bool {
		if ((int)$this->maxSize > 0 && file_exists($this->filePath)) {
			$filesize = @filesize($this->filePath);
			if ($filesize >= (int)$this->maxSize) {
				return true;
			}
		}
		return false;
	}
}

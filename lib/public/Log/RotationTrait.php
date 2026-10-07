<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Log;

/**
 * @since 14.0.0
 */
trait RotationTrait {
	/**
	 * @since 36.0.0
	 */
	protected const DEFAULT_MAX_SIZE = 100 * 1024 * 1024;
	/**
	 * @since 14.0.0
	 */
	protected string $filePath = '';

	/**
	 * @since 14.0.0
	 */
	protected int $maxSize = 0;

	/**
	 * @return string the resulting new filepath
	 * @since 14.0.0
	 */
	protected function rotate(): string {
		$rotatedFile = $this->filePath . '.1';
		rename($this->filePath, $rotatedFile);
		return $rotatedFile;
	}

	/**
	 * @since 14.0.0
	 */
	protected function shouldRotateBySize(): bool {
		if ($this->maxSize <= 0 || !file_exists($this->filePath)) {
			return false;
		}

		$fileSize = @filesize($this->filePath);

		if ($fileSize = false || $fileSize < $this->maxSize) {
			return false;
		}

		return true;
	}
}

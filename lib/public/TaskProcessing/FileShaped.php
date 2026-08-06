<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TaskProcessing;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Data object for file-shaped output entries
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class FileShaped {
	/**
	 * @param EShapeType $shapeType
	 * @param string $data
	 * @param string $extension (optional)
	 *
	 * @since 35.0.0
	 */
	public function __construct(
		private EShapeType $shapeType,
		private string $data,
		private string $extension = '',
	) {
		$this->extension = self::sanitizeExtension($this->extension);
	}

	/**
	 * @return string
	 * @since 35.0.0
	 */
	public function getData(): string {
		return $this->data;
	}

	/**
	 * @since 35.0.0
	 */
	public function getShapeType(): EShapeType {
		return $this->shapeType;
	}

	/**
	 * @since 35.0.0
	 */
	public function getExtension(): string {
		return $this->extension;
	}

	/**
	 * @since 35.0.0
	 */
	public static function sanitizeExtension(string $ext): string {
		if ($ext === '') {
			return '';
		}
		$ext = str_replace(['.', '/'], '', $ext);
		$ext = preg_replace('/[^A-Za-z0-9]/', '', $ext) ?? $ext;
		$ext = strtolower($ext);
		$ext = substr($ext, 0, 16);

		if ($ext === 'php' || $ext === 'htaccess' || $ext === 'phar') {
			return '';
		}

		return $ext;
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TaskProcessing;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Data object for file-shaped output entries
 *
 * @since 50.0.0
 */
#[Consumable(since: '35.0.0')]
class FileShaped {
	/**
	 * @param EShapeType $shapeType
	 * @param string $data
	 * @param string $mimeType (optional)
	 * @param string $extension (optional)
	 *
	 * @since 30.0.0
	 */
	public function __construct(
		private EShapeType $shapeType,
		private string $data,
		private string $mimeType = 'application/octet-stream',
		private string $extension = '.bin',
	) {
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
	public function getMimeType(): string {
		return $this->mimeType;
	}

	/**
	 * @since 35.0.0
	 */
	public function getExtension(): string {
		return $this->extension;
	}
}

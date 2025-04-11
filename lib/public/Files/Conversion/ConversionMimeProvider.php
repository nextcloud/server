<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Conversion;

use JsonSerializable;

/**
 * A tuple-like object representing both an original and target
 * MIME type for a file conversion
 *
 * @since 31.0.0
 */
class ConversionMimeProvider implements JsonSerializable {
	/**
	 * @param string $from The source MIME type of a file
	 * @param string $to The target MIME type for the file
	 * @param string $extension The file extension for the target MIME type (e.g. 'png')
	 * @param string $displayName The human-readable name of the target MIME type (e.g. 'Image (.png)')
	 *
	 * @since 31.0.0
	 */
	public function __construct(
		private string $from,
		private string $to,
		private string $extension,
		private string $displayName,
	) {
	}

	public function getFrom(): string {
		return $this->from;
	}

	public function getTo(): string {
		return $this->to;
	}

	public function getExtension(): string {
		return $this->extension;
	}

	public function getDisplayName(): string {
		return $this->displayName;
	}

	/**
	 * @return array{from: string, to: string, extension: string, displayName: string}
	 *
	 * @since 31.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'from' => $this->from,
			'to' => $this->to,
			'extension' => $this->extension,
			'displayName' => $this->displayName,
		];
	}
}

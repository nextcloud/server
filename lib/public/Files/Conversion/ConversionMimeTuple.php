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
class ConversionMimeTuple implements JsonSerializable {
	/**
	 * @param string $from The original MIME type of a file
	 * @param list<array{mime: string, name: string}> $to The desired MIME type for the file mapped to its translated name
	 *
	 * @since 31.0.0
	 */
	public function __construct(
		private string $from,
		private array $to,
	) {
	}

	/**
	 * @return array{from: string, to: list<array{mime: string, name: string}>}
	 *
	 * @since 31.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'from' => $this->from,
			'to' => $this->to,
		];
	}
}

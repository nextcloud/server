<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Preview;

//.jp2
class JP2 extends Bitmap {
	#[\Override]
	public function getMimeType(): string {
		return '/image\/jp2/';
	}

	#[\Override]
	protected function getAllowedMimeTypes(): string {
		return '/image\/jp2/';
	}

	#[\Override]
	protected function getMagicStrings(): array {
		return [
			// The JP2 signature box, which opens the file: its length, the
			// box type, and the four bytes that catch a transfer having
			// mangled the line endings
			"\x00\x00\x00\x0CjP  \x0D\x0A\x87\x0A",
		];
	}

	#[\Override]
	protected function getImagickFormatHint(): string {
		return 'jp2';
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

//.tiff
class TIFF extends Bitmap {
	#[\Override]
	public function getMimeType(): string {
		return '/image\/tiff/';
	}

	#[\Override]
	protected function getAllowedMimeTypes(): string {
		return '/image\/tiff/';
	}

	#[\Override]
	protected function getMagicStrings(): array {
		return [
			"II*\x00",
			"MM\x00*",
			"II+\x00",
			"MM\x00+",
		];
	}

	#[\Override]
	protected function getImagickFormatHint(): string {
		return 'tiff';
	}
}

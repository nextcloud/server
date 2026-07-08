<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

//.pdf
class PDF extends Bitmap {
	#[\Override]
	public function getMimeType(): string {
		return '/application\/pdf/';
	}

	#[\Override]
	protected function getAllowedMimeTypes(): string {
		return '/application\/pdf/';
	}

	#[\Override]
	protected function getMagicStrings(): array {
		return ['%PDF-'];
	}

	#[\Override]
	protected function getImagickFormatHint(): string {
		return 'pdf';
	}
}

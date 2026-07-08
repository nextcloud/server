<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

//.psd
class Photoshop extends Bitmap {
	#[\Override]
	public function getMimeType(): string {
		return '/application\/x-photoshop/';
	}

	#[\Override]
	protected function getAllowedMimeTypes(): string {
		return '/(application|image)\/(x-photoshop|x-psd)/';
	}

	#[\Override]
	protected function getMagicStrings(): array {
		return ['8BPS'];
	}

	#[\Override]
	protected function getImagickFormatHint(): string {
		return 'psd';
	}
}

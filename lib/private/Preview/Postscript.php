<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

//.eps
class Postscript extends Bitmap {
	#[\Override]
	public function getMimeType(): string {
		return '/application\/postscript/';
	}

	#[\Override]
	protected function getAllowedMimeTypes(): string {
		return '/(application\/postscript|image\/x-eps)/';
	}

	#[\Override]
	protected function getMagicStrings(): array {
		return ['%!PS'];
	}

	#[\Override]
	protected function getImagickFormatHint(): string {
		return 'ps';
	}
}

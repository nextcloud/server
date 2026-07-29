<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

//.sgi
class SGI extends Bitmap {
	#[\Override]
	public function getMimeType(): string {
		return '/image\/(x-)?sgi/';
	}

	#[\Override]
	protected function getAllowedMimeTypes(): string {
		return '/image\/(x-)?sgi/';
	}

	#[\Override]
	protected function getMagicStrings(): array {
		return ["\x01\xDA"];
	}

	#[\Override]
	protected function getImagickFormatHint(): string {
		return 'sgi';
	}
}

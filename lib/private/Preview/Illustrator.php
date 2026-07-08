<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

//.ai
class Illustrator extends Bitmap {
	#[\Override]
	public function getMimeType(): string {
		return '/application\/illustrator/';
	}

	#[\Override]
	protected function getAllowedMimeTypes(): string {
		return '/application\/(illustrator|pdf)/';
	}

	#[\Override]
	protected function getMagicStrings(): array {
		return ["\x25\x50\x44\x46"];
	}

	#[\Override]
	protected function getImagickFormatHint(): string {
		return 'ai';
	}
}

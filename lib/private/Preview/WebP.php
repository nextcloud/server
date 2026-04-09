<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OCP\Files\FileInfo;

class WebP extends Image {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/image\/webp/';
	}

	public function isAvailable(FileInfo $file): bool {
		return (bool)(imagetypes() & IMG_WEBP);
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

//.sgi
class SGI extends Bitmap {
	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function getMimeType(): string {
		return '/image\/(x-)?sgi/';
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	protected function getAllowedMimeTypes(): string {
		return '/image\/(x-)?sgi/';
	}
}

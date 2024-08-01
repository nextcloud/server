<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

// .otf, .ttf and .pfb
class Font extends Bitmap {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/application\/(?:font-sfnt|x-font$)/';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getAllowedMimeTypes(): string {
		return '/(application|image)\/(?:font-sfnt|x-font|x-otf|x-ttf|x-pfb$)/';
	}
}

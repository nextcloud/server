<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

//.psd
class Photoshop extends Bitmap {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/application\/x-photoshop/';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getAllowedMimeTypes(): string {
		return '/(application|image)\/(x-photoshop|x-psd)/';
	}
}

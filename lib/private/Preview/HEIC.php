<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2018 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Preview;

/**
 * Creates a JPG preview of a HEIF file holding h265, using ImageMagick via
 * the PECL extension.
 *
 * @package OC\Preview
 */
class HEIC extends Heif {
	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function getMimeType(): string {
		return '/image\/(x-)?hei(f|c)/';
	}

	#[\Override]
	protected function formatHint(): string {
		return 'heic';
	}

	#[\Override]
	protected function queryFormat(): string {
		return 'HEIC';
	}
}

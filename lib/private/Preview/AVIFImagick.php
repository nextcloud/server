<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Preview;

/**
 * Creates a JPG preview of a HEIF file holding AV1, using ImageMagick via
 * the PECL extension.
 *
 * The AVIF provider beside this one reads the format through libgd, which
 * is where most installations will get it and which needs nothing enabled.
 * This one is for a libgd built without libavif, on a server whose
 * ImageMagick does carry the codec.
 *
 * @package OC\Preview
 */
class AVIFImagick extends Heif {
	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function getMimeType(): string {
		return '/image\/avif/';
	}

	#[\Override]
	protected function formatHint(): string {
		return 'avif';
	}

	#[\Override]
	protected function queryFormat(): string {
		return 'AVIF';
	}
}

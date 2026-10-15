<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Preview;

class AVIF extends Image {
	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function getMimeType(): string {
		return '/image\/avif/';
	}
}

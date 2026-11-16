<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

/**
 * Content-Disposition values for direct downloads from object storage.
 */
final class ContentDisposition {
	/**
	 * Content-Disposition for a direct download. The name is percent-encoded so it cannot break the header.
	 */
	public static function attachment(?string $filename): string {
		$filename = str_replace(["\r", "\n"], '', (string)$filename);
		if ($filename === '' || $filename === '.' || $filename === '..') {
			return 'attachment';
		}

		$encoded = rawurlencode($filename);
		return 'attachment; filename="' . $encoded . '"; filename*=UTF-8\'\'' . $encoded;
	}
}

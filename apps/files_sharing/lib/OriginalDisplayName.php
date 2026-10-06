<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing;

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\NotFoundException;
use OCP\Share\IShare;

/**
 * Source name of a received share when the recipient sees a different name.
 *
 * The names differ after a manual rename or an automatic conflict suffix.
 * Children of a shared folder keep the source name, so this is only set for the share root.
 */
class OriginalDisplayName {
	public static function forShare(IShare $share, string $localName, bool $isShareRoot): ?string {
		if (!$isShareRoot) {
			return null;
		}

		$sourceName = self::sourceName($share);
		if ($sourceName === null || $sourceName === $localName) {
			return null;
		}

		return $sourceName;
	}

	private static function sourceName(IShare $share): ?string {
		$cacheEntry = $share->getNodeCacheEntry();
		if ($cacheEntry instanceof ICacheEntry) {
			$name = $cacheEntry->getName();
			if (is_string($name) && $name !== '') {
				return $name;
			}
		}

		try {
			$name = $share->getNode()->getName();
		} catch (NotFoundException) {
			return null;
		}

		return $name === '' ? null : $name;
	}
}

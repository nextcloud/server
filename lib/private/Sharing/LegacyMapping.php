<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Sharing;

use DateTimeImmutable;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;

final readonly class LegacyMapping {
	public function __construct(
		public string $id,
		public string $legacyProvider,
		public int $legacyId,
		public DateTimeImmutable $lastUpdated,
		public string $secret,
	) {
	}

	/**
	 * @throws ShareNotFound
	 */
	public function getLegacyShare(IManager $legacySharingManager): IShare {
		// Not cached on purpose, so we always get the current state of the share.
		return $legacySharingManager->getShareById(
			$this->legacyProvider . ':' . $this->legacyId,
			onlyValid: false,
		);
	}
}

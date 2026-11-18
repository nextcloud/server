<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Sharing;

use DateTimeImmutable;
use OCP\Server;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShare;
use OCP\Share\IShareProvider;

final readonly class LegacyMapping {
	public function __construct(
		/** @var non-empty-string $id */
		public string $id,
		/** @var class-string<IShareProvider> $legacyProviderClass */
		public string $legacyProviderClass,
		public int $legacyId,
		public DateTimeImmutable $lastUpdated,
		/**
		 * A single legacy share can correspond to multiple share recipients, so we have to save the secret for all of them.
		 * @var non-empty-list<non-empty-string> $recipientSecrets
		 */
		public array $recipientSecrets,
		public string $recipientValue,
	) {
	}

	/**
	 * @throws ShareNotFound
	 */
	public function getLegacyShare(): IShare {
		// Not cached on purpose, so we always get the current state of the share.
		return Server::get($this->legacyProviderClass)->getShareById((string)$this->legacyId);
	}
}

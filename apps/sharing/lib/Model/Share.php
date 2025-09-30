<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Model;

use OCA\Sharing\ResponseDefinitions;

/**
 * @psalm-import-type SharingShare from ResponseDefinitions
 */
class Share {
	public function __construct(
		/** @var non-empty-string $id */
		public readonly string $id,
		/** @var non-empty-string $creator */
		public readonly string $creator,
		/** @var class-string<AShareSourceType> $sourceType */
		public readonly string $sourceType,
		/** @var list<string> $sources */
		public readonly array $sources,
		/** @var class-string<AShareRecipientType> $recipientType */
		public readonly string $recipientType,
		/** @var list<string> $recipients */
		public readonly array $recipients,
		/** @var array<class-string<AShareFeature>, array<string, list<string>>> */
		public readonly array $properties,
	) {
	}

	/**
	 * @param SharingShare $share
	 */
	public static function fromArray(array $share): self {
		return new self(
			$share['id'],
			$share['creator'],
			$share['source_type'],
			$share['sources'],
			$share['recipient_type'],
			$share['recipients'],
			$share['properties'],
		);
	}

	/**
	 * @return SharingShare
	 */
	public function toArray(): array {
		return [
			'id' => $this->id,
			'creator' => $this->creator,
			'source_type' => $this->sourceType,
			'sources' => $this->sources,
			'recipient_type' => $this->recipientType,
			'recipients' => $this->recipients,
			'properties' => $this->properties,
		];
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Tests;

use OCP\IUser;
use OCP\Sharing\Source\IShareSourceType;

class TestShareSourceType implements IShareSourceType {
	public function __construct(
		/** @var array<string, non-empty-string> $validSources */
		private readonly array $validSources,
	) {
	}

	#[\Override]
	public function getDisplayName(): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', static::class);
		return end($parts);
	}

	#[\Override]
	public function validateSource(IUser $owner, string $source): bool {
		return array_key_exists($source, $this->validSources);
	}

	#[\Override]
	public function getSourceDisplayName(string $source): ?string {
		return $this->validSources[$source];
	}
}

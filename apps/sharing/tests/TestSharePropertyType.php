<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Tests;

use OCP\Sharing\Property\AEnumSharePropertyType;

readonly class TestSharePropertyType extends AEnumSharePropertyType {
	public function __construct(
		/** @var non-empty-list<string> $validValues */
		private array $validValues,
	) {
	}

	#[\Override]
	public function getDisplayName(): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', static::class);
		return end($parts);
	}

	#[\Override]
	public function getHint(): ?string {
		return null;
	}

	#[\Override]
	public function getPriority(): int {
		return 1;
	}

	#[\Override]
	public function getRequired(): bool {
		return false;
	}

	#[\Override]
	public function getDefaultValue(): ?string {
		return null;
	}

	/**
	 * @return non-empty-list<string>
	 */
	#[\Override]
	public function getValidValues(): array {
		return $this->validValues;
	}
}

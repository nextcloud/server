<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use OCP\L10N\IFactory;
use OCP\Sharing\Property\AEnumSharePropertyType;

class TestSharePropertyType1 extends AEnumSharePropertyType {
	public function __construct(
		/** @var non-empty-list<string> $validValues */
		private readonly array $validValues,
	) {
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', static::class);
		return end($parts);
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory): string {
		return 'hint ' . $this->getDisplayName($l10nFactory);
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

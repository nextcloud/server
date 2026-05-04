<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Tests;

use OCP\Sharing\Property\AStringSharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;

final readonly class TestSharePropertyTypeFilter extends AStringSharePropertyType implements ISharePropertyTypeFilter {
	#[\Override]
	public function getDisplayName(): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', self::class);
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

	#[\Override]
	public function getMinLength(): ?int {
		return null;
	}

	#[\Override]
	public function getMaxLength(): ?int {
		return null;
	}

	#[\Override]
	public function isFiltered(ShareAccessContext $accessContext, Share $share): bool {
		if (($accessContext->arguments[self::class] ?? null) === 'filtered') {
			return true;
		}

		foreach ($share->properties as $property) {
			if ($property->class === self::class && $property->value === 'filtered') {
				return true;
			}
		}

		return false;
	}
}

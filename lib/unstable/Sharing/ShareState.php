<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
enum ShareState: string {
	/**
	 * @experimental 35.0.0
	 */
	case Active = 'active';
	/**
	 * @experimental 35.0.0
	 */
	case Draft = 'draft';
	/**
	 * @experimental 35.0.0
	 */
	case Deleted = 'deleted';

	/**
	 * @experimental 35.0.0
	 */
	public function encode(): int {
		return match ($this) {
			self::Active => 1,
			self::Draft => 2,
			self::Deleted => 3,
		};
	}

	/**
	 * @experimental 35.0.0
	 */
	public static function decode(int $value): self {
		return match ($value) {
			1 => self::Active,
			2 => self::Draft,
			3 => self::Deleted,
			default => throw new \ValueError('Invalid share state ' . $value)
		};
	}
}

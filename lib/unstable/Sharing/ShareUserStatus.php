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
enum ShareUserStatus: string {
	/**
	 * @experimental 35.0.0
	 */
	case Pending = 'pending';
	/**
	 * @experimental 35.0.0
	 */
	case Accepted = 'accepted';
	/**
	 * @experimental 35.0.0
	 */
	case Rejected = 'rejected';

	/**
	 * @experimental 35.0.0
	 */
	public function encode(): int {
		return match ($this) {
			self::Pending => 1,
			self::Accepted => 2,
			self::Rejected => 3,
		};
	}

	/**
	 * @experimental 35.0.0
	 */
	public static function decode(int $value): self {
		return match ($value) {
			1 => self::Pending,
			2 => self::Accepted,
			3 => self::Rejected,
			default => throw new \ValueError('Invalid user share status ' . $value)
		};
	}
}

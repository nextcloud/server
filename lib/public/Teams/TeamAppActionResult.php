<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * Optional replacement navigation entry IDs and the entry to open after an action.
 * Null fields leave the current navigation and open entry unchanged.
 *
 * @since 36.0.0
 */
final class TeamAppActionResult implements \JsonSerializable {
	/**
	 * @param list<string>|null $navigationEntryIds
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly ?array $navigationEntryIds = null,
		public readonly ?string $open = null,
	) {
	}

	/**
	 * @return array{navigationEntryIds?: list<string>, open?: string}
	 * @since 36.0.0
	 */
	public function jsonSerialize(): array {
		return array_filter(get_object_vars($this), static fn ($value): bool => $value !== null);
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * A resource selection dialog displayed before executing an app action.
 *
 * @since 36.0.0
 */
final class TeamAppResourcePicker implements \JsonSerializable {
	/**
	 * @param list<TeamAppResource> $resources
	 * @param list<string>|null $selectedIds
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string $title,
		public readonly array $resources,
		public readonly ?array $selectedIds = null,
	) {
	}

	/**
	 * @return array{title: string, resources: list<TeamAppResource>, selectedIds?: list<string>}
	 * @since 36.0.0
	 */
	public function jsonSerialize(): array {
		return array_filter(get_object_vars($this), static fn ($value): bool => $value !== null);
	}
}

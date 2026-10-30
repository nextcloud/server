<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * Navigation entries, actions and represented shared resources for a team.
 *
 * @since 36.0.0
 */
final class TeamAppNavigation implements \JsonSerializable {
	/**
	 * @param list<TeamAppEntry> $entries
	 * @param list<TeamAppAction>|null $actions
	 * @param list<TeamAppResourceKey>|null $resourceKeys
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly array $entries,
		public readonly ?array $actions = null,
		public readonly ?array $resourceKeys = null,
	) {
	}

	/**
	 * @return array{entries: list<TeamAppEntry>, actions?: list<TeamAppAction>, resourceKeys?: list<TeamAppResourceKey>}
	 * @since 36.0.0
	 */
	public function jsonSerialize(): array {
		return array_filter(get_object_vars($this), static fn ($value): bool => $value !== null);
	}
}

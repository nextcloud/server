<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * A navigation entry linking to a resource or an embedded app view.
 *
 * @since 36.0.0
 */
final class TeamAppEntry implements \JsonSerializable {
	/**
	 * @param list<string>|null $legacyIds
	 * @param list<TeamAppAction>|null $actions
	 * @param array<string, mixed>|null $viewData
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $label,
		public readonly ?string $icon = null,
		public readonly ?string $iconSvg = null,
		public readonly ?string $iconEmoji = null,
		public readonly ?string $href = null,
		public readonly ?array $legacyIds = null,
		public readonly ?array $actions = null,
		public readonly ?array $viewData = null,
	) {
	}

	/**
	 * @return array<string, mixed>
	 * @since 36.0.0
	 */
	public function jsonSerialize(): array {
		return array_filter(get_object_vars($this), static fn ($value): bool => $value !== null);
	}
}

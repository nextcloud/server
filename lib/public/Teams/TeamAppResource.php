<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * An app resource available for selection in a resource picker.
 *
 * @since 36.0.0
 */
final class TeamAppResource implements \JsonSerializable {
	/** @since 36.0.0 */
	public function __construct(
		public readonly string $id,
		public readonly string $label,
		public readonly ?string $description = null,
		public readonly ?string $icon = null,
		public readonly ?string $iconSvg = null,
		public readonly ?string $iconEmoji = null,
	) {
	}

	/**
	 * @return array<string, string>
	 * @since 36.0.0
	 */
	public function jsonSerialize(): array {
		return array_filter(get_object_vars($this), static fn ($value): bool => $value !== null);
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * An available app command with optional input or confirmation dialogs.
 *
 * @since 36.0.0
 */
final class TeamAppAction implements \JsonSerializable {
	/** @since 36.0.0 */
	public function __construct(
		public readonly string $id,
		public readonly string $label,
		public readonly ?string $icon = null,
		public readonly ?TeamAppActionPrompt $prompt = null,
		public readonly ?TeamAppActionConfirmation $confirm = null,
		public readonly ?TeamAppResourcePicker $resourcePicker = null,
	) {
	}

	/**
	 * @return array<string, string|TeamAppActionPrompt|TeamAppActionConfirmation|TeamAppResourcePicker>
	 * @since 36.0.0
	 */
	public function jsonSerialize(): array {
		return array_filter(get_object_vars($this), static fn ($value): bool => $value !== null);
	}
}

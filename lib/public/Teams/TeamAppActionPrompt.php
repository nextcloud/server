<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * A text prompt displayed before executing an app action.
 *
 * @since 36.0.0
 */
final class TeamAppActionPrompt implements \JsonSerializable {
	/** @since 36.0.0 */
	public function __construct(
		public readonly string $title,
		public readonly ?string $value = null,
	) {
	}

	/**
	 * @return array{title: string, value?: string}
	 * @since 36.0.0
	 */
	public function jsonSerialize(): array {
		return array_filter(['title' => $this->title, 'value' => $this->value], static fn ($value): bool => $value !== null);
	}
}

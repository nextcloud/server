<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * A confirmation dialog displayed before executing an app action.
 *
 * @since 36.0.0
 */
final class TeamAppActionConfirmation implements \JsonSerializable {
	/** @since 36.0.0 */
	public function __construct(
		public readonly string $title,
		public readonly string $message,
	) {
	}

	/**
	 * @return array{title: string, message: string}
	 * @since 36.0.0
	 */
	public function jsonSerialize(): array {
		return ['title' => $this->title, 'message' => $this->message];
	}
}

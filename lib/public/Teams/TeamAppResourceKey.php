<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * Identifies a shared resource already represented by an app entry.
 *
 * @since 36.0.0
 */
final class TeamAppResourceKey implements \JsonSerializable {
	/** @since 36.0.0 */
	public function __construct(
		public readonly string $providerId,
		public readonly string $resourceId,
	) {
	}

	/**
	 * @return array{providerId: string, resourceId: string}
	 * @since 36.0.0
	 */
	public function jsonSerialize(): array {
		return ['providerId' => $this->providerId, 'resourceId' => $this->resourceId];
	}
}

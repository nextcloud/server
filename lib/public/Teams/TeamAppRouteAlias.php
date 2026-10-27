<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * An alternate route and its parameter identifying a Teams app entry.
 *
 * @since 36.0.0
 */
final class TeamAppRouteAlias implements \JsonSerializable {
	/** @since 36.0.0 */
	public function __construct(
		public readonly string $path,
		public readonly string $entryParam,
	) {
	}

	/**
	 * @return array{path: string, entryParam: string}
	 * @since 36.0.0
	 */
	public function jsonSerialize(): array {
		return ['path' => $this->path, 'entryParam' => $this->entryParam];
	}
}

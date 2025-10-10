<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Uuid;

use OCP\AppFramework\Attribute\Consumable;

#[Consumable(since: '33.0.0')]
interface IUuidBuilder {
	/**
	 * Generates time-ordered UUIDs based on a high-resolution Unix Epoch timestamp
	 * source (the number of milliseconds since midnight 1 Jan 1970 UTC, leap seconds
	 * excluded). It's recommended to use this version over UUIDv1 and UUIDv6
	 * because it provides better entropy (and a more strict chronological order
	 * of UUID generation):
	 *
	 * ```php
	 * $uuid = IUuidBuilder::v7();
	 * ```
	 *
	 * @see https://datatracker.ietf.org/doc/html/draft-ietf-uuidrev-rfc4122bis#name-uuid-version-7
	 */
	public function v7(): IUuid;

	/**
	 * Construct a UUID object from a string.
	 */
	public function fromString(string $uuid): IUuid;
}

<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

use OCP\AppFramework\Attribute\Consumable;

/**
 * A set of Activity objects belonging to Team resources.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class TeamActivityScope {
	/** @var list<positive-int> */
	private array $objectIds;

	/**
	 * @param list<positive-int> $objectIds
	 * @param non-empty-string $objectType
	 * @since 36.0.0
	 */
	public function __construct(
		private string $objectType,
		array $objectIds,
	) {
		$this->objectIds = array_values(array_unique($objectIds));
	}

	/**
	 * @return non-empty-string
	 * @since 36.0.0
	 */
	public function getObjectType(): string {
		return $this->objectType;
	}

	/**
	 * @return list<positive-int>
	 * @since 36.0.0
	 */
	public function getObjectIds(): array {
		return $this->objectIds;
	}
}

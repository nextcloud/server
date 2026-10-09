<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * A set of Activity objects belonging to Team resources.
 *
 * @since 36.0.0
 */
class TeamActivityScope {
	/**
	 * @param list<int> $objectIds
	 * @since 36.0.0
	 */
	public function __construct(
		private string $objectType,
		private array $objectIds,
	) {
		if ($this->objectType === '') {
			throw new \InvalidArgumentException('Activity object type must not be empty');
		}
		foreach ($this->objectIds as $objectId) {
			if (!is_int($objectId) || $objectId <= 0) {
				throw new \InvalidArgumentException('Activity object IDs must be positive');
			}
		}
		$this->objectIds = array_values(array_unique($this->objectIds));
	}

	/**
	 * @since 36.0.0
	 */
	public function getObjectType(): string {
		return $this->objectType;
	}

	/**
	 * @return list<int>
	 * @since 36.0.0
	 */
	public function getObjectIds(): array {
		return $this->objectIds;
	}
}

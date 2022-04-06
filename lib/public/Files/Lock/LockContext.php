<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Files\Lock;

use OCP\Files\Node;

/**
 * Structure to identify a specific lock context to request or
 * describe a lock with the affected node and ownership information
 *
 * This is used to match a lock/unlock request or file operation to existing locks
 *
 * @since 24.0.0
 */
final class LockContext {
	private Node $node;
	private int $type;
	private string $owner;

	/**
	 * @param Node $node Node that is owned by the lock
	 * @param int $type Type of the lock owner
	 * @param string $owner Unique identifier for the lock owner based on the type
	 * @since 24.0.0
	 */
	public function __construct(
		Node $node,
		int $type,
		string $owner
	) {
		$this->node = $node;
		$this->type = $type;
		$this->owner = $owner;
	}

	/**
	 * @since 24.0.0
	 */
	public function getNode(): Node {
		return $this->node;
	}

	/**
	 * @return int
	 * @since 24.0.0
	 */
	public function getType(): int {
		return $this->type;
	}

	/**
	 * @return string user id / app id / lock token depending on the type
	 * @since 24.0.0
	 */
	public function getOwner(): string {
		return $this->owner;
	}

	/**
	 * @since 24.0.0
	 */
	public function __toString(): string {
		$typeString = 'unknown';
		if ($this->type === ILock::TYPE_USER) {
			$typeString = 'ILock::TYPE_USER';
		}
		if ($this->type === ILock::TYPE_APP) {
			$typeString = 'ILock::TYPE_APP';
		}
		if ($this->type === ILock::TYPE_TOKEN) {
			$typeString = 'ILock::TYPE_TOKEN';
		}
		return "$typeString  $this->owner " . $this->getNode()->getId();
	}
}

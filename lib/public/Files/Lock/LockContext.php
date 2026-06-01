<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Lock;

use OCP\Files\Node;

/**
 * Value object describing the context in which a lock is requested or evaluated.
 *
 * A lock context identifies the affected node together with the lock owner type
 * and owner identifier, so lock-aware operations can be matched against existing locks.
 *
 * @since 24.0.0
 */
final class LockContext {
	/**
	 * @param Node $node Node the lock context applies to
	 * @param ILock::TYPE_* $type Lock owner type
	 * @param string $owner Owner identifier for the given type - e.g. a user id, app id, or lock token
	 * @since 24.0.0
	 */
	public function __construct(
		private readonly Node $node,
		private readonly int $type,
		private readonly string $owner,
	) {
	}

	/**
	 * @since 24.0.0
	 */
	public function getNode(): Node {
		return $this->node;
	}

	/**
	 * Returns the lock owner type.
	 *
	 * @return ILock::TYPE_*
	 * @since 24.0.0
	 */
	public function getType(): int {
		return $this->type;
	}

	/**
	 * Returns the owner identifier for the current lock type.
	 *
	 * For example, this may be a user id, app id, or lock token.
	 *
	 * @since 24.0.0
	 */
	public function getOwner(): string {
		return $this->owner;
	}

	/**
	 * Returns a human-readable representation for logging and debugging.
	 *
	 * Not guaranteed to be a stable machine-readable contract.
	 *
	 * @since 24.0.0
	 */
	public function __toString(): string {
		$typeString = match ($this->type) {
			ILock::TYPE_USER => 'ILock::TYPE_USER',
			ILock::TYPE_APP => 'ILock::TYPE_APP',
			ILock::TYPE_TOKEN => 'ILock::TYPE_TOKEN',
			default => 'unknown',
		};
		return sprintf('%s %s %d', $typeString, $this->owner, $this->node->getId());
	}
}

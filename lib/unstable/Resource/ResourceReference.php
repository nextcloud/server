<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource;

use OCP\Actor\Principal;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Identifies one resource of a registered {@see IResourceType}.
 *
 * Only the app owning the resource type knows what the ID refers to.
 *
 * @psalm-immutable
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class ResourceReference {
	/**
	 * @param non-empty-string $type ID of the resource type, see {@see IResourceType::getId}
	 * @param non-empty-string $id ID of the resource, unique within the resource type
	 * @param ?Principal $owner Owner of the resource, null if the resource has no single owner
	 * @experimental 36.0.0
	 */
	public function __construct(
		public string $type,
		public string $id,
		public ?Principal $owner = null,
	) {
	}

	/**
	 * Unique key of the resource, the owner is not part of it.
	 *
	 * @return non-empty-string
	 * @experimental 36.0.0
	 */
	public function key(): string {
		return $this->type . '/' . $this->id;
	}
}

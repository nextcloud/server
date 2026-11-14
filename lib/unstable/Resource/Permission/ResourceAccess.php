<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource\Permission;

use NCU\Resource\ResourceReference;
use OCP\AppFramework\Attribute\Consumable;

/**
 * A resource together with the ways the actor has access to it.
 *
 * @psalm-immutable
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class ResourceAccess {
	/**
	 * @param list<AccessEntry> $entries Without entries restrictions apply to the resource as a whole
	 * @experimental 36.0.0
	 */
	public function __construct(
		public ResourceReference $resource,
		public array $entries = [],
	) {
	}
}

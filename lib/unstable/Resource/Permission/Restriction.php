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
 * Permissions an {@see IRestrictionProvider} restricts on a resource.
 *
 * Restricting a core permission also restricts every permission that is a kind of it.
 *
 * @psalm-immutable
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class Restriction {
	/**
	 * @param list<non-empty-string> $permissionIds
	 * @param ?non-empty-string $recipientKind
	 * @param ?non-empty-string $recipientId
	 */
	private function __construct(
		public ResourceReference $resource,
		public array $permissionIds,
		public ?string $recipientKind,
		public ?string $recipientId,
	) {
	}

	/**
	 * Restrict the permissions on the resource, however the actor has access to it.
	 *
	 * @param list<non-empty-string> $permissionIds
	 * @experimental 36.0.0
	 */
	public static function forResource(ResourceReference $resource, array $permissionIds): self {
		return new self($resource, $permissionIds, null, null);
	}

	/**
	 * Restrict the permissions only for the access the actor has through the recipient,
	 * see {@see AccessEntry}.
	 *
	 * @param non-empty-string $recipientKind
	 * @param non-empty-string $recipientId
	 * @param list<non-empty-string> $permissionIds
	 * @experimental 36.0.0
	 */
	public static function forRecipient(ResourceReference $resource, string $recipientKind, string $recipientId, array $permissionIds): self {
		return new self($resource, $permissionIds, $recipientKind, $recipientId);
	}
}

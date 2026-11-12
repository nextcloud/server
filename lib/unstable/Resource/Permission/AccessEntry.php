<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * One way an actor has access to a resource, as stored by the app owning the resource:
 * who the access was given to and which permissions it grants.
 *
 * The app passes the entries that apply to the actor without resolving memberships or roles,
 * e.g. a share with a team is passed as recipient kind `team` and the ID of the team.
 *
 * @psalm-immutable
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class AccessEntry {
	/**
	 * @experimental 36.0.0
	 */
	public const string RECIPIENT_USER = 'user';
	/**
	 * @experimental 36.0.0
	 */
	public const string RECIPIENT_GROUP = 'group';
	/**
	 * @experimental 36.0.0
	 */
	public const string RECIPIENT_TEAM = 'team';

	/**
	 * @param non-empty-string $recipientKind One of the RECIPIENT_* constants or an app specific kind in the format `appid:kind`
	 * @param non-empty-string $recipientId ID of the recipient, unique within the kind
	 * @param list<non-empty-string> $permissionIds IDs of the permissions granted to the recipient
	 * @experimental 36.0.0
	 */
	public function __construct(
		public string $recipientKind,
		public string $recipientId,
		public array $permissionIds,
	) {
	}
}

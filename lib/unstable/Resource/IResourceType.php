<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource;

use NCU\Resource\Permission\AccessEntry;
use OCP\Actor\Actor;
use OCP\AppFramework\Attribute\Implementable;

/**
 * A kind of resource an app owns, e.g. a table or a mail.
 *
 * Register implementations with {@see \OCP\AppFramework\Bootstrap\IRegistrationContext::registerResourceType}.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IResourceType {
	/**
	 * Stable ID in the format `appid:type`, the app ID has to be the ID of the registering app.
	 * The ID is persisted by other apps and must never change.
	 *
	 * @return non-empty-string
	 * @experimental 36.0.0
	 */
	public function getId(): string;

	/**
	 * Translated name of the resource type.
	 *
	 * @experimental 36.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * IDs of the permissions that apply to resources of this type.
	 *
	 * @return list<non-empty-string>
	 * @experimental 36.0.0
	 */
	public function getPermissionIds(): array;

	/**
	 * Get the ways the actor has access to the resources, e.g. as the owner or through shares.
	 *
	 * Every way is returned as stored without resolving memberships or roles,
	 * e.g. a share with a team is returned as recipient kind `team` and the ID of the team.
	 * The owner is returned as an entry with all permissions of the resource.
	 * If this method throws, the actor is treated as having no access to any of the resources.
	 *
	 * Results are remembered, call {@see \NCU\Resource\Permission\IPermissionManager::clearCache}
	 * whenever the access to a resource changes.
	 *
	 * @param list<ResourceReference> $resources Resources of this type
	 * @return array<non-empty-string, list<AccessEntry>> keyed by {@see ResourceReference::key},
	 *                                                    resources the actor has no access to are left out
	 * @experimental 36.0.0
	 */
	public function getAccess(Actor $actor, array $resources): array;
}

<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource\Permission;

use OCP\Actor\Actor;
use OCP\AppFramework\Attribute\Implementable;

/**
 * Restricts permissions on resources of other apps, e.g. based on a label or a role.
 *
 * Register implementations with
 * {@see \OCP\AppFramework\Bootstrap\IRegistrationContext::registerResourceRestrictionProvider}.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IRestrictionProvider {
	/**
	 * Get the restrictions for the actor on the resources.
	 *
	 * The resources can be of any type, nothing has to be returned for unknown ones.
	 * If this method throws, every permission on all the resources is restricted.
	 *
	 * Results are remembered, call {@see IPermissionManager::clearCache} whenever the restrictions change.
	 *
	 * @param list<ResourceAccess> $accesses The access entries are always set
	 * @return iterable<Restriction>
	 * @experimental 36.0.0
	 */
	public function getRestrictions(Actor $actor, array $accesses): iterable;
}

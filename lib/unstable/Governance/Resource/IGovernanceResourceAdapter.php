<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\Resource;

use NCU\Governance\Actor\Actor;
use NCU\Governance\LabelType;
use NCU\Governance\Resource\Exception\ResourceNotFoundException;
use OCP\AppFramework\Attribute\Implementable;

/**
 * Everything Governance needs to know about one app's resource type, supplied
 * by the app that owns it and registered with
 * {@see IGovernanceRegistry::registerResource()}. Governance can then
 * resolve labels for that type without assuming anything about what its
 * resources actually are.
 *
 * Not only access checking: whether a resource can carry a given kind of label
 * is a question about the resource itself, not about who is asking.
 *
 * Methods take the bare id rather than an {@see IGovernanceResource}: an instance of
 * this interface is registered *for one resource class*, so the id is already
 * unambiguous, and Governance is spared having to rebuild a resource object
 * from an id it received over HTTP.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IGovernanceResourceAdapter {
	/**
	 * Must throw if an actor has no read access to the resource
	 *
	 * @throws ResourceNotFoundException when the resource doesn't exist, or exists but
	 *                                   $actor cannot see it — both cases collapse together,
	 *                                   to avoid leaking existence to an actor without access
	 * @experimental 36.0.0
	 */
	// todo: maybe split this into exists and isReadable instead?
	public function assertReadable(string $id, Actor $actor): void;

	/**
	 * Whether the given actor is allowed to update the resource. Governance
	 * uses this to determine whether the actor is allowed to change a label or
	 * not.
	 *
	 * @experimental 36.0.0
	 */
	public function isUpdatable(string $id, Actor $actor): bool;

	/**
	 * Whether this specific resource can carry this kind of label at all — e.g.
	 * for Files, a Sensitivity label applies to a file but not to a folder.
	 * Per resource, not per resource type: two resources of the same class may
	 * answer differently.
	 *
	 * @experimental 36.0.0
	 */
	public function supportsLabelType(string $id, LabelType $labelType): bool;
}

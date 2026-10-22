<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\Resource;

use NCU\Governance\LabelType;
use NCU\Governance\Resource\Exception\ResourceUnavailableException;
use OCP\AppFramework\Attribute\Implementable;

/**
 * A resource Governance knows about, returned by
 * {@see IGovernanceResourceProvider::getResources()}.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IGovernanceResource {
	/**
	 * Whether the resource exists.
	 *
	 * @throws ResourceUnavailableException when an answer can't be determined at the moment of the call
	 * @experimental 36.0.0
	 */
	public function exists(): bool;

	/**
	 * Whether the resource is readable by the actor the resource was requested for.
	 * If the resource does not exist, this method should also return false.
	 *
	 * @throws ResourceUnavailableException when an answer can't be determined at the moment of the call
	 * @experimental 36.0.0
	 */
	public function isReadable(): bool;

	/**
	 * Whether the resource is updateable by the actor the resource was requested for.
	 * Governance may use this to determine whether the actor is allowed to
	 * assign/change a label or not.
	 *
	 * @throws ResourceUnavailableException when an answer can't be determined at the moment of the call
	 * @experimental 36.0.0
	 */
	public function isUpdatable(): bool;

	/**
	 * Whether this specific resource can carry $labelType at all — e.g.
	 * for Files, a Sensitivity label applies to a File but not to a Folder.
	 *
	 * @throws ResourceUnavailableException when an answer can't be determined at the moment of the call
	 * @experimental 36.0.0
	 */
	public function supportsLabelType(LabelType $labelType): bool;
}

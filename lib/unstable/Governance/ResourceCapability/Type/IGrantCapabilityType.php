<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Type;

use NCU\Governance\ResourceCapability\CanonicalAction;
use NCU\Governance\ResourceCapability\CanonicalPermission;
use OCP\AppFramework\Attribute\Implementable;

/**
 * A capability that represents a grant being allowed for someone.
 *
 * Note: in general a grant being present does not ensure that a user gets access
 *   to a resource, but that they can if, for example, the content is shared with
 *   them. A grant being absent means that the action will be forbidden.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IGrantCapabilityType extends ICapabilityType {

	/**
	 * Value that can be used to express that a grant is the same as a grant
	 * given by another app, for a different resource.
	 *
	 * For example: {@see CanonicalPermission::Read} for a file and table
	 * resource means that someone can get read access to both.
	 *
	 * @return string|value-of<CanonicalPermission>|value-of<CanonicalAction>
	 */
	public function getMeaning(): string;
}

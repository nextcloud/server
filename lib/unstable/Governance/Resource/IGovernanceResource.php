<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Governance\Resource;

use NCU\Governance\LabelType;
use NCU\Governance\ResourceCapability\Type\ICapabilityType;
use OCP\AppFramework\Attribute\Implementable;

/**
 * Names a kind of resource an API or function call can be about: a Talk room,
 * a Tables row, a file version. Resource types are open - every app defines
 * its own.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IGovernanceResource {
	/**
	 * TODO: something like appId.getIdentifier
	 *
	 * A stable url-safe name identifier for the resource in governance: this identifier
	 * will be persisted and used as part of URLs and will be considered a
	 * valid scope/entity-type parameter in all Governance APIs.
	 *
	 * Note: governance will register the providing class as owner of the identifier and further registrations with a different FQCN will fail.
	 * // todo: for registration, add a result object
	 * @since 36.0.0
	 */
	public function getIdentifier(): string;

	/**
	 * @since 36.0.0
	 */
	public function getAdapter(): IGovernanceResourceAdapter;

	/**
	 * A dictionary of the supported capabilities per label type.
	 *
	 * @return array<value-of<LabelType>, list<class-string<ICapabilityType>>>
	 * @since 36.0.0
	 */
	public function getSupportedCapabilities(): array;
}

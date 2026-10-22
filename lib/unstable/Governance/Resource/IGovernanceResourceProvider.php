<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Governance\Resource;

use NCU\Governance\Actor\Actor;
use NCU\Governance\Actor\PrincipalType;
use NCU\Governance\IGovernanceResourceProviderRegistry;
use NCU\Governance\LabelType;
use NCU\Governance\ResourceCapability\Type\ACapabilityType;
use OCP\AppFramework\Attribute\Implementable;

/**
 * Names a kind of resource known to Governance.
 *
 * Registered via {@see IGovernanceResourceProviderRegistry::registerResourceProvider()}
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IGovernanceResourceProvider {
	/**
	 * A stable, url-safe name identifier for the resource, MUST be in the
	 * following format: `{APP_ID}.name` e.g. tables.table, tables.row, etc.
	 *
	 * @return non-empty-lowercase-string
	 * @experimental 36.0.0
	 */
	public function getIdentifier(): string;

	/**
	 * A human-readable name for the resource.
	 *
	 * @return non-empty-string
	 * @experimental 36.0.0
	 */
	public function getName(): string;

	/**
	 * A dictionary of the supported capabilities per label type.
	 * Note that it is possible for a resource to support capabilities for a
	 * label type although no assignment is declared via {@see self::getSupportedLabelTypes()}.
	 *
	 * @return array<value-of<LabelType>, list<ACapabilityType>>
	 * @experimental 36.0.0
	 */
	public function getSupportedCapabilities(): array;

	/**
	 * Specifies the label types that can be assigned to the resource.
	 * Per-instance support can be configured in {@see IGovernanceResource::supportsLabelType()}.
	 *
	 * @return list<LabelType>
	 * @experimental 36.0.0
	 */
	public function getSupportedLabelTypes(): array;

	/**
	 * What $actor can do with each of $ids, MUST follow those rules:
	 *   - every requested ID gets an entry
	 *   - {@see IGovernanceResource::isReadable()} returns false for resources that don't exist or can't be read by the actor
	 *   - {@see ResourceUnavailableException} is thrown per resource for temporary failures
	 *   - a {@see PrincipalType::System} actor can read everything that exists
	 *   - one loader SHOULD be shared across the batch for efficiency
	 *
	 * @param non-empty-list<string> $ids
	 * @return array<non-empty-string, IGovernanceResource> resources keyed by ID
	 * @throws \RuntimeException when nothing can be answered at all about any resource of the same provider
	 * @experimental 36.0.0
	 */
	public function getResources(Actor $actor, array $ids): array;
}

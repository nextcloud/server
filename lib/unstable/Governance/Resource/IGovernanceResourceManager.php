<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\Resource;

use NCU\Governance\Context\Context;
use NCU\Governance\ResourceCapability\Type\ACapabilityType;
use OCP\AppFramework\Attribute\Consumable;

/**
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IGovernanceResourceManager {
	/**
	 * Resolves capabilities for the given resource IDs.
	 *
	 * TODO: what type of output to return?
	 *   - when a resource is not readable in context
	 *   - when a resource type is not registered/known
	 *   - when giving an answer is currently not possible
	 *   - when a listed capability is not registered/known
	 *   - when the list of ids is empty
	 *   - when the list of capabilities is empty
	 *
	 * TODO: (maybe?) change IGovernanceResource to an Abstract class and get the resource list which gets the resolved capabilities set.
	 * TODO: we don't want to keep fetching resource data every time something is asking
	 *
	 * @param Context $context the context for which capabilities are being resolved.
	 * @param non-empty-lowercase-string $providerId {@see IGovernanceResourceProvider::getIdentifier()}.
	 * @param string[] $resourceIds IDs of the resource to resolve the capabilities for.
	 * @param non-empty-lowercase-string[] $capabilityList {@see ACapabilityType::getIdentifier()}.
	 * @return array TODO: to be defined
	 * @experimental 36.0.0
	 */
	public function resolveCapabilities(Context $context, string $providerId, array $resourceIds, array $capabilityList): array;
}

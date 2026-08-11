<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceCapability;

use NCU\Resource\IResource;
use NCU\ResourceCapability\Type\ICapabilityType;
use NCU\ResourceRight\IRight;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Registry of the capability vocabularies apps declare per resource type.
 *
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface ICapabilityManager {
	/**
	 * Declares the capability vocabulary available for a resource type.
	 *
	 * @param class-string<IResource> $resourceClass
	 * @param list<ICapabilityType> $capabilities
	 * @throws \InvalidArgumentException when a capability with the same identifier is already registered for
	 *                                   $resourceClass, or requires a capability not registered for it
	 * @experimental 35.0.0
	 */
	public function registerCapabilitiesForResource(string $resourceClass, array $capabilities): void;

	/**
	 * @param class-string<IResource> $resourceClass
	 * @return list<ICapabilityType>
	 * @experimental 35.0.0
	 */
	public function getSupportedCapabilities(string $resourceClass): array;

	/**
	 * Capabilities are identified via a string, so apps that support the same
	 * capability (although with different parameters) use the same identifier.
	 *
	 * @param class-string<IResource> $resourceClass
	 * @param string $identifier an {@see ICapabilityType::getIdentifier()} value
	 * @experimental 35.0.0
	 */
	public function getCapabilityType(string $resourceClass, string $identifier): ?ICapabilityType;

	/**
	 * @param class-string<IResource> $resourceClass
	 * @param class-string<IRight> $right
	 * @experimental 35.0.0
	 */
	public function getCapabilityTypeForRight(string $resourceClass, string $right): ?ICapabilityType;

	/**
	 * Checks that a set of capabilities being granted is coherent, according to
	 * the requirements the types declare.
	 *
	 * Should be called before storing a capability grant configuration to make
	 * sure that requirements of capabilities are respected.
	 *
	 * @param class-string<IResource> $resourceClass
	 * @param list<string> $capabilityIdentifiers the identifiers for the capabilities being granted
	 * @throws \InvalidArgumentException when an identifier is not registered for $resourceClass, or a
	 *                                   requirement of a granted capability is missing from the set
	 * @experimental 35.0.0
	 */
	public function validateCapabilitySet(string $resourceClass, array $capabilityIdentifiers): void;
}

<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use NCU\Governance\Resource\IGovernanceResource;
use NCU\Governance\ResourceCapability\CanonicalAction;
use NCU\Governance\ResourceCapability\CanonicalPermission;
use NCU\Governance\ResourceCapability\Type\ICapabilityType;
use NCU\Governance\ResourceCapability\Type\IGrantCapabilityType;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Allows apps to register resources and their capabilities to governance.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IGovernanceRegistry {
	/**
	 * Register a provider declaring a resource that can carry governance labels.
	 *
	 * Call {@see GovernanceRegistry::registerResourceType()} instead of
	 * reaching for the manager.
	 *
	 * The provider is resolved when governance reads the registry, not when this
	 * is called: every app registers during the same bootstrap phase, in no
	 * guaranteed order, so a declaration may name a capability that is not
	 * registered yet.
	 *
	 * // todo: make sure that the identifier is url-safe!
	 *
	 * @param class-string<IGovernanceResource> $resource
	 * @experimental 36.0.0
	 */
	public function registerResource(string $resource): void;

	/**
	 * Every identifier declared by a registered provider.
	 *
	 * @return list<string>
	 * @experimental 36.0.0
	 */
	public function getIdentifiers(): array;

	/**
	 * Returns the instance of the resource registered with $identifier, null when the identifier was not registered.
	 *
	 * @experimental 36.0.0
	 */
	public function getRegisteredResource(string $resourceIdentifier): ?IGovernanceResource;

	/**
	 * Returns the class of the resource registered with $identifier, null when the identifier was not registered.
	 *
	 * @experimental 36.0.0
	 */
	public function getRegisteredResourceClass(string $resourceIdentifier): ?string;

	/**
	 * @param class-string<IGovernanceResource> $resourceClass
	 * @return list<ICapabilityType>
	 * @experimental 36.0.0
	 */
	public function getSupportedCapabilities(string $resourceClass): array;

	public function getSupportedCapabilitiesByIdentifier(string $resourceIdentifier): array;

	/**
	 * The registered type declared by this class, for compile-time call sites
	 * that name a capability class directly.
	 *
	 * Use {@see self::getCapabilityTypeForIdentifier()} for anything that comes
	 * from storage or from a client: what is persisted and sent over the wire is
	 * {@see ICapabilityType::getIdentifier()}, and two resource types may express
	 * the same identifier with different classes.
	 *
	 * @param class-string<IGovernanceResource> $resourceClass
	 * @param class-string<ICapabilityType> $capabilityClass
	 * @experimental 36.0.0
	 */
	public function getCapabilityType(string $resourceClass, string $capabilityClass): ?ICapabilityType;

	/**
	 * The registered type carrying this identifier for the resource type.
	 *
	 * Identifiers are unique per resource type, not globally, so the resource
	 * class is part of the lookup. When two registered types claim the same
	 * identifier for one resource class the collision is logged and the first
	 * declaration is kept.
	 *
	 * @param class-string<IGovernanceResource> $resourceClass
	 * @param string $capabilityIdentifier as returned by {@see ICapabilityType::getIdentifier()}
	 * @experimental 36.0.0
	 */
	public function getCapabilityTypeForIdentifier(string $resourceClass, string $capabilityIdentifier): ?ICapabilityType;

	/**
	 * Register one or more capabilities for a resource.
	 *
	 * @param class-string<IGovernanceResource> $resourceClass
	 * @param list<class-string<ICapabilityType>> $capabilityClasses
	 * @experimental 36.0.0
	 */
	public function registerCapabilities(string $resourceClass, array $capabilityClasses): void;

	/**
	 * @param class-string<IGovernanceResource> $resourceClass
	 * @param string|value-of<CanonicalPermission>|value-of<CanonicalAction> $meaning
	 * @experimental 36.0.0
	 */
	public function getGrantCapabilityForMeaning(string $resourceClass, string $meaning): ?IGrantCapabilityType;

	/**
	 * TODO: this depends on whether capabilities keep requirements or not. If not, this can be dropped.
	 *
	 * Checks that a set of capabilities being granted is coherent, according to
	 * the requirements the types declare.
	 *
	 * Should be called before storing a capability grant configuration to make
	 * sure that requirements of capabilities are respected.
	 *
	 * @param class-string<IGovernanceResource> $resourceClass
	 * @param list<class-string<ICapabilityType>> $capabilityClasses the capabilities being granted
	 * @throws \InvalidArgumentException when a capability is not registered for $resourceClass, or a
	 *                                   requirement of a granted capability is missing from the set
	 * @experimental 36.0.0
	 */
	public function validateCapabilitySet(string $resourceClass, array $capabilityClasses): void;
}

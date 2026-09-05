<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceCapability;

use InvalidArgumentException;
use NCU\Actor\IActor;
use NCU\Resource\IResource;
use NCU\ResourceCapability\Type\ICapabilityType;
use OCP\AppFramework\Attribute\Dispatchable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Server;

/**
 * Asks what capability values currently apply to resources, for a given actor.
 *
 * Default empty: nothing is returned for a resource unless a listener calls
 * {@see addCapability()} for it. Values contributed by several listeners for
 * the same capability type are combined by that type's resolve().
 *
 * One batch covers one resource type, so a listener can resolve it using bulk
 * lookups, and skips a batch it has no opinion on by comparing {@see $resourceType}.
 *
 * @experimental 35.0.0
 */
#[Listenable(since: '35.0.0')]
#[Dispatchable(since: '35.0.0')]
final class QueryCapabilitiesEvent extends Event {
	/**
	 * @var array<string, list<Capability>> resource id => folded capabilities
	 */
	private array $capabilities = [];
	private bool $dispatched = false;

	/** @var class-string<IResource> */
	public readonly string $resourceType;

	/** @var array<string, true> */
	private array $resourceIdIndex = [];

	/** @var array<string, true> */
	private array $capabilityIdentifierIndex = [];

	/**
	 * @var non-empty-list<string>
	 * @experimental 35.0.0
	 */
	public readonly array $resourceIds;

	/**
	 * @psalm-param string $resourceType
	 * @param class-string<IResource> $resourceType
	 * @psalm-property class-string<IResource> $resourceType
	 * @psalm-param array<array-key, mixed> $resourceIds
	 * @param non-empty-list<string> $resourceIds ids of that type
	 * @psalm-param array<array-key, mixed> $identifierFilter
	 * @param list<string> $identifierFilter restricts the answer to these
	 *                                       {@see ICapabilityType::getIdentifier()} values
	 * @throws InvalidArgumentException on an empty batch
	 * @experimental 35.0.0
	 */
	public function __construct(
		public readonly IActor $actor,
		string $resourceType,
		array $resourceIds,
		public readonly array $identifierFilter = [],
	) {
		parent::__construct();

		if (!is_a($resourceType, IResource::class, true)) {
			throw new InvalidArgumentException('Resource type must implement IResource.');
		}
		$this->resourceType = $resourceType;

		if ($resourceIds === []) {
			throw new InvalidArgumentException('A QueryCapabilitiesEvent needs at least one resource');
		}

		$uniqueResourceIds = [];
		foreach ($resourceIds as $resourceId) {
			if (!is_string($resourceId)) {
				throw new InvalidArgumentException('resourceId must be a string');
			}

			$resourceId = trim($resourceId);
			if ($resourceId === '' || isset($this->resourceIdIndex[$resourceId])) {
				continue;
			}

			$uniqueResourceIds[] = $resourceId;
			$this->resourceIdIndex[$resourceId] = true;
		}

		if ($uniqueResourceIds === []) {
			throw new InvalidArgumentException('A QueryCapabilitiesEvent needs at least one resource');
		}
		$this->resourceIds = $uniqueResourceIds;

		foreach ($this->identifierFilter as $identifier) {
			if (!is_string($identifier)) {
				throw new InvalidArgumentException('identifiers must be strings');
			}

			$identifier = trim($identifier);
			if ($identifier === '' || isset($this->identifierIndex[$identifier])) {
				continue;
			}
			$this->capabilityIdentifierIndex[$identifier] = true;
		}
	}

	/**
	 * Listener-facing: contribute a capability materialization for one resource.
	 *
	 * @throws InvalidArgumentException when $resourceId isn't part of this event
	 * @experimental 35.0.0
	 */
	public function addCapability(string $resourceId, Capability $capability): void {
		if (!isset($this->resourceIdIndex[$resourceId])) {
			throw new InvalidArgumentException('addCapability() called for a resource not in this event: ' . $resourceId);
		}
		$this->capabilities[$resourceId][] = $capability;
	}

	/**
	 * Dispatches once. Every registered listener is consulted, for every
	 * resource in the batch.
	 *
	 * @return array<string, list<Capability>> resource id => capabilities, one entry per type
	 * @experimental 35.0.0
	 */
	public function queryCapabilities(): array {
		if (!$this->dispatched) {
			$this->dispatched = true;
			Server::get(IEventDispatcher::class)->dispatchTyped($this);
		}

		$result = [];
		foreach ($this->resourceIds as $resourceId) {
			$result[$resourceId] = $this->resolveForResource($this->capabilities[$resourceId] ?? []);
		}

		return $result;
	}

	/**
	 * The capabilities that apply to one resource of the batch.
	 *
	 * @return list<Capability>
	 * @experimental 35.0.0
	 */
	public function getCapabilitiesFor(string $resourceId): array {
		return $this->queryCapabilities()[$resourceId] ?? [];
	}

	/**
	 * Convenience for the common single-resource call site.
	 *
	 * @return list<Capability>
	 * @throws InvalidArgumentException when the batch covers more than one resource,
	 *                                  since answering for only the first would silently
	 *                                  discard the rest
	 * @experimental 35.0.0
	 */
	public function getCapabilities(): array {
		if (count($this->resourceIds) > 1) {
			throw new InvalidArgumentException('Multiple resource IDs provided, one expected');
		}

		return $this->getCapabilitiesFor($this->resourceIds[0]);
	}

	/**
	 * Groups one resource's contributions by identifier and hands each group to the
	 * type that defined it. A type nobody registered is passed through
	 * untouched rather than dropped: the caller asked about it, and the kernel
	 * has no basis for choosing between answers it knows nothing about.
	 *
	 * @param list<Capability> $capabilities contributed capabilities for one resource
	 * @return list<Capability> one entry per type
	 */
	private function resolveForResource(array $capabilities): array {
		$manager = Server::get(ICapabilityManager::class);

		$byIdentifier = [];
		foreach ($capabilities as $capability) {
			// discard unrequested capabilities
			if ($this->capabilityIdentifierIndex !== [] && !isset($this->capabilityIdentifierIndex[$capability->identifier])) {
				continue;
			}
			$byIdentifier[$capability->identifier][] = $capability;
		}

		$result = [];
		foreach ($byIdentifier as $identifier => $typeCapabilities) {
			$capabilityType = $manager->getCapabilityType($this->resourceType, $identifier);
			if ($capabilityType === null) {
				array_push($result, ...$typeCapabilities);
				continue;
			}
			$result[] = $capabilityType->resolve($typeCapabilities);
		}

		return $result;
	}
}

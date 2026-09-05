<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\ResourceCapability;

use InvalidArgumentException;
use NCU\ResourceCapability\ICapabilityManager;
use NCU\ResourceCapability\Type\ICapabilityType;

final class CapabilityManager implements ICapabilityManager {
	/** @var array<class-string, array<string, ICapabilityType>> resource class => capability type => type */
	private array $types = [];

	/**
	 * {@inheritdoc}
	 * @throws InvalidArgumentException when a capability type is already registered for this
	 *                                  resource class, or requires a type not registered for it
	 */
	#[\Override]
	public function registerCapabilitiesForResource(string $resourceClass, array $capabilities): void {
		foreach ($capabilities as $capability) {
			// todo: validate capability instance of ...
			$existing = $this->types[$resourceClass][$capability->getIdentifier()] ?? null;
			if ($existing !== null) {
				throw new InvalidArgumentException(sprintf(
					'Capability type "%s" is already registered for resource type %s',
					$capability->getIdentifier(),
					$resourceClass,
				));
			}
			$this->types[$resourceClass][$capability->getIdentifier()] = $capability;
		}

		// resolved after the whole batch is in, so types may require each other in
		// any order within one registration
		foreach ($capabilities as $capability) {
			foreach ($capability->getRequires() as $required) {
				if (!isset($this->types[$resourceClass][$required])) {
					throw new InvalidArgumentException(sprintf(
						'Capability type "%s" requires "%s", which is not registered for resource type %s. Requirements name types of the same resource type.',
						$capability->getIdentifier(),
						$required,
						$resourceClass,
					));
				}
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function getSupportedCapabilities(string $resourceClass): array {
		return array_values($this->types[$resourceClass] ?? []);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function getCapabilityType(string $resourceClass, string $identifier): ?ICapabilityType {
		return $this->types[$resourceClass][$identifier] ?? null;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function getCapabilityTypeForRight(string $resourceClass, string $right): ?ICapabilityType {
		foreach ($this->types[$resourceClass] ?? [] as $type) {
			if ($type->getGrantedRight() === $right) {
				return $type;
			}
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 * @throws InvalidArgumentException when a type is unknown for this resource class, or a
	 *                                  requirement of a granted type is missing from the set
	 */
	#[\Override]
	public function validateCapabilitySet(string $resourceClass, array $capabilityIdentifiers): void {
		$granted = array_fill_keys($capabilityIdentifiers, true);

		foreach ($capabilityIdentifiers as $type) {
			$capability = $this->types[$resourceClass][$type] ?? null;
			if ($capability === null) {
				throw new InvalidArgumentException(sprintf(
					'Capability type "%s" is not registered for resource type %s.',
					$type,
					$resourceClass,
				));
			}

			foreach ($capability->getRequires() as $required) {
				if (!isset($granted[$required])) {
					throw new InvalidArgumentException(sprintf(
						'Capability type "%s" requires "%s", which is not part of the granted set.',
						$type,
						$required,
					));
				}
			}
		}
	}
}

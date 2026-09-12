<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceCapability;

use NCU\ResourceCapability\Type\ICapabilityType;
use OCP\AppFramework\Attribute\Consumable;

/**
 * One capability: a type from the registered vocabulary and the values
 * configured for it.
 *
 * Can be handed to {@see ICapabilityType::validate()} for validation, and is what
 * {@see QueryCapabilitiesEvent} answers with.
 *
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final readonly class Capability {
	/**
	 * @param string $identifier an {@see ICapabilityType::getIdentifier()} value
	 * @param array<string, mixed> $parameters values by parameter name
	 * @experimental 35.0.0
	 */
	public function __construct(
		public string $identifier,
		public array $parameters,
	) {
	}

	/**
	 * Whether a value was configured for $name, including an explicit null.
	 *
	 * @experimental 35.0.0
	 */
	public function has(string $name): bool {
		return array_key_exists($name, $this->parameters);
	}

	/**
	 * The configured value, or null when the parameter was omitted. Use
	 * {@see has()} to tell an omitted parameter from a null one.
	 *
	 * @experimental 35.0.0
	 */
	public function get(string $name): mixed {
		return $this->parameters[$name] ?? null;
	}
}

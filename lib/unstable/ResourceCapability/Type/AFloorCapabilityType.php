<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceCapability\Type;

use NCU\ResourceCapability\Capability;
use OCP\AppFramework\Attribute\Implementable;

/**
 * A lower bound: whichever provider demands most wins.
 *
 * Declares one whole-number parameter, named by {@see self::getValueParameterName()}.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class AFloorCapabilityType extends ACapabilityType {
	/**
	 * The name of the parameter representing the floor value.
	 *
	 * @experimental 35.0.0
	 */
	abstract public function getValueParameterName(): string;

	/**
	 * @param non-empty-list<Capability> $capabilities
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function resolve(array $capabilities): Capability {
		$name = $this->getValueParameterName();
		$winner = $capabilities[0];

		foreach ($capabilities as $capability) {
			// a provider that omits the value abstains rather than asserting zero
			if (!array_key_exists($name, $capability->parameters)) {
				continue;
			}
			if (!array_key_exists($name, $winner->parameters)
				|| $capability->parameters[$name] > $winner->parameters[$name]) {
				$winner = $capability;
			}
		}

		return $winner;
	}
}

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
 * A capability that carries no value: granting it is the whole statement.
 *
 * Resolves by presence - any provider granting it means it is granted - so
 * every answer is equivalent and the first is returned.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class AGrantCapabilityType extends ACapabilityType {
	/**
	 * @param non-empty-list<Capability> $capabilities
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function resolve(array $capabilities): Capability {
		return $capabilities[0];
	}
}

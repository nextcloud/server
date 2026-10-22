<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Type;

use NCU\Governance\ResourceCapability\Capability;
use NCU\Governance\ResourceCapability\SemanticType;
use OCP\AppFramework\Attribute\Implementable;
use Override;

/**
 * A capability that carries no value: granting it is the whole statement.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
abstract class AGrantCapabilityType extends ACapabilityType {
	/**
	 * @experimental 36.0.0
	 */
	#[Override]
	final public function getSemanticType(): string {
		return SemanticType::Grant->value;
	}

	/**
	 * @experimental 36.0.0
	 */
	#[Override]
	public function validate(Capability $capability): true|string {
		return true;
	}
}

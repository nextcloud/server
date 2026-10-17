<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Parameter;

use NCU\Governance\ResourceCapability\Capability;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;

/**
 * Defaults shared by every parameter type: no hint, required, and a format()
 * carrying the common fields. Extend a typed base rather than this when possible.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
abstract class ACapabilityParameterType implements ICapabilityParameterType {
	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		return null;
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function isRequired(Capability $capability): bool {
		return true;
	}

	/**
	 * @return array<string, mixed>
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function format(IFactory $l10nFactory): array {
		return [
			'name' => $this->getName(),
			'displayName' => $this->getDisplayName($l10nFactory),
			'hint' => $this->getHint($l10nFactory),
		];
	}
}

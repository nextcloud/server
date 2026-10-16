<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Parameter;

use NCU\Governance\ResourceCapability\Capability;
use OC\Core\AppInfo\Application;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;

/**
 * Boolean parameter type.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
abstract class ABoolCapabilityParameterType extends ACapabilityParameterType {
	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, Capability $capability, mixed $value): true|string {
		if (is_bool($value)) {
			return true;
		}

		return $l10nFactory->get(Application::APP_ID)->t('%s must be true or false', [$this->getName()]);
	}

	/**
	 * @return array<string, mixed>
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function format(IFactory $l10nFactory): array {
		return parent::format($l10nFactory) + ['type' => 'bool'];
	}
}

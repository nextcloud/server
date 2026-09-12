<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceCapability\Parameter;

use NCU\ResourceCapability\Capability;
use OC\Core\AppInfo\Application;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;

/**
 * One of a fixed set of values.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class AEnumCapabilityParameterType extends ACapabilityParameterType {
	/**
	 * @return non-empty-list<string>
	 * @experimental 35.0.0
	 */
	abstract public function getValidValues(): array;

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, Capability $capability, mixed $value): true|string {
		if (is_string($value) && in_array($value, $this->getValidValues(), true)) {
			return true;
		}

		return $l10nFactory->get(Application::APP_ID)->t('%1$s must be one of: %2$s', [
			$this->getName(),
			implode(', ', $this->getValidValues()),
		]);
	}

	/**
	 * @return array<string, mixed>
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function format(IFactory $l10nFactory): array {
		return parent::format($l10nFactory) + [
			'type' => 'enum',
			'validValues' => $this->getValidValues(),
		];
	}
}

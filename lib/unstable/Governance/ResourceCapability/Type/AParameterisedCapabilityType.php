<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Type;

use NCU\Governance\ResourceCapability\Capability;
use NCU\Governance\ResourceCapability\Parameter\ICapabilityParameterType;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;
use Override;

/**
 * Abstract capability with parameters.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
abstract class AParameterisedCapabilityType extends ACapabilityType {
	/**
	 * @experimental 36.0.0
	 */
	public function __construct(
		protected readonly IFactory $l10nFactory,
	) {
	}

	/**
	 * @return list<ICapabilityParameterType>
	 * @experimental 36.0.0
	 */
	abstract public function getParameters(): array;

	/**
	 * Each declared parameter is checked: present when required, and acceptable
	 * to its own type. A value for a parameter that was never declared is
	 * rejected rather than ignored, so a typo does not silently persist.
	 *
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function validate(Capability $capability): true|string {
		$l = $this->l10nFactory->get('core');
		$declared = [];

		foreach ($this->getParameters() as $parameter) {
			$name = $parameter->getName();
			$declared[$name] = true;

			if (!$capability->has($name)) {
				if ($parameter->isRequired($capability)) {
					return $l->t('%s is required', [$name]);
				}
				continue;
			}

			$result = $parameter->validateValue($capability, $capability->get($name));
			if ($result !== true) {
				return $result;
			}
		}

		foreach (array_keys($capability->parameters) as $name) {
			if (!isset($declared[$name])) {
				return $l->t('%s is not a parameter of this capability', [$name]);
			}
		}

		return true;
	}

	/**
	 * @experimental 36.0.0
	 */
	#[Override]
	protected function getAdditionalFormatValues(): array {
		return [
			'parameters' => array_map(static fn (ICapabilityParameterType $parameter): array => $parameter->format(),
				$this->getParameters(), ),
		];
	}
}

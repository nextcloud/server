<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceCapability\Type;

use NCU\ResourceCapability\Capability;
use NCU\ResourceCapability\Parameter\ICapabilityParameterType;
use OC\Core\AppInfo\Application;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;

/**
 * Defaults shared by every capability: no hint, no group, no parameters, no
 * requirements, no granted right, and a validate function  that checks each declared
 * parameter against the capability it belongs to.
 *
 * Only {@see self::resolve()} is left abstract, since combining two answers is the
 * one thing the kernel cannot guess.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class ACapabilityType implements ICapabilityType {
	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		return null;
	}

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getGroup(): ?string {
		return null;
	}

	/**
	 * @return list<ICapabilityParameterType>
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getParameters(): array {
		return [];
	}

	/**
	 * @return list<string>
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getRequires(): array {
		return [];
	}

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getGrantedRight(): ?string {
		return null;
	}

	/**
	 * Each declared parameter is checked: present when required, and acceptable
	 * to its own type. A value for a parameter that was never declared is
	 * rejected rather than ignored, so a typo does not silently persist.
	 *
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function validate(IFactory $l10nFactory, Capability $capability): true|string {
		$l = $l10nFactory->get(Application::APP_ID);
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

			$result = $parameter->validateValue($l10nFactory, $capability, $capability->get($name));
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
	 * @return array<string, mixed>
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function format(IFactory $l10nFactory): array {
		return [
			'identifier' => $this->getIdentifier(),
			'displayName' => $this->getDisplayName($l10nFactory),
			'hint' => $this->getHint($l10nFactory),
			'group' => $this->getGroup(),
			'requires' => $this->getRequires(),
			'parameters' => array_map(
				static fn (ICapabilityParameterType $parameter): array => $parameter->format($l10nFactory),
				$this->getParameters(),
			),
		];
	}
}

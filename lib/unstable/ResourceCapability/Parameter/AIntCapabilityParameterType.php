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
 * A whole number, optionally bounded.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class AIntCapabilityParameterType extends ACapabilityParameterType {
	/**
	 * @experimental 35.0.0
	 */
	abstract public function getMin(): ?int;

	/**
	 * @experimental 35.0.0
	 */
	abstract public function getMax(): ?int;

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, Capability $capability, mixed $value): true|string {
		$l = $l10nFactory->get(Application::APP_ID);
		if (!is_int($value)) {
			return $l->t('%s must be a whole number', [$this->getName()]);
		}

		if (($min = $this->getMin()) !== null && $value < $min) {
			return $l->t('%1$s must be at least %2$s', [$this->getName(), $min]);
		}

		if (($max = $this->getMax()) !== null && $value > $max) {
			return $l->t('%1$s must be at most %2$s', [$this->getName(), $max]);
		}

		return true;
	}

	/**
	 * @return array<string, mixed>
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function format(IFactory $l10nFactory): array {
		return parent::format($l10nFactory) + [
			'type' => 'int',
			'min' => $this->getMin(),
			'max' => $this->getMax(),
		];
	}
}

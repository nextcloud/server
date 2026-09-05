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
 * Free text, optionally bounded in length.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class AStringCapabilityParameterType extends ACapabilityParameterType {
	/**
	 * @experimental 35.0.0
	 */
	abstract public function getMinLength(): ?int;

	/**
	 * @experimental 35.0.0
	 */
	abstract public function getMaxLength(): ?int;

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, Capability $capability, mixed $value): true|string {
		$l = $l10nFactory->get(Application::APP_ID);
		if (!is_string($value)) {
			return $l->t('%s must be text', [$this->getName()]);
		}

		if (($minLength = $this->getMinLength()) !== null && mb_strlen($value) < $minLength) {
			return $l->t('%1$s needs at least %2$s characters', [$this->getName(), $minLength]);
		}

		if (($maxLength = $this->getMaxLength()) !== null && mb_strlen($value) > $maxLength) {
			return $l->t('%1$s takes %2$s characters at most', [$this->getName(), $maxLength]);
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
			'type' => 'string',
			'minLength' => $this->getMinLength(),
			'maxLength' => $this->getMaxLength(),
		];
	}
}

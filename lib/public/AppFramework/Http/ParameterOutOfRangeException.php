<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http;

/**
 * @since 29.0.0
 */
class ParameterOutOfRangeException extends \OutOfRangeException {
	/**
	 * @since 29.0.0
	 */
	public function __construct(
		protected string $parameterName,
		protected int $actualValue,
		protected int $minValue,
		protected int $maxValue,
	) {
		parent::__construct(
			sprintf(
				'Parameter %s must be between %d and %d',
				$this->parameterName,
				$this->minValue,
				$this->maxValue,
			)
		);
	}

	/**
	 * @since 29.0.0
	 */
	public function getParameterName(): string {
		return $this->parameterName;
	}

	/**
	 * @since 29.0.0
	 */
	public function getActualValue(): int {
		return $this->actualValue;
	}

	/**
	 * @since 29.0.0
	 */
	public function getMinValue(): int {
		return $this->minValue;
	}

	/**
	 * @since 29.0.0
	 */
	public function getMaxValue(): int {
		return $this->maxValue;
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http;

/**
 * @since 35.0.0
 */
class InvalidStringParameterException extends \InvalidArgumentException {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		protected string $parameterName,
		protected string $constraint,
	) {
		parent::__construct(
			sprintf('Parameter %s must be a %s', $this->parameterName, $this->constraint)
		);
	}

	/**
	 * @since 35.0.0
	 */
	public function getParameterName(): string {
		return $this->parameterName;
	}

	/**
	 * @since 35.0.0
	 */
	public function getConstraint(): string {
		return $this->constraint;
	}
}

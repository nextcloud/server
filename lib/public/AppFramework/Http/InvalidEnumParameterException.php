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
class InvalidEnumParameterException extends \InvalidArgumentException {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		protected string $parameterName,
		protected string $value,
		protected string $enumClass,
	) {
		parent::__construct(
			sprintf('Parameter %s with value "%s" is not a valid case of %s', $this->parameterName, $this->value, $this->enumClass)
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
	public function getValue(): string {
		return $this->value;
	}

	/**
	 * @since 35.0.0
	 */
	public function getEnumClass(): string {
		return $this->enumClass;
	}
}

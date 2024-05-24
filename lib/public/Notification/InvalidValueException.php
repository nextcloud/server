<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Notification;

/**
 * @since 30.0.0
 */
class InvalidValueException extends \InvalidArgumentException {
	/**
	 * @since 30.0.0
	 */
	public function __construct(
		protected string $field,
		?\Throwable $previous = null,
	) {
		parent::__construct('Value provided for ' . $field . ' is not valid', previous: $previous);
	}

	/**
	 * @since 30.0.0
	 */
	public function getFieldIdentifier(): string {
		return $this->field;
	}
}

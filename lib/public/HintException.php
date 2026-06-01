<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP;

/**
 * An Exception class with the intention to be presented to the end user
 *
 * @since 23.0.0
 */
class HintException extends \Exception {
	/**
	 * HintException constructor.
	 *
	 * @since 23.0.0
	 * @param string $message The error message. It is not intended to be shown
	 *                        to the end user unless the hint is empty, and
	 *                        therefore should not be translated.
	 * @param string $hint A useful message presented to the end user. It should
	 *                     be translated, but must not contain sensitive data.
	 * @param int $code
	 * @param \Exception|null $previous
	 */
	public function __construct(
		string $message,
		private string $hint = '',
		int $code = 0,
		?\Exception $previous = null,
	) {
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Returns a string representation of this Exception that includes the error
	 * code, the message and the hint.
	 *
	 * @since 23.0.0
	 */
	public function __toString(): string {
		return self::class . ": [{$this->code}]: {$this->message} ({$this->hint})\n";
	}

	/**
	 * Returns the hint with the intention to be presented to the end user. If
	 * an empty hint was specified upon instantiation, the message is returned
	 * instead.
	 *
	 * @since 23.0.0
	 */
	public function getHint(): string {
		return $this->hint !== '' ? $this->hint : $this->message;
	}
}

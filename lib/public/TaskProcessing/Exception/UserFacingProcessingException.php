<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\TaskProcessing\Exception;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Exception thrown during processing of a task
 * by a synchronous provider with the possibility to set a user-facing
 * error message
 *
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
class UserFacingProcessingException extends ProcessingException {

	/**
	 * @param string $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 * @param string|null $userFacingMessage
	 * @since 33.0.0
	 */
	public function __construct(
		string $message = '',
		int $code = 0,
		?\Throwable $previous = null,
		private ?string $userFacingMessage = null,
	) {
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @since 33.0.0
	 */
	public function getUserFacingMessage(): ?string {
		return $this->userFacingMessage;
	}

	/**
	 * @param null|string $userFacingMessage Must be already translated into the language of the user
	 * @since 33.0.0
	 */
	public function setUserFacingMessage(?string $userFacingMessage): void {
		$this->userFacingMessage = $userFacingMessage;
	}
}

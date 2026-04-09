<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TextToImage\Events;

use OCP\TextToImage\Task;

/**
 * @since 28.0.0
 * @deprecated 30.0.0
 */
class TaskFailedEvent extends AbstractTextToImageEvent {
	/**
	 * @param Task $task
	 * @param string $errorMessage
	 * @since 28.0.0
	 */
	public function __construct(
		Task $task,
		private string $errorMessage,
	) {
		parent::__construct($task);
	}

	/**
	 * @return string
	 * @since 28.0.0
	 */
	public function getErrorMessage(): string {
		return $this->errorMessage;
	}
}

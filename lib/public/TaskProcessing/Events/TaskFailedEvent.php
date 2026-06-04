<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\TaskProcessing\Events;

use OCP\TaskProcessing\Task;

/**
 * @since 30.0.0
 */
class TaskFailedEvent extends AbstractTaskProcessingEvent {
	/**
	 * @param Task $task
	 * @param string $errorMessage
	 * @since 30.0.0
	 */
	public function __construct(
		Task $task,
		private readonly string $errorMessage,
	) {
		parent::__construct($task);
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function getErrorMessage(): string {
		return $this->errorMessage;
	}
}

<?php

namespace OCP\TaskProcessing\Events;

use OCP\TaskProcessing\Task;

/**
 * @since 30.0.0
 */
class TaskFailedEvent extends AbstractTextProcessingEvent {
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

<?php

namespace OCP\TextProcessing\Events;

use OCP\TextProcessing\Task;

/**
 * @since 27.1.0
 */
class TaskSuccessfulEvent extends AbstractTextProcessingEvent {
	/**
	 * @param Task $task
	 * @since 27.1.0
	 */
	public function __construct(Task $task) {
		parent::__construct($task);
	}
}

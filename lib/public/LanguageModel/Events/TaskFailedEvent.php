<?php

namespace OCP\LanguageModel\Events;

use OCP\LanguageModel\ILanguageModelTask;

/**
 * @since 28.0.0
 */
class TaskFailedEvent extends AbstractLanguageModelEvent {
	/**
	 * @param ILanguageModelTask $task
	 * @param string $errorMessage
	 * @since 28.0.0
	 */
	public function __construct(ILanguageModelTask $task,
		private string $errorMessage) {
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

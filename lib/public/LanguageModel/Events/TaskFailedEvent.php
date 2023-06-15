<?php

namespace OCP\LanguageModel\Events;

use OCP\LanguageModel\AbstractLanguageModelTask;

/**
 * @since 28.0.0
 */
class TaskFailedEvent extends AbstractLanguageModelEvent {

	public function __construct(AbstractLanguageModelTask $task,
								private string $errorMessage) {
		parent::__construct($task);
	}

	/**
	 * @return string
	 */
	public function getErrorMessage(): string {
		return $this->errorMessage;
	}
}

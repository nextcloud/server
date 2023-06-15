<?php

namespace OCP\LanguageModel\Events;

use OCP\LanguageModel\AbstractLanguageModelTask;

/**
 * @since 28.0.0
 */
class TaskSuccessfulEvent extends AbstractLanguageModelEvent {

	public function __construct(AbstractLanguageModelTask $task,
								private string $output) {
		parent::__construct($task);
	}

	/**
	 * @return string
	 */
	public function getErrorMessage(): string {
		return $this->output;
	}
}

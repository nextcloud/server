<?php

namespace OCP\LanguageModel\Events;

use OCP\LanguageModel\ILanguageModelTask;

/**
 * @since 28.0.0
 */
class TaskSuccessfulEvent extends AbstractLanguageModelEvent {

	public function __construct(ILanguageModelTask $task,
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

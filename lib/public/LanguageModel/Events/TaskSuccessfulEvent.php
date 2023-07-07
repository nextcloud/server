<?php

namespace OCP\LanguageModel\Events;

use OCP\LanguageModel\ILanguageModelTask;

/**
 * @since 27.1.0
 */
class TaskSuccessfulEvent extends AbstractLanguageModelEvent {
	/**
	 * @param ILanguageModelTask $task
	 * @since 27.1.0
	 */
	public function __construct(ILanguageModelTask $task) {
		parent::__construct($task);
	}
}

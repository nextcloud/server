<?php

namespace OCP\LanguageModel\Events;

/**
 * @since 28.0.0
 */
class PromptFailedEvent extends AbstractLanguageModelEvent {

	public function __construct(int $requestId,
								?string $userId,
								string $appId,
								private string $errorMessage) {
		parent::__construct($requestId, $userId, $appId);
	}

	/**
	 * @since 28.0.0
	 * @return string
	 */
	public function getErrorMessage(): string {
		return $this->errorMessage;
	}
}

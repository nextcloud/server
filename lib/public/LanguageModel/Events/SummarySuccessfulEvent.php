<?php

namespace OCP\LanguageModel\Events;

/**
 * @since 28.0.0
 */
class SummarySuccessfulEvent extends AbstractLanguageModelEvent {

	public function __construct(int $requestId,
								?string $userId,
								string $appId,
								private string $output) {
		parent::__construct($requestId, $userId, $appId);
	}

	/**
	 * @return string
	 */
	public function getOutput(): string {
		return $this->output;
	}
}

<?php

namespace OCP\SpeechToText\Events;

class TranscriptionFailedEvent extends AbstractTranscriptionEvent {

	/**
	 * @since 27.0.0
	 */
	public function __construct(
		int $fileId,
		private string $errorMessage
	) {
		parent::__construct($fileId);
	}

	/**
	 * @since 27.0.0
	 * @return string The error message
	 */
	public function getErrorMessage(): string {
		return $this->errorMessage;
	}
}

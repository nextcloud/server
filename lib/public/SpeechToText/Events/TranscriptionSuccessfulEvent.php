<?php

namespace OCP\SpeechToText\Events;

use OCP\Files\File;

class TranscriptionSuccessfulEvent extends AbstractTranscriptionEvent {

	/**
	 * @since 27.0.0
	 */
	public function __construct(
		int $fileId,
		private string $transcript
	) {
		parent::__construct($fileId);
	}

	/**
	 * @since 27.0.0
	 * @return string The transcript of the media file
	 */
	public function getTranscript(): string {
		return $this->transcript;
	}
}

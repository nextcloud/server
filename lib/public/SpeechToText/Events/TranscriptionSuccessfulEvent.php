<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\SpeechToText\Events;

use OCP\Files\File;

/**
 * This Event is emitted when a transcription of a media file happened successfully
 * @since 27.0.0
 * @deprecated 30.0.0
 */
class TranscriptionSuccessfulEvent extends AbstractTranscriptionEvent {
	/**
	 * @since 27.0.0
	 */
	public function __construct(
		int $fileId,
		?File $file,
		private string $transcript,
		?string $userId,
		string $appId,
	) {
		parent::__construct($fileId, $file, $userId, $appId);
	}

	/**
	 * @since 27.0.0
	 * @return string The transcript of the media file
	 */
	public function getTranscript(): string {
		return $this->transcript;
	}
}

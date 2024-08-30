<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\SpeechToText;

use InvalidArgumentException;
use OCP\Files\File;
use OCP\PreConditionNotMetException;
use RuntimeException;

/**
 * @since 27.0.0
 * @deprecated 30.0.0
 */
interface ISpeechToTextManager {
	/**
	 * @since 27.0.0
	 */
	public function hasProviders(): bool;

	/**
	 * @return ISpeechToTextProvider[]
	 * @since 27.1.0
	 */
	public function getProviders(): array;

	/**
	 * Will schedule a transcription process in the background. The result will become available
	 * with the \OCP\SpeechToText\Events\TranscriptionFinishedEvent
	 * You should add context information to the context array to re-identify the transcription result as
	 * belonging to your transcription request.
	 *
	 * @param File $file The media file to transcribe
	 * @param ?string $userId The user that triggered this request (only for convenience, will be available on the TranscriptEvents)
	 * @param string $appId The app that triggered this request (only for convenience, will be available on the TranscriptEvents)
	 * @throws PreConditionNotMetException If no provider was registered but this method was still called
	 * @throws InvalidArgumentException If the file could not be found or is not of a supported type
	 * @since 27.0.0
	 */
	public function scheduleFileTranscription(File $file, ?string $userId, string $appId): void;

	/**
	 * Will cancel a scheduled transcription process
	 *
	 * @param File $file The media file involved in the transcription
	 * @param ?string $userId The user that triggered this request
	 * @param string $appId The app that triggered this request
	 * @throws InvalidArgumentException If the file could not be found or is not of a supported type
	 * @since 29.0.0
	 */
	public function cancelScheduledFileTranscription(File $file, ?string $userId, string $appId): void;

	/**
	 * @param File $file The media file to transcribe
	 * @param ?string $userId The user that triggered this request
	 * @param string $appId The app that triggered this request
	 * @returns string The transcription of the passed media file
	 * @throws PreConditionNotMetException If no provider was registered but this method was still called
	 * @throws InvalidArgumentException If the file could not be found or is not of a supported type
	 * @throws RuntimeException If the transcription failed for other reasons
	 * @since 27.0.0
	 */
	public function transcribeFile(File $file, ?string $userId, string $appId): string;
}

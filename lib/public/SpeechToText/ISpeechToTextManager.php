<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


namespace OCP\SpeechToText;

use InvalidArgumentException;
use OCP\Files\File;
use OCP\PreConditionNotMetException;
use RuntimeException;

/**
 * @since 27.0.0
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
	 * @param File $file The media file to transcribe
	 * @returns string The transcription of the passed media file
	 * @throws PreConditionNotMetException If no provider was registered but this method was still called
	 * @throws InvalidArgumentException If the file could not be found or is not of a supported type
	 * @throws RuntimeException If the transcription failed for other reasons
	 * @since 27.0.0
	 */
	public function transcribeFile(File $file): string;
}

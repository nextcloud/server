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


namespace OCP\LanguageModel;

use InvalidArgumentException;
use OCP\PreConditionNotMetException;
use RuntimeException;

interface ILanguageModelManager {
	/**
	 * @since 28.0.0
	 */
	public function hasProviders(): bool;

	/**
	 * @since 28.0.0
	 */
	public function hasSummaryProviders(): bool;

	/**
	 * @param string $prompt The prompt to call the Language model with
	 * @returns string The output
	 * @throws PreConditionNotMetException If no provider was registered but this method was still called
	 * @throws InvalidArgumentException If the file could not be found or is not of a supported type
	 * @throws RuntimeException If the transcription failed for other reasons
	 * @since 28.0.0
	 */
	public function prompt(string $prompt): string;

	/**
	 * Will schedule an LLM inference process in the background. The result will become available
	 * with the \OCP\LanguageModel\Events\PromptFinishedEvent
	 *
	 * @param string $prompt The prompt to call the Language model with
	 * @param ?string $userId The user that triggered this request (only for convenience, will be available on the TranscriptEvents)
	 * @param string $appId The app that triggered this request (only for convenience, will be available on the TranscriptEvents)
	 * @returns int The id of the prompt request
	 * @throws PreConditionNotMetException If no provider was registered but this method was still called
	 * @since 28.0.0
	 */
	public function schedulePrompt(string $prompt, ?string $userId, string $appId): int;

	/**
	 * Will schedule an LLM inference process in the background. The result will become available
	 * with the \OCP\LanguageModel\Events\PromptFinishedEvent
	 *
	 * @param string $text The text to summarize
	 * @param ?string $userId The user that triggered this request (only for convenience, will be available on the TranscriptEvents)
	 * @param string $appId The app that triggered this request (only for convenience, will be available on the TranscriptEvents)
	 * @returns int The id of the prompt request
	 * @throws PreConditionNotMetException If no summary provider was registered but this method was still called
	 * @since 28.0.0
	 */
	public function scheduleSummary(string $text, ?string $userId, string $appId): int;

	/**
	 * @param string $text The text to summarize
	 * @returns string The output
	 * @throws PreConditionNotMetException If no summary provider was registered but this method was still called
	 * @throws InvalidArgumentException If the file could not be found or is not of a supported type
	 * @throws RuntimeException If the transcription failed for other reasons
	 * @since 28.0.0
	 */
	public function summarize(string $text): string;
}

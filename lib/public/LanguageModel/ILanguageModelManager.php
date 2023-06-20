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

use OCP\PreConditionNotMetException;
use RuntimeException;

/**
 * @since 28.0.0
 */
interface ILanguageModelManager {
	/**
	 * @since 28.0.0
	 */
	public function hasProviders(): bool;

	/**
	 * @return string[]
	 * @since 28.0.0
	 */
	public function getAvailableTasks(): array;

	/**
	 * @return string[]
	 * @since 28.0.0
	 */
	public function getAvailableTaskTypes(): array;

	/**
	 * @throws PreConditionNotMetException If no or not the requested provider was registered but this method was still called
	 * @throws RuntimeException If something else failed
	 * @since 28.0.0
	 */
	public function runTask(ILanguageModelTask $task): string;

	/**
	 * Will schedule an LLM inference process in the background. The result will become available
	 * with the \OCP\LanguageModel\Events\TaskFinishedEvent
	 *
	 * @throws PreConditionNotMetException If no or not the requested provider was registered but this method was still called
	 * @since 28.0.0
	 */
	public function scheduleTask(ILanguageModelTask $task) : void;

	/**
	 * @param int $id The id of the task
	 * @return ILanguageModelTask
	 * @throws RuntimeException If the query failed
	 * @throws \ValueError If the task could not be found
	 * @since 28.0.0
	 */
	public function getTask(int $id): ILanguageModelTask;
}

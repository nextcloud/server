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


namespace OCP\TextProcessing;

use OCP\Common\Exception\NotFoundException;
use OCP\DB\Exception;
use OCP\PreConditionNotMetException;
use OCP\TextProcessing\Exception\TaskFailureException;
use RuntimeException;

/**
 * API surface for apps interacting with and making use of LanguageModel providers
 * without known which providers are installed
 * @since 27.1.0
 */
interface IManager {
	/**
	 * @since 27.1.0
	 */
	public function hasProviders(): bool;

	/**
	 * @return IProvider[]
	 * @since 27.1.0
	 */
	public function getProviders(): array;

	/**
	 * @return class-string<ITaskType>[]
	 * @since 27.1.0
	 */
	public function getAvailableTaskTypes(): array;

	/**
	 * @param Task $task The task to run
	 * @throws PreConditionNotMetException If no or not the requested provider was registered but this method was still called
	 * @throws TaskFailureException If running the task failed
	 * @since 27.1.0
	 */
	public function runTask(Task $task): string;

	/**
	 * Will schedule an LLM inference process in the background. The result will become available
	 * with the \OCP\LanguageModel\Events\TaskSuccessfulEvent
	 * If inference fails a \OCP\LanguageModel\Events\TaskFailedEvent will be dispatched instead
	 *
	 * @param Task $task The task to schedule
	 * @throws PreConditionNotMetException If no or not the requested provider was registered but this method was still called
	 * @throws Exception storing the task in the database failed
	 * @since 27.1.0
	 */
	public function scheduleTask(Task $task) : void;

	/**
	 * If the designated provider for the passed task provides an expected average runtime, we check if the runtime fits into the
	 * max execution time of this php process and run it synchronously if it does, if it doesn't fit (or the provider doesn't provide that information)
	 * execution is deferred to a background job
	 *
	 * @param Task $task The task to schedule
	 * @returns bool A boolean indicating whether the task was run synchronously (`true`) or offloaded to a background job (`false`)
	 * @throws PreConditionNotMetException If no or not the requested provider was registered but this method was still called
	 * @throws TaskFailureException If running the task failed
	 * @throws Exception storing the task in the database failed
	 * @since 28.0.0
	 */
	public function runOrScheduleTask(Task $task): bool;

	/**
	 * Delete a task that has been scheduled before
	 *
	 * @param Task $task The task to delete
	 * @since 27.1.0
	 */
	public function deleteTask(Task $task): void;

	/**
	 * @param int $id The id of the task
	 * @return Task
	 * @throws RuntimeException If the query failed
	 * @throws NotFoundException If the task could not be found
	 * @since 27.1.0
	 */
	public function getTask(int $id): Task;

	/**
	 * @param int $id The id of the task
	 * @param string|null $userId The user id that scheduled the task
	 * @return Task
	 * @throws RuntimeException If the query failed
	 * @throws NotFoundException If the task could not be found
	 * @since 27.1.0
	 */
	public function getUserTask(int $id, ?string $userId): Task;

	/**
	 * @param string $userId
	 * @param string $appId
	 * @param string|null $identifier
	 * @return array
	 * @since 27.1.0
	 */
	public function getUserTasksByApp(string $userId, string $appId, ?string $identifier = null): array;
}

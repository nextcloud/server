<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\TaskProcessing;

use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\NotPermittedException;
use OCP\Lock\LockedException;
use OCP\TaskProcessing\Exception\Exception;
use OCP\TaskProcessing\Exception\NotFoundException;
use OCP\TaskProcessing\Exception\PreConditionNotMetException;
use OCP\TaskProcessing\Exception\UnauthorizedException;
use OCP\TaskProcessing\Exception\ValidationException;

/**
 * API surface for apps interacting with and making use of LanguageModel providers
 * without known which providers are installed
 * @since 30.0.0
 */
interface IManager {
	/**
	 * @since 30.0.0
	 */
	public function hasProviders(): bool;

	/**
	 * @return IProvider[]
	 * @since 30.0.0
	 */
	public function getProviders(): array;

	/**
	 * @param string $taskTypeId
	 * @return IProvider
	 * @throws Exception
	 * @since 30.0.0
	 */
	public function getPreferredProvider(string $taskTypeId);

	/**
	 * @param bool $showDisabled if false, disabled task types will be filtered
	 * @return array<string, array{name: string, description: string, inputShape: ShapeDescriptor[], inputShapeEnumValues: ShapeEnumValue[][], inputShapeDefaults: array<array-key, numeric|string>, optionalInputShape: ShapeDescriptor[], optionalInputShapeEnumValues: ShapeEnumValue[][], optionalInputShapeDefaults: array<array-key, numeric|string>, outputShape: ShapeDescriptor[], outputShapeEnumValues: ShapeEnumValue[][], optionalOutputShape: ShapeDescriptor[], optionalOutputShapeEnumValues: ShapeEnumValue[][]}>
	 * @since 30.0.0
	 * @since 31.0.0 Added the `showDisabled` argument.
	 */
	public function getAvailableTaskTypes(bool $showDisabled = false): array;

	/**
	 * @param Task $task The task to run
	 * @throws PreConditionNotMetException If no or not the requested provider was registered but this method was still called
	 * @throws ValidationException the given task input didn't pass validation against the task type's input shape and/or the providers optional input shape specs
	 * @throws Exception storing the task in the database failed
	 * @throws UnauthorizedException the user scheduling the task does not have access to the files used in the input
	 * @since 30.0.0
	 */
	public function scheduleTask(Task $task): void;

	/**
	 * Run the task and return the finished task
	 *
	 * @param Task $task The task to run
	 * @return Task The result task
	 * @throws PreConditionNotMetException If no or not the requested provider was registered but this method was still called
	 * @throws ValidationException the given task input didn't pass validation against the task type's input shape and/or the providers optional input shape specs
	 * @throws Exception storing the task in the database failed
	 * @throws UnauthorizedException the user scheduling the task does not have access to the files used in the input
	 * @since 30.0.0
	 */
	public function runTask(Task $task): Task;

	/**
	 * Process task with a synchronous provider
	 *
	 * Prepare task input data and run the process method of the provider
	 * This should only be used by OC\TaskProcessing\SynchronousBackgroundJob::run() and OCP\TaskProcessing\IManager::runTask()
	 *
	 * @param Task $task
	 * @param ISynchronousProvider $provider
	 * @return bool True if the task has run successfully
	 * @throws Exception
	 * @since 30.0.0
	 */
	public function processTask(Task $task, ISynchronousProvider $provider): bool;

	/**
	 * Delete a task that has been scheduled before
	 *
	 * @param Task $task The task to delete
	 * @throws Exception if deleting the task in the database failed
	 * @since 30.0.0
	 */
	public function deleteTask(Task $task): void;

	/**
	 * @param int $id The id of the task
	 * @return Task
	 * @throws Exception If the query failed
	 * @throws NotFoundException If the task could not be found
	 * @since 30.0.0
	 */
	public function getTask(int $id): Task;

	/**
	 * @param int $id The id of the task
	 * @throws Exception If the query failed
	 * @throws NotFoundException If the task could not be found
	 * @since 30.0.0
	 */
	public function cancelTask(int $id): void;

	/**
	 * @param int $id The id of the task
	 * @param string|null $error
	 * @param array|null $result
	 * @param bool $isUsingFileIds
	 * @throws Exception If the query failed
	 * @throws NotFoundException If the task could not be found
	 * @since 30.0.0
	 */
	public function setTaskResult(int $id, ?string $error, ?array $result, bool $isUsingFileIds = false): void;

	/**
	 * @param int $id
	 * @param float $progress
	 * @return bool `true` if the task should still be running; `false` if the task has been cancelled in the meantime
	 * @throws ValidationException
	 * @throws Exception
	 * @throws NotFoundException
	 * @since 30.0.0
	 */
	public function setTaskProgress(int $id, float $progress): bool;

	/**
	 * @param list<string> $taskTypeIds
	 * @param list<int> $taskIdsToIgnore
	 * @return Task
	 * @throws Exception If the query failed
	 * @throws NotFoundException If no task could not be found
	 * @since 30.0.0
	 */
	public function getNextScheduledTask(array $taskTypeIds = [], array $taskIdsToIgnore = []): Task;

	/**
	 * @param int $id The id of the task
	 * @param string|null $userId The user id that scheduled the task
	 * @return Task
	 * @throws Exception If the query failed
	 * @throws NotFoundException If the task could not be found
	 * @since 30.0.0
	 */
	public function getUserTask(int $id, ?string $userId): Task;

	/**
	 * @param string|null $userId The user id that scheduled the task
	 * @param string|null $taskTypeId The task type id to filter by
	 * @param string|null $customId
	 * @return list<Task>
	 * @throws Exception If the query failed
	 * @throws NotFoundException If the task could not be found
	 * @since 30.0.0
	 */
	public function getUserTasks(?string $userId, ?string $taskTypeId = null, ?string $customId = null): array;

	/**
	 * @param string|null $userId The user id that scheduled the task
	 * @param string|null $taskTypeId The task type id to filter by
	 * @param string|null $appId The app ID of the app that submitted the task
	 * @param string|null $customId The custom task ID
	 * @param int|null $status The task status
	 * @param int|null $scheduleAfter Minimum schedule time filter
	 * @param int|null $endedBefore Maximum ending time filter
	 * @return list<Task>
	 * @throws Exception If the query failed
	 * @throws NotFoundException If the task could not be found
	 * @since 30.0.0
	 */
	public function getTasks(
		?string $userId, ?string $taskTypeId = null, ?string $appId = null, ?string $customId = null,
		?int $status = null, ?int $scheduleAfter = null, ?int $endedBefore = null,
	): array;

	/**
	 * @param string|null $userId
	 * @param string $appId
	 * @param string|null $customId
	 * @return list<Task>
	 * @throws Exception If the query failed
	 * @throws \JsonException If parsing the task input and output failed
	 * @since 30.0.0
	 */
	public function getUserTasksByApp(?string $userId, string $appId, ?string $customId = null): array;

	/**
	 * Prepare the task's input data, so it can be processed by the provider
	 * ie. this replaces file ids with File objects
	 *
	 * @param Task $task
	 * @return array<array-key, list<numeric|string|File>|numeric|string|File>
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 * @throws LockedException
	 * @throws ValidationException
	 * @throws UnauthorizedException
	 * @since 30.0.0
	 */
	public function prepareInputData(Task $task): array;

	/**
	 * Changes the task status to STATUS_RUNNING and, if successful, returns True.
	 *
	 * @param Task $task
	 * @return bool
	 * @since 30.0.0
	 */
	public function lockTask(Task $task): bool;

	/**
	 * @param Task $task
	 * @psalm-param Task::STATUS_* $status
	 * @param int $status
	 * @throws \JsonException
	 * @throws Exception
	 * @since 30.0.0
	 */
	public function setTaskStatus(Task $task, int $status): void;
}

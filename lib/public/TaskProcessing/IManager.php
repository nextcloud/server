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
	 * @return array<string,array{name: string, description: string, inputShape: ShapeDescriptor[], optionalInputShape: ShapeDescriptor[], outputShape: ShapeDescriptor[], optionalOutputShape: ShapeDescriptor[]}>
	 * @since 30.0.0
	 */
	public function getAvailableTaskTypes(): array;

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
	 * @throws Exception If the query failed
	 * @throws NotFoundException If the task could not be found
	 * @since 30.0.0
	 */
	public function setTaskResult(int $id, ?string $error, ?array $result): void;

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
	 * @param string|null $taskTypeId
	 * @return Task
	 * @throws Exception If the query failed
	 * @throws NotFoundException If no task could not be found
	 * @since 30.0.0
	 */
	public function getNextScheduledTask(?string $taskTypeId = null): Task;

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
	 * ie. this replaces file ids with base64 data
	 *
	 * @param Task $task
	 * @return array<array-key, list<numeric|string|File>|numeric|string|File>
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 * @throws LockedException
	 * @throws ValidationException
	 * @since 30.0.0
	 */
	public function prepareInputData(Task $task): array;
}

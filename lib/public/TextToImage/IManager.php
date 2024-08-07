<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\TextToImage;

use OCP\DB\Exception;
use OCP\PreConditionNotMetException;
use OCP\TextToImage\Exception\TaskFailureException;
use OCP\TextToImage\Exception\TaskNotFoundException;
use RuntimeException;

/**
 * API surface for apps interacting with and making use of TextToImage providers
 * without knowing which providers are installed
 * @since 28.0.0
 * @deprecated 30.0.0
 */
interface IManager {
	/**
	 * @since 28.0.0
	 */
	public function hasProviders(): bool;

	/**
	 * @since 28.0.0
	 * @return list<IProvider>
	 */
	public function getProviders(): array;

	/**
	 * @param Task $task The task to run
	 * @throws PreConditionNotMetException If no or not the requested provider was registered but this method was still called
	 * @throws TaskFailureException If something else failed. When this is thrown task status was already set to failure.
	 * @since 28.0.0
	 */
	public function runTask(Task $task): void;

	/**
	 * Will schedule a TextToImage process in the background. The result will become available
	 * with the \OCP\TextToImage\TaskSuccessfulEvent
	 * If inference fails a \OCP\TextToImage\Events\TaskFailedEvent will be dispatched instead
	 *
	 * @param Task $task The task to schedule
	 * @throws PreConditionNotMetException If no provider was registered but this method was still called
	 * @throws Exception If there was a problem inserting the task into the database
	 * @since 28.0.0
	 */
	public function scheduleTask(Task $task) : void;

	/**
	 * @throws Exception if there was a problem inserting the task into the database
	 * @throws PreConditionNotMetException if no provider is registered
	 * @throws TaskFailureException If the task run failed
	 * @since 28.0.0
	 */
	public function runOrScheduleTask(Task $task) : void;

	/**
	 * Delete a task that has been scheduled before
	 *
	 * @param Task $task The task to delete
	 * @since 28.0.0
	 */
	public function deleteTask(Task $task): void;

	/**
	 * @param int $id The id of the task
	 * @return Task
	 * @throws RuntimeException If the query failed
	 * @throws TaskNotFoundException If the task could not be found
	 * @since 28.0.0
	 */
	public function getTask(int $id): Task;

	/**
	 * @param int $id The id of the task
	 * @param string|null $userId The user id that scheduled the task
	 * @return Task
	 * @throws RuntimeException If the query failed
	 * @throws TaskNotFoundException If the task could not be found
	 * @since 28.0.0
	 */
	public function getUserTask(int $id, ?string $userId): Task;

	/**
	 * @param ?string $userId
	 * @param string $appId
	 * @param string|null $identifier
	 * @return Task[]
	 * @since 28.0.0
	 * @throws RuntimeException If the query failed
	 */
	public function getUserTasksByApp(?string $userId, string $appId, ?string $identifier = null): array;
}

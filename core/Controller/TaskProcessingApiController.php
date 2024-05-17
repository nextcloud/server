<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OC\Core\Controller;

use OCA\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\ExAppRequired;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Lock\LockedException;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\Exception\Exception;
use OCP\TaskProcessing\Exception\NotFoundException;
use OCP\TaskProcessing\Exception\PreConditionNotMetException;
use OCP\TaskProcessing\Exception\UnauthorizedException;
use OCP\TaskProcessing\Exception\ValidationException;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\ShapeDescriptor;
use OCP\TaskProcessing\Task;
use RuntimeException;

/**
 * @psalm-import-type CoreTaskProcessingTask from ResponseDefinitions
 * @psalm-import-type CoreTaskProcessingTaskType from ResponseDefinitions
 */
class TaskProcessingApiController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string              $appName,
		IRequest            $request,
		private IManager    $taskProcessingManager,
		private IL10N       $l,
		private ?string     $userId,
		private IRootFolder $rootFolder,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Returns all available TaskProcessing task types
	 *
	 * @return DataResponse<Http::STATUS_OK, array{types: array<string, CoreTaskProcessingTaskType>}, array{}>
	 *
	 * 200: Task types returned
	 */
	#[PublicPage]
	#[ApiRoute(verb: 'GET', url: '/tasktypes', root: '/taskprocessing')]
	public function taskTypes(): DataResponse {
		$taskTypes = $this->taskProcessingManager->getAvailableTaskTypes();

		$serializedTaskTypes = [];
		foreach ($taskTypes as $key => $taskType) {
			$serializedTaskTypes[$key] = [
				'name' => $taskType['name'],
				'description' => $taskType['description'],
				'inputShape' => array_map(fn (ShapeDescriptor $descriptor) =>
					$descriptor->jsonSerialize() + ['mandatory' => true], $taskType['inputShape'])
					+ array_map(fn (ShapeDescriptor $descriptor) =>
					$descriptor->jsonSerialize() + ['mandatory' => false], $taskType['optionalInputShape']),
				'outputShape' => array_map(fn (ShapeDescriptor $descriptor) =>
					$descriptor->jsonSerialize() + ['mandatory' => true], $taskType['outputShape'])
					+ array_map(fn (ShapeDescriptor $descriptor) =>
					$descriptor->jsonSerialize() + ['mandatory' => false], $taskType['optionalOutputShape']),
			];
		}

		return new DataResponse([
			'types' => $serializedTaskTypes,
		]);
	}

	/**
	 * Schedules a task
	 *
	 * @param array<string, mixed> $input Task's input parameters
	 * @param string $type Type of the task
	 * @param string $appId ID of the app that will execute the task
	 * @param string $customId An arbitrary identifier for the task
	 *
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_BAD_REQUEST|Http::STATUS_PRECONDITION_FAILED|Http::STATUS_UNAUTHORIZED, array{message: string}, array{}>
	 *
	 * 200: Task scheduled successfully
	 * 400: Scheduling task is not possible
	 * 412: Scheduling task is not possible
	 * 401: Cannot schedule task because it references files in its input that the user doesn't have access to
	 */
	#[PublicPage]
	#[UserRateLimit(limit: 20, period: 120)]
	#[AnonRateLimit(limit: 5, period: 120)]
	#[ApiRoute(verb: 'POST', url: '/schedule', root: '/taskprocessing')]
	public function schedule(array $input, string $type, string $appId, string $customId = ''): DataResponse {
		$task = new Task($type, $input, $appId, $this->userId, $customId);
		try {
			$this->taskProcessingManager->scheduleTask($task);

			/** @var CoreTaskProcessingTask $json */
			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (PreConditionNotMetException) {
			return new DataResponse(['message' => $this->l->t('The given provider is not available')], Http::STATUS_PRECONDITION_FAILED);
		} catch (ValidationException $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		} catch (UnauthorizedException) {
			return new DataResponse(['message' => 'User does not have access to the files mentioned in the task input'], Http::STATUS_UNAUTHORIZED);
		} catch (Exception) {
			return new DataResponse(['message' => 'Internal server error'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Gets a task including status and result
	 *
	 * Tasks are removed 1 week after receiving their last update
	 *
	 * @param int $id The id of the task
	 *
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask}, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Task returned
	 * 404: Task not found
	 */
	#[PublicPage]
	#[ApiRoute(verb: 'GET', url: '/task/{id}', root: '/taskprocessing')]
	public function getTask(int $id): DataResponse {
		try {
			$task = $this->taskProcessingManager->getUserTask($id, $this->userId);

			/** @var CoreTaskProcessingTask $json */
			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (NotFoundException) {
			return new DataResponse(['message' => $this->l->t('Task not found')], Http::STATUS_NOT_FOUND);
		} catch (RuntimeException) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Deletes a task
	 *
	 * @param int $id The id of the task
	 *
	 * @return DataResponse<Http::STATUS_OK, null, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Task deleted
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/task/{id}', root: '/taskprocessing')]
	public function deleteTask(int $id): DataResponse {
		try {
			$task = $this->taskProcessingManager->getUserTask($id, $this->userId);

			$this->taskProcessingManager->deleteTask($task);

			return new DataResponse(null);
		} catch (NotFoundException) {
			return new DataResponse(null);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}


	/**
	 * Returns tasks for the current user filtered by the appId and optional customId
	 *
	 * @param string $appId ID of the app
	 * @param string|null $customId An arbitrary identifier for the task
	 * @return DataResponse<Http::STATUS_OK, array{tasks: CoreTaskProcessingTask[]}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 *  200: Tasks returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks/app/{appId}', root: '/taskprocessing')]
	public function listTasksByApp(string $appId, ?string $customId = null): DataResponse {
		try {
			$tasks = $this->taskProcessingManager->getUserTasksByApp($this->userId, $appId, $customId);
			/** @var CoreTaskProcessingTask[] $json */
			$json = array_map(static function (Task $task) {
				return $task->jsonSerialize();
			}, $tasks);

			return new DataResponse([
				'tasks' => $json,
			]);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Returns tasks for the current user filtered by the optional taskType and optional customId
	 *
	 * @param string|null $taskType The task type to filter by
	 * @param string|null $customId An arbitrary identifier for the task
	 * @return DataResponse<Http::STATUS_OK, array{tasks: CoreTaskProcessingTask[]}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 *  200: Tasks returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks', root: '/taskprocessing')]
	public function listTasks(?string $taskType, ?string $customId = null): DataResponse {
		try {
			$tasks = $this->taskProcessingManager->getUserTasks($this->userId, $taskType, $customId);
			/** @var CoreTaskProcessingTask[] $json */
			$json = array_map(static function (Task $task) {
				return $task->jsonSerialize();
			}, $tasks);

			return new DataResponse([
				'tasks' => $json,
			]);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Returns the contents of a file referenced in a task
	 *
	 * @param int $taskId The id of the task
	 * @param int $fileId The file id of the file to retrieve
	 * @return DataDownloadResponse<Http::STATUS_OK, string, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 *  200: File content returned
	 *  404: Task or file not found
	 */
	#[NoAdminRequired]
	#[Http\Attribute\NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks/{taskId}/file/{fileId}', root: '/taskprocessing')]
	public function getFileContents(int $taskId, int $fileId): Http\DataDownloadResponse|DataResponse {
		try {
			$task = $this->taskProcessingManager->getUserTask($taskId, $this->userId);
			return $this->getFileContentsInternal($task, $fileId);
		} catch (NotFoundException) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Returns the contents of a file referenced in a task(ExApp route version)
	 *
	 * @param int $taskId The id of the task
	 * @param int $fileId The file id of the file to retrieve
	 * @return DataDownloadResponse<Http::STATUS_OK, string, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 *  200: File content returned
	 *  404: Task or file not found
	 */
	#[ExAppRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks_provider/{taskId}/file/{fileId}', root: '/taskprocessing')]
	public function getFileContentsExApp(int $taskId, int $fileId): Http\DataDownloadResponse|DataResponse {
		try {
			$task = $this->taskProcessingManager->getTask($taskId);
			return $this->getFileContentsInternal($task, $fileId);
		} catch (NotFoundException) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 * @throws GenericFileException
	 * @throws LockedException
	 *
	 * @return DataDownloadResponse<Http::STATUS_OK, string, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 */
	private function getFileContentsInternal(Task $task, int $fileId): Http\DataDownloadResponse|DataResponse {
		$ids = $this->extractFileIdsFromTask($task);
		if (!in_array($fileId, $ids)) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		}
		$node = $this->rootFolder->getFirstNodeById($fileId);
		if ($node === null) {
			$node = $this->rootFolder->getFirstNodeByIdInPath($fileId, '/' . $this->rootFolder->getAppDataDirectoryName() . '/');
			if (!$node instanceof File) {
				throw new NotFoundException('Node is not a file');
			}
		} elseif (!$node instanceof File) {
			throw new NotFoundException('Node is not a file');
		}
		return new Http\DataDownloadResponse($node->getContent(), $node->getName(), $node->getMimeType());
	}

	/**
	 * @param Task $task
	 * @return list<int>
	 * @throws NotFoundException
	 */
	private function extractFileIdsFromTask(Task $task): array {
		$ids = [];
		$taskTypes = $this->taskProcessingManager->getAvailableTaskTypes();
		if (!isset($taskTypes[$task->getTaskTypeId()])) {
			throw new NotFoundException('Could not find task type');
		}
		$taskType = $taskTypes[$task->getTaskTypeId()];
		foreach ($taskType['inputShape'] + $taskType['optionalInputShape'] as $key => $descriptor) {
			if (in_array(EShapeType::getScalarType($descriptor->getShapeType()), [EShapeType::File, EShapeType::Image, EShapeType::Audio, EShapeType::Video], true)) {
				/** @var int|list<int> $inputSlot */
				$inputSlot = $task->getInput()[$key];
				if (is_array($inputSlot)) {
					$ids += $inputSlot;
				} else {
					$ids[] = $inputSlot;
				}
			}
		}
		if ($task->getOutput() !== null) {
			foreach ($taskType['outputShape'] + $taskType['optionalOutputShape'] as $key => $descriptor) {
				if (in_array(EShapeType::getScalarType($descriptor->getShapeType()), [EShapeType::File, EShapeType::Image, EShapeType::Audio, EShapeType::Video], true)) {
					/** @var int|list<int> $outputSlot */
					$outputSlot = $task->getOutput()[$key];
					if (is_array($outputSlot)) {
						$ids += $outputSlot;
					} else {
						$ids[] = $outputSlot;
					}
				}
			}
		}
		return array_values($ids);
	}

	/**
	 * Sets the task progress
	 *
	 * @param int $taskId The id of the task
	 * @param float $progress The progress
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 *  200: Progress updated successfully
	 *  404: Task not found
	 */
	#[ExAppRequired]
	#[ApiRoute(verb: 'POST', url: '/tasks_provider/{taskId}/progress', root: '/taskprocessing')]
	public function setProgress(int $taskId, float $progress): DataResponse {
		try {
			$this->taskProcessingManager->setTaskProgress($taskId, $progress);
			$task = $this->taskProcessingManager->getTask($taskId);

			/** @var CoreTaskProcessingTask $json */
			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (NotFoundException) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Sets the task result
	 *
	 * @param int $taskId The id of the task
	 * @param array<string,mixed>|null $output The resulting task output
	 * @param string|null $errorMessage An error message if the task failed
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 *  200: Result updated successfully
	 *  404: Task not found
	 */
	#[ExAppRequired]
	#[ApiRoute(verb: 'POST', url: '/tasks_provider/{taskId}/result', root: '/taskprocessing')]
	public function setResult(int $taskId, ?array $output = null, ?string $errorMessage = null): DataResponse {
		try {
			// set result
			$this->taskProcessingManager->setTaskResult($taskId, $errorMessage, $output);
			$task = $this->taskProcessingManager->getTask($taskId);

			/** @var CoreTaskProcessingTask $json */
			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (NotFoundException) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Cancels a task
	 *
	 * @param int $taskId The id of the task
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 *  200: Task canceled successfully
	 *  404: Task not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/tasks/{taskId}/cancel', root: '/taskprocessing')]
	public function cancelTask(int $taskId): DataResponse {
		try {
			// Check if the current user can access the task
			$this->taskProcessingManager->getUserTask($taskId, $this->userId);
			// set result
			$this->taskProcessingManager->cancelTask($taskId);
			$task = $this->taskProcessingManager->getUserTask($taskId, $this->userId);

			/** @var CoreTaskProcessingTask $json */
			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (NotFoundException) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Returns the next scheduled task for the taskTypeId
	 *
	 * @param list<string> $providerIds The ids of the providers
	 * @param list<string> $taskTypeIds The ids of the task types
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask, provider: array{name: string}}, array{}>|DataResponse<Http::STATUS_NO_CONTENT, null, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 *  200: Task returned
	 *  204: No task found
	 */
	#[ExAppRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks_provider/next', root: '/taskprocessing')]
	public function getNextScheduledTask(array $providerIds, array $taskTypeIds): DataResponse {
		try {
			// restrict $providerIds to providers that are configured as preferred for the passed task types
			$providerIds = array_values(array_intersect(array_unique(array_map(fn ($taskTypeId) => $this->taskProcessingManager->getPreferredProvider($taskTypeId)->getId(), $taskTypeIds)), $providerIds));
			// restrict $taskTypeIds to task types that can actually be run by one of the now restricted providers
			$taskTypeIds = array_values(array_filter($taskTypeIds, fn ($taskTypeId) => in_array($this->taskProcessingManager->getPreferredProvider($taskTypeId)->getId(), $providerIds, true)));
			if (count($providerIds) === 0 || count($taskTypeIds) === 0) {
				throw new NotFoundException();
			}

			$taskIdsToIgnore = [];
			while (true) {
				$task = $this->taskProcessingManager->getNextScheduledTask($taskTypeIds, $taskIdsToIgnore);
				$provider = $this->taskProcessingManager->getPreferredProvider($task->getTaskTypeId());
				if (in_array($provider->getId(), $providerIds, true)) {
					if ($this->taskProcessingManager->lockTask($task)) {
						break;
					}
				}
				$taskIdsToIgnore[] = (int)$task->getId();
			}

			/** @var CoreTaskProcessingTask $json */
			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
				'provider' => [
					'name' => $provider->getId(),
				],
			]);
		} catch (NotFoundException) {
			return new DataResponse(null, Http::STATUS_NO_CONTENT);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}

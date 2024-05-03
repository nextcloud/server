<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Marcel Klehr <mklehr@gmx.net>
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


namespace OC\Core\Controller;

use OCA\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\Common\Exception\NotFoundException;
use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Lock\LockedException;
use OCP\PreConditionNotMetException;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\Exception\Exception;
use OCP\TaskProcessing\Exception\ValidationException;
use OCP\TaskProcessing\ShapeDescriptor;
use OCP\TaskProcessing\Task;

/**
 * @psalm-import-type CoreTaskProcessingTask from ResponseDefinitions
 * @psalm-import-type CoreTaskProcessingTaskType from ResponseDefinitions
 */
class TaskProcessingApiController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private \OCP\TaskProcessing\IManager $taskProcessingManager,
		private IL10N $l,
		private ?string $userId,
		private IRootFolder $rootFolder,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * This endpoint returns all available TaskProcessing task types
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
				'inputShape' => array_map(fn (ShapeDescriptor $descriptor) => $descriptor->jsonSerialize(), $taskType['inputShape']),
				'optionalInputShape' => array_map(fn (ShapeDescriptor $descriptor) => $descriptor->jsonSerialize(), $taskType['optionalInputShape']),
				'outputShape' => array_map(fn (ShapeDescriptor $descriptor) => $descriptor->jsonSerialize(), $taskType['outputShape']),
				'optionalOutputShape' => array_map(fn (ShapeDescriptor $descriptor) => $descriptor->jsonSerialize(), $taskType['optionalOutputShape']),
			];
		}

		return new DataResponse([
			'types' => $serializedTaskTypes,
		]);
	}

	/**
	 * This endpoint allows scheduling a task
	 *
	 * @param array<string, mixed> $input Input text
	 * @param string $type Type of the task
	 * @param string $appId ID of the app that will execute the task
	 * @param string $identifier An arbitrary identifier for the task
	 *
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_BAD_REQUEST|Http::STATUS_PRECONDITION_FAILED, array{message: string}, array{}>
	 *
	 * 200: Task scheduled successfully
	 * 400: Scheduling task is not possible
	 * 412: Scheduling task is not possible
	 */
	#[PublicPage]
	#[UserRateLimit(limit: 20, period: 120)]
	#[AnonRateLimit(limit: 5, period: 120)]
	#[ApiRoute(verb: 'POST', url: '/schedule', root: '/taskprocessing')]
	public function schedule(array $input, string $type, string $appId, string $identifier = ''): DataResponse {
		$task = new Task($type, $input, $appId, $this->userId, $identifier);
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
		} catch (\OCP\TaskProcessing\Exception\Exception $e) {
			return new DataResponse(['message' => 'Internal server error'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * This endpoint allows checking the status and results of a task.
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
		} catch (NotFoundException $e) {
			return new DataResponse(['message' => $this->l->t('Task not found')], Http::STATUS_NOT_FOUND);
		} catch (\RuntimeException $e) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * This endpoint allows to delete a scheduled task for a user
	 *
	 * @param int $id The id of the task
	 *
	 * @return DataResponse<Http::STATUS_OK, array{}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Task returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/task/{id}', root: '/taskprocessing')]
	public function deleteTask(int $id): DataResponse {
		try {
			$task = $this->taskProcessingManager->getUserTask($id, $this->userId);

			$this->taskProcessingManager->deleteTask($task);

			return new DataResponse([]);
		} catch (\OCP\TaskProcessing\Exception\NotFoundException $e) {
			return new DataResponse([]);
		} catch (\OCP\TaskProcessing\Exception\Exception $e) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}


	/**
	 * This endpoint returns a list of tasks of a user that are related
	 * with a specific appId and optionally with an identifier
	 *
	 * @param string $appId ID of the app
	 * @param string|null $identifier An arbitrary identifier for the task
	 * @return DataResponse<Http::STATUS_OK, array{tasks: CoreTaskProcessingTask[]}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 *  200: Task list returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks/app/{appId}', root: '/taskprocessing')]
	public function listTasksByApp(string $appId, ?string $identifier = null): DataResponse {
		try {
			$tasks = $this->taskProcessingManager->getUserTasksByApp($this->userId, $appId, $identifier);
			/** @var CoreTaskProcessingTask[] $json */
			$json = array_map(static function (Task $task) {
				return $task->jsonSerialize();
			}, $tasks);

			return new DataResponse([
				'tasks' => $json,
			]);
		} catch (Exception $e) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\JsonException $e) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * This endpoint returns the contents of a file referenced in a task
	 *
	 * @param int $taskId The id of the task
	 * @param int $fileId The file id of the file to retrieve
	 * @return DataDownloadResponse<Http::STATUS_OK, string, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 *  200: File content returned
	 *  404: Task or file not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks/{taskId}/file/{fileId}', root: '/taskprocessing')]
	public function getFileContents(int $taskId, int $fileId): Http\DataDownloadResponse|DataResponse {
		try {
			$task = $this->taskProcessingManager->getUserTask($taskId, $this->userId);
			$ids = $this->extractFileIdsFromTask($task);
			if (!in_array($fileId, $ids)) {
				return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
			}
			$node = $this->rootFolder->getFirstNodeById($fileId);
			if ($node === null) {
				$node = $this->rootFolder->getFirstNodeByIdInPath($fileId, '/' . $this->rootFolder->getAppDataDirectoryName() . '/');
				if (!$node instanceof File) {
					throw new \OCP\TaskProcessing\Exception\NotFoundException('Node is not a file');
				}
			} elseif (!$node instanceof File) {
				throw new \OCP\TaskProcessing\Exception\NotFoundException('Node is not a file');
			}
			return new Http\DataDownloadResponse($node->getContent(), $node->getName(), $node->getMimeType());
		} catch (\OCP\TaskProcessing\Exception\NotFoundException $e) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		} catch (GenericFileException|NotPermittedException|LockedException|Exception $e) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @param Task $task
	 * @return list<mixed>
	 * @throws \OCP\TaskProcessing\Exception\NotFoundException
	 */
	private function extractFileIdsFromTask(Task $task) {
		$ids = [];
		$taskTypes = $this->taskProcessingManager->getAvailableTaskTypes();
		if (!isset($taskTypes[$task->getTaskTypeId()])) {
			throw new \OCP\TaskProcessing\Exception\NotFoundException('Could not find task type');
		}
		$taskType = $taskTypes[$task->getTaskTypeId()];
		foreach ($taskType['inputShape'] + $taskType['optionalInputShape'] as $key => $descriptor) {
			if (in_array(EShapeType::getScalarType($descriptor->getShapeType()), [EShapeType::File, EShapeType::Image, EShapeType::Audio, EShapeType::Video], true)) {
				$ids[] = $task->getInput()[$key];
			}
		}
		if ($task->getOutput() !== null) {
			foreach ($taskType['outputShape'] + $taskType['optionalOutputShape'] as $key => $descriptor) {
				if (in_array(EShapeType::getScalarType($descriptor->getShapeType()), [EShapeType::File, EShapeType::Image, EShapeType::Audio, EShapeType::Video], true)) {
					$ids[] = $task->getOutput()[$key];
				}
			}
		}
		return $ids;
	}

	/**
	 * This endpoint sets the task progress
	 *
	 * @param int $taskId The id of the task
	 * @param float $progress The progress
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 *  200: File content returned
	 *  404: Task not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/tasks/{taskId}/progress', root: '/taskprocessing')]
	public function setProgress(int $taskId, float $progress): DataResponse {
		try {
			$this->taskProcessingManager->setTaskProgress($taskId, $progress);
			$task = $this->taskProcessingManager->getUserTask($taskId, $this->userId);

			/** @var CoreTaskProcessingTask $json */
			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (\OCP\TaskProcessing\Exception\NotFoundException $e) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * This endpoint sets the task progress
	 *
	 * @param int $taskId The id of the task
	 * @param array<string,mixed>|null $output The resulting task output
	 * @param string|null $errorMessage An error message if the task failed
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 *  200: File content returned
	 *  404: Task not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/tasks/{taskId}/result', root: '/taskprocessing')]
	public function setResult(int $taskId, ?array $output = null, ?string $errorMessage = null): DataResponse {
		try {
			$this->taskProcessingManager->getUserTask($taskId, $this->userId);
			$this->taskProcessingManager->setTaskResult($taskId, $errorMessage, $output);
			$task = $this->taskProcessingManager->getUserTask($taskId, $this->userId);

			/** @var CoreTaskProcessingTask $json */
			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (\OCP\TaskProcessing\Exception\NotFoundException $e) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}

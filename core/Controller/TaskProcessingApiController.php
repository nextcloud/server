<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OC\Core\Controller;

use OC\Core\ResponseDefinitions;
use OC\Files\SimpleFS\SimpleFile;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\ExAppRequired;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\OCSController;
use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\Files\IMimeTypeDetector;
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
use OCP\TaskProcessing\ShapeEnumValue;
use OCP\TaskProcessing\Task;
use RuntimeException;
use stdClass;

/**
 * @psalm-import-type CoreTaskProcessingTask from ResponseDefinitions
 * @psalm-import-type CoreTaskProcessingTaskType from ResponseDefinitions
 */
class TaskProcessingApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IManager $taskProcessingManager,
		private IL10N $l,
		private ?string $userId,
		private IRootFolder $rootFolder,
		private IAppData $appData,
		private IMimeTypeDetector $mimeTypeDetector,
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
		/** @var array<string, CoreTaskProcessingTaskType> $taskTypes */
		$taskTypes = array_map(function (array $tt) {
			$tt['inputShape'] = array_map(function ($descriptor) {
				return $descriptor->jsonSerialize();
			}, $tt['inputShape']);
			if (empty($tt['inputShape'])) {
				$tt['inputShape'] = new stdClass;
			}

			$tt['outputShape'] = array_map(function ($descriptor) {
				return $descriptor->jsonSerialize();
			}, $tt['outputShape']);
			if (empty($tt['outputShape'])) {
				$tt['outputShape'] = new stdClass;
			}

			$tt['optionalInputShape'] = array_map(function ($descriptor) {
				return $descriptor->jsonSerialize();
			}, $tt['optionalInputShape']);
			if (empty($tt['optionalInputShape'])) {
				$tt['optionalInputShape'] = new stdClass;
			}

			$tt['optionalOutputShape'] = array_map(function ($descriptor) {
				return $descriptor->jsonSerialize();
			}, $tt['optionalOutputShape']);
			if (empty($tt['optionalOutputShape'])) {
				$tt['optionalOutputShape'] = new stdClass;
			}

			$tt['inputShapeEnumValues'] = array_map(function (array $enumValues) {
				return array_map(fn (ShapeEnumValue $enumValue) => $enumValue->jsonSerialize(), $enumValues);
			}, $tt['inputShapeEnumValues']);
			if (empty($tt['inputShapeEnumValues'])) {
				$tt['inputShapeEnumValues'] = new stdClass;
			}

			$tt['optionalInputShapeEnumValues'] = array_map(function (array $enumValues) {
				return array_map(fn (ShapeEnumValue $enumValue) => $enumValue->jsonSerialize(), $enumValues);
			}, $tt['optionalInputShapeEnumValues']);
			if (empty($tt['optionalInputShapeEnumValues'])) {
				$tt['optionalInputShapeEnumValues'] = new stdClass;
			}

			$tt['outputShapeEnumValues'] = array_map(function (array $enumValues) {
				return array_map(fn (ShapeEnumValue $enumValue) => $enumValue->jsonSerialize(), $enumValues);
			}, $tt['outputShapeEnumValues']);
			if (empty($tt['outputShapeEnumValues'])) {
				$tt['outputShapeEnumValues'] = new stdClass;
			}

			$tt['optionalOutputShapeEnumValues'] = array_map(function (array $enumValues) {
				return array_map(fn (ShapeEnumValue $enumValue) => $enumValue->jsonSerialize(), $enumValues);
			}, $tt['optionalOutputShapeEnumValues']);
			if (empty($tt['optionalOutputShapeEnumValues'])) {
				$tt['optionalOutputShapeEnumValues'] = new stdClass;
			}

			if (empty($tt['inputShapeDefaults'])) {
				$tt['inputShapeDefaults'] = new stdClass;
			}
			if (empty($tt['optionalInputShapeDefaults'])) {
				$tt['optionalInputShapeDefaults'] = new stdClass;
			}
			return $tt;
		}, $this->taskProcessingManager->getAvailableTaskTypes());
		return new DataResponse([
			'types' => $taskTypes,
		]);
	}

	/**
	 * Schedules a task
	 *
	 * @param array<string, mixed> $input Task's input parameters
	 * @param string $type Type of the task
	 * @param string $appId ID of the app that will execute the task
	 * @param string $customId An arbitrary identifier for the task
	 * @param string|null $webhookUri URI to be requested when the task finishes
	 * @param string|null $webhookMethod Method used for the webhook request (HTTP:GET, HTTP:POST, HTTP:PUT, HTTP:DELETE or AppAPI:APP_ID:GET, AppAPI:APP_ID:POST...)
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
	public function schedule(
		array $input, string $type, string $appId, string $customId = '',
		?string $webhookUri = null, ?string $webhookMethod = null,
	): DataResponse {
		$task = new Task($type, $input, $appId, $this->userId, $customId);
		$task->setWebhookUri($webhookUri);
		$task->setWebhookMethod($webhookMethod);
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
	 * @return DataResponse<Http::STATUS_OK, array{tasks: list<CoreTaskProcessingTask>}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Tasks returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks/app/{appId}', root: '/taskprocessing')]
	public function listTasksByApp(string $appId, ?string $customId = null): DataResponse {
		try {
			$tasks = $this->taskProcessingManager->getUserTasksByApp($this->userId, $appId, $customId);
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
	 * @return DataResponse<Http::STATUS_OK, array{tasks: list<CoreTaskProcessingTask>}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Tasks returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks', root: '/taskprocessing')]
	public function listTasks(?string $taskType, ?string $customId = null): DataResponse {
		try {
			$tasks = $this->taskProcessingManager->getUserTasks($this->userId, $taskType, $customId);
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
	 * @return StreamResponse<Http::STATUS_OK, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 * 200: File content returned
	 * 404: Task or file not found
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks/{taskId}/file/{fileId}', root: '/taskprocessing')]
	public function getFileContents(int $taskId, int $fileId): StreamResponse|DataResponse {
		try {
			$task = $this->taskProcessingManager->getUserTask($taskId, $this->userId);
			return $this->getFileContentsInternal($task, $fileId);
		} catch (NotFoundException) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		} catch (LockedException) {
			return new DataResponse(['message' => $this->l->t('Node is locked')], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Returns the contents of a file referenced in a task(ExApp route version)
	 *
	 * @param int $taskId The id of the task
	 * @param int $fileId The file id of the file to retrieve
	 * @return StreamResponse<Http::STATUS_OK, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 * 200: File content returned
	 * 404: Task or file not found
	 */
	#[ExAppRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks_provider/{taskId}/file/{fileId}', root: '/taskprocessing')]
	public function getFileContentsExApp(int $taskId, int $fileId): StreamResponse|DataResponse {
		try {
			$task = $this->taskProcessingManager->getTask($taskId);
			return $this->getFileContentsInternal($task, $fileId);
		} catch (NotFoundException) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		} catch (LockedException) {
			return new DataResponse(['message' => $this->l->t('Node is locked')], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Upload a file so it can be referenced in a task result (ExApp route version)
	 *
	 * Use field 'file' for the file upload
	 *
	 * @param int $taskId The id of the task
	 * @return DataResponse<Http::STATUS_CREATED, array{fileId: int}, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 * 201: File created
	 * 400: File upload failed or no file was uploaded
	 * 404: Task not found
	 */
	#[ExAppRequired]
	#[ApiRoute(verb: 'POST', url: '/tasks_provider/{taskId}/file', root: '/taskprocessing')]
	public function setFileContentsExApp(int $taskId): DataResponse {
		try {
			$task = $this->taskProcessingManager->getTask($taskId);
			$file = $this->request->getUploadedFile('file');
			if (!isset($file['tmp_name'])) {
				return new DataResponse(['message' => $this->l->t('Bad request')], Http::STATUS_BAD_REQUEST);
			}
			$handle = fopen($file['tmp_name'], 'r');
			if (!$handle) {
				return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
			}
			$fileId = $this->setFileContentsInternal($handle);
			return new DataResponse(['fileId' => $fileId], Http::STATUS_CREATED);
		} catch (NotFoundException) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 * @throws LockedException
	 *
	 * @return StreamResponse<Http::STATUS_OK, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 */
	private function getFileContentsInternal(Task $task, int $fileId): StreamResponse|DataResponse {
		$ids = $this->extractFileIdsFromTask($task);
		if (!in_array($fileId, $ids)) {
			return new DataResponse(['message' => $this->l->t('Not found')], Http::STATUS_NOT_FOUND);
		}
		if ($task->getUserId() !== null) {
			\OC_Util::setupFS($task->getUserId());
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

		$contentType = $node->getMimeType();
		if (function_exists('mime_content_type')) {
			$mimeType = mime_content_type($node->fopen('rb'));
			if ($mimeType !== false) {
				$mimeType = $this->mimeTypeDetector->getSecureMimeType($mimeType);
				if ($mimeType !== 'application/octet-stream') {
					$contentType = $mimeType;
				}
			}
		}

		$response = new StreamResponse($node->fopen('rb'));
		$response->addHeader(
			'Content-Disposition',
			'attachment; filename="' . rawurldecode($node->getName()) . '"'
		);
		$response->addHeader('Content-Type', $contentType);
		return $response;
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
					$ids = array_merge($inputSlot, $ids);
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
						$ids = array_merge($outputSlot, $ids);
					} else {
						$ids[] = $outputSlot;
					}
				}
			}
		}
		return $ids;
	}

	/**
	 * Sets the task progress
	 *
	 * @param int $taskId The id of the task
	 * @param float $progress The progress
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 * 200: Progress updated successfully
	 * 404: Task not found
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
	 * @param array<string,mixed>|null $output The resulting task output, files are represented by their IDs
	 * @param string|null $errorMessage An error message if the task failed
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 * 200: Result updated successfully
	 * 404: Task not found
	 */
	#[ExAppRequired]
	#[ApiRoute(verb: 'POST', url: '/tasks_provider/{taskId}/result', root: '/taskprocessing')]
	public function setResult(int $taskId, ?array $output = null, ?string $errorMessage = null): DataResponse {
		try {
			// set result
			$this->taskProcessingManager->setTaskResult($taskId, $errorMessage, $output, true);
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
	 * 200: Task canceled successfully
	 * 404: Task not found
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
	 * 200: Task returned
	 * 204: No task found
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

	/**
	 * @param resource $data
	 * @return int
	 * @throws NotPermittedException
	 */
	private function setFileContentsInternal($data): int {
		try {
			$folder = $this->appData->getFolder('TaskProcessing');
		} catch (\OCP\Files\NotFoundException) {
			$folder = $this->appData->newFolder('TaskProcessing');
		}
		/** @var SimpleFile $file */
		$file = $folder->newFile(time() . '-' . rand(1, 100000), $data);
		return $file->getId();
	}
}

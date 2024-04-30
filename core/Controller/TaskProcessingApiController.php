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
use OCP\AppFramework\Http\DataResponse;
use OCP\Common\Exception\NotFoundException;
use OCP\IL10N;
use OCP\IRequest;
use OCP\PreConditionNotMetException;
use OCP\TaskProcessing\Exception\ValidationException;
use OCP\TaskProcessing\ShapeDescriptor;
use OCP\TaskProcessing\Task;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type CoreTaskProcessingTask from ResponseDefinitions
 * @psalm-import-type CoreTaskProcessingTaskType from ResponseDefinitions
 */
class TaskProcessingApiController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string                     $appName,
		IRequest                   $request,
		private \OCP\TaskProcessing\IManager           $taskProcessingManager,
		private IL10N              $l,
		private ?string            $userId,
		private ContainerInterface $container,
		private LoggerInterface    $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * This endpoint returns all available TaskProcessing task types
	 *
	 * @return DataResponse<Http::STATUS_OK, array{types: array<string, CoreTaskProcessingTaskType>}>
	 * []}, array{}>
	 *
	 * 200: Task types returned
	 */
	#[PublicPage]
	#[ApiRoute(verb: 'GET', url: '/tasktypes', root: '/taskprocessing')]
	public function taskTypes(): DataResponse {
		$taskTypes = $this->taskProcessingManager->getAvailableTaskTypes();

		/** @var string $typeClass */
		foreach ($taskTypes as $taskType) {
			$taskType['inputShape'] = array_map(fn (ShapeDescriptor $descriptor) => $descriptor->jsonSerialize(), $taskType['inputShape']);
			$taskType['optionalInputShape'] = array_map(fn (ShapeDescriptor $descriptor) => $descriptor->jsonSerialize(), $taskType['optionalInputShape']);
			$taskType['outputShape'] = array_map(fn (ShapeDescriptor $descriptor) => $descriptor->jsonSerialize(), $taskType['outputShape']);
			$taskType['optionalOutputShape'] = array_map(fn (ShapeDescriptor $descriptor) => $descriptor->jsonSerialize(), $taskType['optionalOutputShape']);
		}

		return new DataResponse([
			'types' => $taskTypes,
		]);
	}

	/**
	 * This endpoint allows scheduling a task
	 *
	 * @param string $input Input text
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
	 * Tasks are removed 1 week after receiving their last update.
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
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTaskProcessingTask}, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Task returned
	 * 404: Task not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/task/{id}', root: '/taskprocessing')]
	public function deleteTask(int $id): DataResponse {
		try {
			$task = $this->taskProcessingManager->getUserTask($id, $this->userId);

			$this->taskProcessingManager->deleteTask($task);

			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (\OCP\TaskProcessing\Exception\NotFoundException $e) {
			return new DataResponse(['message' => $this->l->t('Task not found')], Http::STATUS_NOT_FOUND);
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
		} catch (\RuntimeException $e) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}

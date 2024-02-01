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


namespace OC\Core\Controller;

use InvalidArgumentException;
use OCA\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\Common\Exception\NotFoundException;
use OCP\DB\Exception;
use OCP\IL10N;
use OCP\IRequest;
use OCP\PreConditionNotMetException;
use OCP\TextProcessing\Exception\TaskFailureException;
use OCP\TextProcessing\IManager;
use OCP\TextProcessing\ITaskType;
use OCP\TextProcessing\Task;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type CoreTextProcessingTask from ResponseDefinitions
 */
class TextProcessingApiController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string                     $appName,
		IRequest                   $request,
		private IManager           $textProcessingManager,
		private IL10N              $l,
		private ?string            $userId,
		private ContainerInterface $container,
		private LoggerInterface    $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * This endpoint returns all available LanguageModel task types
	 *
	 * @return DataResponse<Http::STATUS_OK, array{types: array{id: string, name: string, description: string}[]}, array{}>
	 *
	 * 200: Task types returned
	 */
	#[PublicPage]
	public function taskTypes(): DataResponse {
		$typeClasses = $this->textProcessingManager->getAvailableTaskTypes();
		$types = [];
		/** @var string $typeClass */
		foreach ($typeClasses as $typeClass) {
			try {
				/** @var ITaskType $object */
				$object = $this->container->get($typeClass);
			} catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
				$this->logger->warning('Could not find ' . $typeClass, ['exception' => $e]);
				continue;
			}
			$types[] = [
				'id' => $typeClass,
				'name' => $object->getName(),
				'description' => $object->getDescription(),
			];
		}

		return new DataResponse([
			'types' => $types,
		]);
	}

	/**
	 * This endpoint allows scheduling a language model task
	 *
	 * @param string $input Input text
	 * @param string $type Type of the task
	 * @param string $appId ID of the app that will execute the task
	 * @param string $identifier An arbitrary identifier for the task
	 *
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTextProcessingTask}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_BAD_REQUEST|Http::STATUS_PRECONDITION_FAILED, array{message: string}, array{}>
	 *
	 * 200: Task scheduled successfully
	 * 400: Scheduling task is not possible
	 * 412: Scheduling task is not possible
	 */
	#[PublicPage]
	#[UserRateLimit(limit: 20, period: 120)]
	#[AnonRateLimit(limit: 5, period: 120)]
	public function schedule(string $input, string $type, string $appId, string $identifier = ''): DataResponse {
		try {
			$task = new Task($type, $input, $appId, $this->userId, $identifier);
		} catch (InvalidArgumentException) {
			return new DataResponse(['message' => $this->l->t('Requested task type does not exist')], Http::STATUS_BAD_REQUEST);
		}
		try {
			try {
				$this->textProcessingManager->runOrScheduleTask($task);
			} catch(TaskFailureException) {
				// noop, because the task object has the failure status set already, we just return the task json
			}

			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (PreConditionNotMetException) {
			return new DataResponse(['message' => $this->l->t('Necessary language model provider is not available')], Http::STATUS_PRECONDITION_FAILED);
		} catch (Exception) {
			return new DataResponse(['message' => 'Internal server error'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * This endpoint allows checking the status and results of a task.
	 * Tasks are removed 1 week after receiving their last update.
	 *
	 * @param int $id The id of the task
	 *
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTextProcessingTask}, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Task returned
	 * 404: Task not found
	 */
	#[PublicPage]
	public function getTask(int $id): DataResponse {
		try {
			$task = $this->textProcessingManager->getUserTask($id, $this->userId);

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
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTextProcessingTask}, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Task returned
	 * 404: Task not found
	 */
	#[NoAdminRequired]
	public function deleteTask(int $id): DataResponse {
		try {
			$task = $this->textProcessingManager->getUserTask($id, $this->userId);

			$this->textProcessingManager->deleteTask($task);

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
	 * This endpoint returns a list of tasks of a user that are related
	 * with a specific appId and optionally with an identifier
	 *
	 * @param string $appId ID of the app
	 * @param string|null $identifier An arbitrary identifier for the task
	 * @return DataResponse<Http::STATUS_OK, array{tasks: CoreTextProcessingTask[]}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 *  200: Task list returned
	 */
	#[NoAdminRequired]
	public function listTasksByApp(string $appId, ?string $identifier = null): DataResponse {
		try {
			$tasks = $this->textProcessingManager->getUserTasksByApp($this->userId, $appId, $identifier);
			/** @var CoreTextProcessingTask[] $json */
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

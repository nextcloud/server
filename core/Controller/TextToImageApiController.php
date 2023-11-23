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

use OC\Files\AppData\AppData;
use OCA\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\DB\Exception;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\IRequest;
use OCP\PreConditionNotMetException;
use OCP\TextToImage\Exception\TaskFailureException;
use OCP\TextToImage\Exception\TaskNotFoundException;
use OCP\TextToImage\IManager;
use OCP\TextToImage\Task;

/**
 * @psalm-import-type CoreTextToImageTask from ResponseDefinitions
 */
class TextToImageApiController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IManager $textToImageManager,
		private IL10N $l,
		private ?string $userId,
		private AppData $appData,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Check whether this feature is available
	 *
	 * @return DataResponse<Http::STATUS_OK, array{isAvailable: bool}, array{}>
	 *
	 * 200: Returns availability status
	 */
	#[PublicPage]
	public function isAvailable(): DataResponse {
		return new DataResponse([
			'isAvailable' => $this->textToImageManager->hasProviders(),
		]);
	}

	/**
	 * This endpoint allows scheduling a text to image task
	 *
	 * @param string $input Input text
	 * @param string $appId ID of the app that will execute the task
	 * @param string $identifier An arbitrary identifier for the task
	 * @param int $numberOfImages The number of images to generate
	 *
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTextToImageTask}, array{}>|DataResponse<Http::STATUS_PRECONDITION_FAILED|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Task scheduled successfully
	 * 412: Scheduling task is not possible
	 */
	#[PublicPage]
	#[UserRateLimit(limit: 20, period: 120)]
	#[AnonRateLimit(limit: 5, period: 120)]
	public function schedule(string $input, string $appId, string $identifier = '', int $numberOfImages = 8): DataResponse {
		$task = new Task($input, $appId, $numberOfImages, $this->userId, $identifier);
		try {
			try {
				$this->textToImageManager->runOrScheduleTask($task);
			} catch (TaskFailureException) {
				// Task status was already updated by the manager, nothing to do here
			}

			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (PreConditionNotMetException) {
			return new DataResponse(['message' => $this->l->t('No text to image provider is available')], Http::STATUS_PRECONDITION_FAILED);
		} catch (Exception) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * This endpoint allows checking the status and results of a task.
	 * Tasks are removed 1 week after receiving their last update.
	 *
	 * @param int $id The id of the task
	 *
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTextToImageTask}, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Task returned
	 * 404: Task not found
	 */
	#[PublicPage]
	#[BruteForceProtection(action: 'text2image')]
	public function getTask(int $id): DataResponse {
		try {
			$task = $this->textToImageManager->getUserTask($id, $this->userId);

			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (TaskNotFoundException) {
			$res = new DataResponse(['message' => $this->l->t('Task not found')], Http::STATUS_NOT_FOUND);
			$res->throttle(['action' => 'text2image']);
			return $res;
		} catch (\RuntimeException) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * This endpoint allows downloading the resulting image of a task
	 *
	 * @param int $id The id of the task
	 * @param int $index The index of the image to retrieve
	 *
	 * @return FileDisplayResponse<Http::STATUS_OK, array{'Content-Type': string}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Image returned
	 * 404: Task or image not found
	 */
	#[PublicPage]
	#[BruteForceProtection(action: 'text2image')]
	public function getImage(int $id, int $index): DataResponse|FileDisplayResponse {
		try {
			$task = $this->textToImageManager->getUserTask($id, $this->userId);
			try {
				$folder = $this->appData->getFolder('text2image');
			} catch(NotFoundException) {
				$res = new DataResponse(['message' => $this->l->t('Image not found')], Http::STATUS_NOT_FOUND);
				$res->throttle(['action' => 'text2image']);
				return $res;
			}
			$file = $folder->getFolder((string) $task->getId())->getFile((string) $index);
			$info = getimagesizefromstring($file->getContent());

			return new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => image_type_to_mime_type($info[2])]);
		} catch (TaskNotFoundException) {
			$res = new DataResponse(['message' => $this->l->t('Task not found')], Http::STATUS_NOT_FOUND);
			$res->throttle(['action' => 'text2image']);
			return $res;
		} catch (\RuntimeException) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (NotFoundException) {
			$res = new DataResponse(['message' => $this->l->t('Image not found')], Http::STATUS_NOT_FOUND);
			$res->throttle(['action' => 'text2image']);
			return $res;
		}
	}

	/**
	 * This endpoint allows to delete a scheduled task for a user
	 *
	 * @param int $id The id of the task
	 *
	 * @return DataResponse<Http::STATUS_OK, array{task: CoreTextToImageTask}, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Task returned
	 * 404: Task not found
	 */
	#[NoAdminRequired]
	#[BruteForceProtection(action: 'text2image')]
	public function deleteTask(int $id): DataResponse {
		try {
			$task = $this->textToImageManager->getUserTask($id, $this->userId);

			$this->textToImageManager->deleteTask($task);

			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
			]);
		} catch (TaskNotFoundException) {
			$res = new DataResponse(['message' => $this->l->t('Task not found')], Http::STATUS_NOT_FOUND);
			$res->throttle(['action' => 'text2image']);
			return $res;
		} catch (\RuntimeException) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}


	/**
	 * This endpoint returns a list of tasks of a user that are related
	 * with a specific appId and optionally with an identifier
	 *
	 * @param string $appId ID of the app
	 * @param string|null $identifier An arbitrary identifier for the task
	 * @return DataResponse<Http::STATUS_OK, array{tasks: CoreTextToImageTask[]}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 *  200: Task list returned
	 */
	#[NoAdminRequired]
	#[AnonRateLimit(limit: 5, period: 120)]
	public function listTasksByApp(string $appId, ?string $identifier = null): DataResponse {
		try {
			$tasks = $this->textToImageManager->getUserTasksByApp($this->userId, $appId, $identifier);
			/** @var CoreTextToImageTask[] $json */
			$json = array_map(static function (Task $task) {
				return $task->jsonSerialize();
			}, $tasks);

			return new DataResponse([
				'tasks' => $json,
			]);
		} catch (\RuntimeException) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}

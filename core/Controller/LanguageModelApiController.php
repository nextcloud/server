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
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Common\Exception\NotFoundException;
use OCP\IL10N;
use OCP\IRequest;
use OCP\LanguageModel\AbstractLanguageModelTask;
use OCP\LanguageModel\ILanguageModelManager;
use OCP\PreConditionNotMetException;

class LanguageModelApiController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ILanguageModelManager $languageModelManager,
		private IL10N $l,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * This endpoint returns all available LanguageModel task types
	 *
	 * @PublicPage
	 * @return DataResponse<Http::STATUS_OK, array{types: string[]}, array{}>
	 *
	 * 200: Task types returned
	 */
	public function taskTypes(): DataResponse {
		return new DataResponse([
			'types' => $this->languageModelManager->getAvailableTaskTypes(),
		]);
	}

	/**
	 * This endpoint allows scheduling a language model task
	 *
	 * @PublicPage
	 * @UserRateThrottle(limit=20, period=120)
	 * @AnonRateThrottle(limit=5, period=120)
	 * @param string $input The input for the language model task
	 * @param string $type The task type
	 * @param string $appId The originating app ID
	 * @param string $identifier An identifier to identify this task
	 * @return DataResponse<Http::STATUS_OK, array{task: array{id: int, type: string, status: int, userId: string, appId: string, input: string, output: string, identifier: string}}, array{}>| DataResponse<Http::STATUS_PRECONDITION_FAILED|Http::STATUS_BAD_REQUEST, array{message: string}, array{}>
	 *
	 * 200: Task scheduled
	 * 400: Task type does not exist
	 * 412: Task type not available
	 */
	public function schedule(string $input, string $type, string $appId, string $identifier = ''): DataResponse {
		try {
			$task = AbstractLanguageModelTask::factory($type, $input, $this->userId, $appId, $identifier);
		} catch (InvalidArgumentException $e) {
			return new DataResponse(['message' => $this->l->t('Requested task type does not exist')], Http::STATUS_BAD_REQUEST);
		}
		try {
			$this->languageModelManager->scheduleTask($task);

			return new DataResponse([
				'task' => $task->jsonSerialize(),
			]);
		} catch (PreConditionNotMetException) {
			return new DataResponse(['message' => $this->l->t('Necessary language model provider is not available')], Http::STATUS_PRECONDITION_FAILED);
		}
	}

	/**
	 * This endpoint allows checking the status and results of a task.
	 * Tasks are removed 1 week after receiving their last update.
	 *
	 * @PublicPage
	 * @param int $id The id of the task
	 * @return DataResponse<Http::STATUS_NOT_FOUND | Http::STATUS_INTERNAL_SERVER_ERROR, array{message:string}> | DataResponse<Http::STATUS_OK, array{task: array{id: int, type: string, status: int, userId: string, appId: string, input: string, output: string, identifier: string}}, array{}>
	 *
	 * 200: Task returned
	 * 404: Task not found
	 * 500: Internal error
	 */
	public function getTask(int $id): DataResponse {
		try {
			$task = $this->languageModelManager->getTask($id);

			if ($this->userId !== $task->getUserId()) {
				return new DataResponse(['message' => $this->l->t('Task not found')], Http::STATUS_NOT_FOUND);
			}

			return new DataResponse([
				'task' => $task->jsonSerialize(),
			]);
		} catch (NotFoundException $e) {
			return new DataResponse(['message' => $this->l->t('Task not found')], Http::STATUS_NOT_FOUND);
		} catch (\RuntimeException $e) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}

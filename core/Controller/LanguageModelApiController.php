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
	 * @PublicPage
	 */
	public function tasks(): DataResponse {
		return new DataResponse([
			'tasks' => $this->languageModelManager->getAvailableTaskTypes(),
		]);
	}

	/**
	 * @PublicPage
	 * @UserRateThrottle(limit=20, period=120)
	 * @AnonRateThrottle(limit=5, period=120)
	 */
	public function schedule(string $text, string $type, ?string $appId): DataResponse {
		try {
			$task = AbstractLanguageModelTask::factory($type, $text, $this->userId, $appId);
		} catch (InvalidArgumentException $e) {
			return new DataResponse(['message' => $this->l->t('Requested task type does not exist')], Http::STATUS_BAD_REQUEST);
		}
		try {
			$this->languageModelManager->scheduleTask($task);

			return new DataResponse([
				'task' => $task,
			]);
		} catch (PreConditionNotMetException) {
			return new DataResponse(['message' => $this->l->t('Necessary language model provider is not available')], Http::STATUS_PRECONDITION_FAILED);
		}
	}

	/**
	 * @PublicPage
	 */
	public function getTask(int $id): DataResponse {
		try {
			$task = $this->languageModelManager->getTask($id);

			return new DataResponse([
				'task' => $task,
			]);
		} catch (NotFoundException $e) {
			return new DataResponse(['message' => $this->l->t('Task not found')], Http::STATUS_NOT_FOUND);
		} catch (\RuntimeException $e) {
			return new DataResponse(['message' => $this->l->t('Internal error')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}

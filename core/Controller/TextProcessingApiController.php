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
use OCP\TextProcessing\ITaskType;
use OCP\TextProcessing\Task;
use OCP\TextProcessing\IManager;
use OCP\PreConditionNotMetException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class TextProcessingApiController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string           $appName,
		IRequest         $request,
		private IManager $languageModelManager,
		private IL10N    $l,
		private ?string  $userId,
		private ContainerInterface $container,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * This endpoint returns all available LanguageModel task types
	 *
	 * @PublicPage
	 */
	public function taskTypes(): DataResponse {
		$typeClasses = $this->languageModelManager->getAvailableTaskTypes();
		$types = [];
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
	 * @PublicPage
	 * @UserRateThrottle(limit=20, period=120)
	 * @AnonRateThrottle(limit=5, period=120)
	 */
	public function schedule(string $input, string $type, string $appId, string $identifier = ''): DataResponse {
		try {
			$task = new Task($type, $input, $this->userId, $appId, $identifier);
		} catch (InvalidArgumentException) {
			return new DataResponse(['message' => $this->l->t('Requested task type does not exist')], Http::STATUS_BAD_REQUEST);
		}
		try {
			$this->languageModelManager->scheduleTask($task);

			$json = $task->jsonSerialize();

			return new DataResponse([
				'task' => $json,
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
	 */
	public function getTask(int $id): DataResponse {
		try {
			$task = $this->languageModelManager->getTask($id);

			if ($this->userId !== $task->getUserId()) {
				return new DataResponse(['message' => $this->l->t('Task not found')], Http::STATUS_NOT_FOUND);
			}

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
}

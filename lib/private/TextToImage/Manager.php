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

namespace OC\TextToImage;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\TextToImage\Db\Task as DbTask;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\TextToImage\Exception\TaskNotFoundException;
use OCP\TextToImage\IManager;
use OCP\TextToImage\Task;
use OC\TextToImage\Db\TaskMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\IServerContainer;
use OCP\TextToImage\IProvider;
use OCP\PreConditionNotMetException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class Manager implements IManager {
	/** @var ?IProvider[] */
	private ?array $providers = null;
	private IAppData $appData;

	public function __construct(
		private IServerContainer $serverContainer,
		private Coordinator $coordinator,
		private LoggerInterface $logger,
		private IJobList $jobList,
		private TaskMapper $taskMapper,
		private IConfig $config,
		private IAppDataFactory $appDataFactory,
	) {
		$this->appData = $this->appDataFactory->get('core');
	}

	public function getProviders(): array {
		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			return [];
		}

		if ($this->providers !== null) {
			return $this->providers;
		}

		$this->providers = [];

		foreach ($context->getTextToImageProviders() as $providerServiceRegistration) {
			$class = $providerServiceRegistration->getService();
			try {
				$this->providers[$class] = $this->serverContainer->get($class);
			} catch (Throwable $e) {
				$this->logger->error('Failed to load Text to image provider ' . $class, [
					'exception' => $e,
				]);
			}
		}

		return $this->providers;
	}

	public function hasProviders(): bool {
		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			return false;
		}
		return count($context->getTextToImageProviders()) > 0;
	}

	/**
	 * @inheritDoc
	 */
	public function runTask(Task $task): void {
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No text to image provider is installed that can handle this task');
		}
		$providers = $this->getProviders();

		$json = $this->config->getAppValue('core', 'ai.text2image_provider', '');
		if ($json !== '') {
			$className = json_decode($json, true);
			$provider = current(array_filter($providers, fn ($provider) => $provider::class === $className));
			if ($provider !== false) {
				$providers = [$provider];
			}
		}

		foreach ($providers as $provider) {
			try {
				$task->setStatus(Task::STATUS_RUNNING);
				if ($task->getId() === null) {
					$taskEntity = $this->taskMapper->insert(DbTask::fromPublicTask($task));
					$task->setId($taskEntity->getId());
				} else {
					$this->taskMapper->update(DbTask::fromPublicTask($task));
				}
				try {
					$folder = $this->appData->getFolder('text2image');
				} catch(\OCP\Files\NotFoundException $e) {
					$folder = $this->appData->newFolder('text2image');
				}
				$file = $folder->newFile((string) $task->getId());
				$provider->generate($task->getInput(), $file->write());
				$task->setStatus(Task::STATUS_SUCCESSFUL);
				$this->taskMapper->update(DbTask::fromPublicTask($task));
				return;
			} catch (\RuntimeException $e) {
				$this->logger->info('Text2Image generation using provider ' . $provider->getName() . ' failed', ['exception' => $e]);
				$task->setStatus(Task::STATUS_FAILED);
				$this->taskMapper->update(DbTask::fromPublicTask($task));
				throw $e;
			} catch (\Throwable $e) {
				$this->logger->info('Text2Image generation using provider ' . $provider->getName() . ' failed', ['exception' => $e]);
				$task->setStatus(Task::STATUS_FAILED);
				$this->taskMapper->update(DbTask::fromPublicTask($task));
				throw new RuntimeException('Text2Image generation using provider ' . $provider->getName() . ' failed: ' . $e->getMessage(), 0, $e);
			}
		}

		throw new RuntimeException('Could not run task');
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function scheduleTask(Task $task): void {
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No text to image provider is installed that can handle this task');
		}
		$task->setStatus(Task::STATUS_SCHEDULED);
		$taskEntity = DbTask::fromPublicTask($task);
		$this->taskMapper->insert($taskEntity);
		$task->setId($taskEntity->getId());
		$this->jobList->add(TaskBackgroundJob::class, [
			'taskId' => $task->getId()
		]);
	}

	/**
	 * @inheritDoc
	 */
	public function deleteTask(Task $task): void {
		$taskEntity = DbTask::fromPublicTask($task);
		$this->taskMapper->delete($taskEntity);
		$this->jobList->remove(TaskBackgroundJob::class, [
			'taskId' => $task->getId()
		]);
	}

	/**
	 * Get a task from its id
	 *
	 * @param int $id The id of the task
	 * @return Task
	 * @throws RuntimeException If the query failed
	 * @throws TaskNotFoundException If the task could not be found
	 */
	public function getTask(int $id): Task {
		try {
			$taskEntity = $this->taskMapper->find($id);
			return $taskEntity->toPublicTask();
		} catch (DoesNotExistException $e) {
			throw new TaskNotFoundException('Could not find task with the provided id');
		} catch (MultipleObjectsReturnedException $e) {
			throw new RuntimeException('Could not uniquely identify task with given id', 0, $e);
		} catch (Exception $e) {
			throw new RuntimeException('Failure while trying to find task by id: ' . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * Get a task from its user id and task id
	 * If userId is null, this can only get a task that was scheduled anonymously
	 *
	 * @param int $id The id of the task
	 * @param string|null $userId The user id that scheduled the task
	 * @return Task
	 * @throws RuntimeException If the query failed
	 * @throws TaskNotFoundException If the task could not be found
	 */
	public function getUserTask(int $id, ?string $userId): Task {
		try {
			$taskEntity = $this->taskMapper->findByIdAndUser($id, $userId);
			return $taskEntity->toPublicTask();
		} catch (DoesNotExistException $e) {
			throw new TaskNotFoundException('Could not find task with the provided id and user id');
		} catch (MultipleObjectsReturnedException $e) {
			throw new RuntimeException('Could not uniquely identify task with given id and user id', 0, $e);
		} catch (Exception $e) {
			throw new RuntimeException('Failure while trying to find task by id and user id: ' . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * Get a list of tasks scheduled by a specific user for a specific app
	 * and optionally with a specific identifier.
	 * This cannot be used to get anonymously scheduled tasks
	 *
	 * @param string $userId
	 * @param string $appId
	 * @param string|null $identifier
	 * @return array
	 */
	public function getUserTasksByApp(string $userId, string $appId, ?string $identifier = null): array {
		try {
			$taskEntities = $this->taskMapper->findUserTasksByApp($userId, $appId, $identifier);
			return array_map(static function (DbTask $taskEntity) {
				return $taskEntity->toPublicTask();
			}, $taskEntities);
		} catch (Exception $e) {
			throw new RuntimeException('Failure while trying to find tasks by appId and identifier: ' . $e->getMessage(), 0, $e);
		}
	}
}

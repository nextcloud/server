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
use OC\TextToImage\Db\TaskMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\PreConditionNotMetException;
use OCP\TextToImage\Exception\TaskFailureException;
use OCP\TextToImage\Exception\TaskNotFoundException;
use OCP\TextToImage\IManager;
use OCP\TextToImage\IProvider;
use OCP\TextToImage\Task;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class Manager implements IManager {
	/** @var ?list<IProvider> */
	private ?array $providers = null;
	private IAppData $appData;

	public function __construct(
		private IServerContainer $serverContainer,
		private Coordinator $coordinator,
		private LoggerInterface $logger,
		private IJobList $jobList,
		private TaskMapper $taskMapper,
		private IConfig $config,
		IAppDataFactory $appDataFactory,
	) {
		$this->appData = $appDataFactory->get('core');
	}

	/**
	 * @inheritDoc
	 */
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
				/** @var IProvider $provider */
				$provider = $this->serverContainer->get($class);
				$this->providers[] = $provider;
			} catch (Throwable $e) {
				$this->logger->error('Failed to load Text to image provider ' . $class, [
					'exception' => $e,
				]);
			}
		}

		return $this->providers;
	}

	/**
	 * @inheritDoc
	 */
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
		$this->logger->debug('Running TextToImage Task');
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No text to image provider is installed that can handle this task');
		}
		$providers = $this->getPreferredProviders();

		foreach ($providers as $provider) {
			$this->logger->debug('Trying to run Text2Image provider '.$provider::class);
			try {
				$task->setStatus(Task::STATUS_RUNNING);
				$completionExpectedAt = new \DateTime('now');
				$completionExpectedAt->add(new \DateInterval('PT'.$provider->getExpectedRuntime().'S'));
				$task->setCompletionExpectedAt($completionExpectedAt);
				if ($task->getId() === null) {
					$this->logger->debug('Inserting Text2Image task into DB');
					$taskEntity = $this->taskMapper->insert(DbTask::fromPublicTask($task));
					$task->setId($taskEntity->getId());
				} else {
					$this->logger->debug('Updating Text2Image task in DB');
					$this->taskMapper->update(DbTask::fromPublicTask($task));
				}
				try {
					$folder = $this->appData->getFolder('text2image');
				} catch(NotFoundException) {
					$this->logger->debug('Creating folder in appdata for Text2Image results');
					$folder = $this->appData->newFolder('text2image');
				}
				try {
					$folder = $folder->getFolder((string) $task->getId());
				} catch(NotFoundException) {
					$this->logger->debug('Creating new folder in appdata Text2Image results folder');
					$folder = $folder->newFolder((string) $task->getId());
				}
				$this->logger->debug('Creating result files for Text2Image task');
				$resources = [];
				$files = [];
				for ($i = 0; $i < $task->getNumberOfImages(); $i++) {
					$file = $folder->newFile((string) $i);
					$files[] = $file;
					$resource = $file->write();
					if ($resource !== false && $resource !== true && is_resource($resource)) {
						$resources[] = $resource;
					} else {
						throw new RuntimeException('Text2Image generation using provider "' . $provider->getName() . '" failed: Couldn\'t open file to write.');
					}
				}
				$this->logger->debug('Calling Text2Image provider\'s generate method');
				$provider->generate($task->getInput(), $resources);
				for ($i = 0; $i < $task->getNumberOfImages(); $i++) {
					if (is_resource($resources[$i])) {
						// If $resource hasn't been closed yet, we'll do that here
						fclose($resources[$i]);
					}
				}
				$task->setStatus(Task::STATUS_SUCCESSFUL);
				$this->logger->debug('Updating Text2Image task in DB');
				$this->taskMapper->update(DbTask::fromPublicTask($task));
				return;
			} catch (\RuntimeException|\Throwable $e) {
				for ($i = 0; $i < $task->getNumberOfImages(); $i++) {
					if (isset($files, $files[$i])) {
						try {
							$files[$i]->delete();
						} catch(NotPermittedException $e) {
							$this->logger->warning('Failed to clean up Text2Image result file after error', ['exception' => $e]);
						}
					}
				}

				$this->logger->info('Text2Image generation using provider "' . $provider->getName() . '" failed', ['exception' => $e]);
				$task->setStatus(Task::STATUS_FAILED);
				try {
					$this->taskMapper->update(DbTask::fromPublicTask($task));
				} catch (Exception $e) {
					$this->logger->warning('Failed to update database after Text2Image error', ['exception' => $e]);
				}
				throw new TaskFailureException('Text2Image generation using provider "' . $provider->getName() . '" failed: ' . $e->getMessage(), 0, $e);
			}
		}

		$task->setStatus(Task::STATUS_FAILED);
		try {
			$this->taskMapper->update(DbTask::fromPublicTask($task));
		} catch (Exception $e) {
			$this->logger->warning('Failed to update database after Text2Image error', ['exception' => $e]);
		}
		throw new TaskFailureException('Could not run task');
	}

	/**
	 * @inheritDoc
	 */
	public function scheduleTask(Task $task): void {
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No text to image provider is installed that can handle this task');
		}
		$this->logger->debug('Scheduling Text2Image Task');
		$task->setStatus(Task::STATUS_SCHEDULED);
		$completionExpectedAt = new \DateTime('now');
		$completionExpectedAt->add(new \DateInterval('PT'.$this->getPreferredProviders()[0]->getExpectedRuntime().'S'));
		$task->setCompletionExpectedAt($completionExpectedAt);
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
	public function runOrScheduleTask(Task $task) : void {
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No text to image provider is installed that can handle this task');
		}
		$providers = $this->getPreferredProviders();
		$maxExecutionTime = (int) ini_get('max_execution_time');
		// Offload the task to a background job if the expected runtime of the likely provider is longer than 80% of our max execution time
		if ($providers[0]->getExpectedRuntime() > $maxExecutionTime * 0.8) {
			$this->scheduleTask($task);
			return;
		}
		$this->runTask($task);
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
	 * @return Task[]
	 * @throws RuntimeException
	 */
	public function getUserTasksByApp(?string $userId, string $appId, ?string $identifier = null): array {
		try {
			$taskEntities = $this->taskMapper->findUserTasksByApp($userId, $appId, $identifier);
			return array_map(static function (DbTask $taskEntity) {
				return $taskEntity->toPublicTask();
			}, $taskEntities);
		} catch (Exception $e) {
			throw new RuntimeException('Failure while trying to find tasks by appId and identifier: ' . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * @return list<IProvider>
	 */
	private function getPreferredProviders() {
		$providers = $this->getProviders();
		$json = $this->config->getAppValue('core', 'ai.text2image_provider', '');
		if ($json !== '') {
			try {
				$id = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
				$provider = current(array_filter($providers, fn ($provider) => $provider->getId() === $id));
				if ($provider !== false && $provider !== null) {
					$providers = [$provider];
				}
			} catch (\JsonException $e) {
				$this->logger->warning('Failed to decode Text2Image setting `ai.text2image_provider`', ['exception' => $e]);
			}
		}

		return $providers;
	}
}

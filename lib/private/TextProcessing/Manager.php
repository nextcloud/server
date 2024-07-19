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

namespace OC\TextProcessing;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\TextProcessing\Db\Task as DbTask;
use OC\TextProcessing\Db\TaskMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\BackgroundJob\IJobList;
use OCP\Common\Exception\NotFoundException;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\PreConditionNotMetException;
use OCP\TextProcessing\Exception\TaskFailureException;
use OCP\TextProcessing\IManager;
use OCP\TextProcessing\IProvider;
use OCP\TextProcessing\IProviderWithExpectedRuntime;
use OCP\TextProcessing\Task;
use OCP\TextProcessing\Task as OCPTask;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class Manager implements IManager {
	/** @var ?IProvider[] */
	private ?array $providers = null;

	public function __construct(
		private IServerContainer $serverContainer,
		private Coordinator $coordinator,
		private LoggerInterface $logger,
		private IJobList $jobList,
		private TaskMapper $taskMapper,
		private IConfig $config,
	) {
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

		foreach ($context->getTextProcessingProviders() as $providerServiceRegistration) {
			$class = $providerServiceRegistration->getService();
			try {
				$this->providers[$class] = $this->serverContainer->get($class);
			} catch (Throwable $e) {
				$this->logger->error('Failed to load Text processing provider ' . $class, [
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
		return count($context->getTextProcessingProviders()) > 0;
	}

	/**
	 * @inheritDoc
	 */
	public function getAvailableTaskTypes(): array {
		$tasks = [];
		foreach ($this->getProviders() as $provider) {
			$tasks[$provider->getTaskType()] = true;
		}
		return array_keys($tasks);
	}

	public function canHandleTask(OCPTask $task): bool {
		return in_array($task->getType(), $this->getAvailableTaskTypes());
	}

	/**
	 * @inheritDoc
	 */
	public function runTask(OCPTask $task): string {
		if (!$this->canHandleTask($task)) {
			throw new PreConditionNotMetException('No text processing provider is installed that can handle this task');
		}
		$providers = $this->getPreferredProviders($task);

		foreach ($providers as $provider) {
			try {
				$task->setStatus(OCPTask::STATUS_RUNNING);
				if ($provider instanceof IProviderWithExpectedRuntime) {
					$completionExpectedAt = new \DateTime('now');
					$completionExpectedAt->add(new \DateInterval('PT'.$provider->getExpectedRuntime().'S'));
					$task->setCompletionExpectedAt($completionExpectedAt);
				}
				if ($task->getId() === null) {
					$taskEntity = $this->taskMapper->insert(DbTask::fromPublicTask($task));
					$task->setId($taskEntity->getId());
				} else {
					$this->taskMapper->update(DbTask::fromPublicTask($task));
				}
				$output = $task->visitProvider($provider);
				$task->setOutput($output);
				$task->setStatus(OCPTask::STATUS_SUCCESSFUL);
				$this->taskMapper->update(DbTask::fromPublicTask($task));
				return $output;
			} catch (\Throwable $e) {
				$this->logger->error('LanguageModel call using provider ' . $provider->getName() . ' failed', ['exception' => $e]);
				$task->setStatus(OCPTask::STATUS_FAILED);
				$this->taskMapper->update(DbTask::fromPublicTask($task));
				throw new TaskFailureException('LanguageModel call using provider ' . $provider->getName() . ' failed: ' . $e->getMessage(), 0, $e);
			}
		}

		$task->setStatus(OCPTask::STATUS_FAILED);
		$this->taskMapper->update(DbTask::fromPublicTask($task));
		throw new TaskFailureException('Could not run task');
	}

	/**
	 * @inheritDoc
	 */
	public function scheduleTask(OCPTask $task): void {
		if (!$this->canHandleTask($task)) {
			throw new PreConditionNotMetException('No LanguageModel provider is installed that can handle this task');
		}
		$task->setStatus(OCPTask::STATUS_SCHEDULED);
		$providers = $this->getPreferredProviders($task);
		if (count($providers) === 0) {
			throw new PreConditionNotMetException('No LanguageModel provider is installed that can handle this task');
		}
		[$provider,] = $providers;
		if ($provider instanceof IProviderWithExpectedRuntime) {
			$completionExpectedAt = new \DateTime('now');
			$completionExpectedAt->add(new \DateInterval('PT'.$provider->getExpectedRuntime().'S'));
			$task->setCompletionExpectedAt($completionExpectedAt);
		}
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
	public function runOrScheduleTask(OCPTask $task): bool {
		if (!$this->canHandleTask($task)) {
			throw new PreConditionNotMetException('No LanguageModel provider is installed that can handle this task');
		}
		[$provider,] = $this->getPreferredProviders($task);
		$maxExecutionTime = (int) ini_get('max_execution_time');
		// Offload the task to a background job if the expected runtime of the likely provider is longer than 80% of our max execution time
		// or if the provider doesn't provide a getExpectedRuntime() method
		if (!$provider instanceof IProviderWithExpectedRuntime || $provider->getExpectedRuntime() > $maxExecutionTime * 0.8) {
			$this->scheduleTask($task);
			return false;
		}
		$this->runTask($task);
		return true;
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
	 * @return OCPTask
	 * @throws RuntimeException If the query failed
	 * @throws NotFoundException If the task could not be found
	 */
	public function getTask(int $id): OCPTask {
		try {
			$taskEntity = $this->taskMapper->find($id);
			return $taskEntity->toPublicTask();
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Could not find task with the provided id');
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
	 * @return OCPTask
	 * @throws RuntimeException If the query failed
	 * @throws NotFoundException If the task could not be found
	 */
	public function getUserTask(int $id, ?string $userId): OCPTask {
		try {
			$taskEntity = $this->taskMapper->findByIdAndUser($id, $userId);
			return $taskEntity->toPublicTask();
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Could not find task with the provided id and user id');
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

	/**
	 * @param OCPTask $task
	 * @return IProvider[]
	 */
	public function getPreferredProviders(OCPTask $task): array {
		$providers = $this->getProviders();
		$json = $this->config->getAppValue('core', 'ai.textprocessing_provider_preferences', '');
		if ($json !== '') {
			$preferences = json_decode($json, true);
			if (isset($preferences[$task->getType()])) {
				// If a preference for this task type is set, move the preferred provider to the start
				$provider = current(array_values(array_filter($providers, fn ($provider) => $provider::class === $preferences[$task->getType()])));
				if ($provider !== false) {
					$providers = array_filter($providers, fn ($p) => $p !== $provider);
					array_unshift($providers, $provider);
				}
			}
		}
		$providers = array_values(array_filter($providers, fn (IProvider $provider) => $task->canUseProvider($provider)));
		return $providers;
	}
}

<?php

namespace OC\LanguageModel;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\LanguageModel\Db\Task;
use OC\LanguageModel\Db\TaskMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\BackgroundJob\IJobList;
use OCP\Common\Exception\NotFoundException;
use OCP\DB\Exception;
use OCP\IServerContainer;
use OCP\LanguageModel\AbstractLanguageModelTask;
use OCP\LanguageModel\FreePromptTask;
use OCP\LanguageModel\HeadlineTask;
use OCP\LanguageModel\IHeadlineProvider;
use OCP\LanguageModel\ILanguageModelManager;
use OCP\LanguageModel\ILanguageModelProvider;
use OCP\LanguageModel\ILanguageModelTask;
use OCP\LanguageModel\ISummaryProvider;
use OCP\LanguageModel\ITopicsProvider;
use OCP\LanguageModel\SummaryTask;
use OCP\LanguageModel\TopicsTask;
use OCP\PreConditionNotMetException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class LanguageModelManager implements ILanguageModelManager {
	/** @var ?ILanguageModelProvider[] */
	private ?array $providers = null;

	public function __construct(
		private IServerContainer $serverContainer,
		private Coordinator $coordinator,
		private LoggerInterface $logger,
		private IJobList $jobList,
		private TaskMapper $taskMapper,
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

		foreach ($context->getLanguageModelProviders() as $providerServiceRegistration) {
			$class = $providerServiceRegistration->getService();
			try {
				$this->providers[$class] = $this->serverContainer->get($class);
			} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable $e) {
				$this->logger->error('Failed to load LanguageModel provider ' . $class, [
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
		return count($context->getLanguageModelProviders()) > 0;
	}

	/**
	 * @inheritDoc
	 */
	public function getAvailableTasks(): array {
		$tasks = [];
		foreach ($this->getProviders() as $provider) {
			$tasks[FreePromptTask::class] = true;
			if ($provider instanceof ISummaryProvider) {
				$tasks[SummaryTask::class] = true;
			}
			if ($provider instanceof IHeadlineProvider) {
				$tasks[HeadlineTask::class] = true;
			}
			if ($provider instanceof ITopicsProvider) {
				$tasks[TopicsTask::class] = true;
			}
		}
		return array_keys($tasks);
	}

	/**
	 * @inheritDoc
	 */
	public function getAvailableTaskTypes(): array {
		return array_map(fn ($taskClass) => $taskClass::TYPE, $this->getAvailableTasks());
	}

	public function canHandleTask(ILanguageModelTask $task): bool {
		return count(array_filter($this->getAvailableTasks(), fn ($class) => $task instanceof $class)) > 0;
	}

	/**
	 * @inheritDoc
	 */
	public function runTask(ILanguageModelTask $task): string {
		if (!$this->canHandleTask($task)) {
			throw new PreConditionNotMetException('No LanguageModel provider is installed that can handle this task');
		}
		foreach ($this->getProviders() as $provider) {
			if (!$task->canUseProvider($provider)) {
				continue;
			}
			try {
				$task->setStatus(ILanguageModelTask::STATUS_RUNNING);
				$taskEntity = $this->taskMapper->update(Task::fromLanguageModelTask($task));
				$task->setId($taskEntity->getId());
				$output = $task->visitProvider($provider);
				$task->setOutput($output);
				$task->setStatus(ILanguageModelTask::STATUS_SUCCESSFUL);
				$this->taskMapper->update(Task::fromLanguageModelTask($task));
				return $output;
			} catch (\RuntimeException $e) {
				$this->logger->info('LanguageModel call using provider ' . $provider->getName() . ' failed', ['exception' => $e]);
				$task->setStatus(ILanguageModelTask::STATUS_FAILED);
				$this->taskMapper->update(Task::fromLanguageModelTask($task));
				throw $e;
			} catch (\Throwable $e) {
				$this->logger->info('LanguageModel call using provider ' . $provider->getName() . ' failed', ['exception' => $e]);
				$task->setStatus(ILanguageModelTask::STATUS_FAILED);
				$this->taskMapper->update(Task::fromLanguageModelTask($task));
				throw new RuntimeException('LanguageModel call using provider ' . $provider->getName() . ' failed: ' . $e->getMessage());
			}
		}

		throw new RuntimeException('Could not transcribe file');
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function scheduleTask(ILanguageModelTask $task): void {
		if (!$this->canHandleTask($task)) {
			throw new PreConditionNotMetException('No LanguageModel provider is installed that can handle this task');
		}
		$task->setStatus(ILanguageModelTask::STATUS_SCHEDULED);
		$taskEntity = Task::fromLanguageModelTask($task);
		$this->taskMapper->insert($taskEntity);
		$task->setId($taskEntity->getId());
		$this->jobList->add(TaskBackgroundJob::class, [
			'taskId' => $task->getId()
		]);
	}

	/**
	 * @param int $id The id of the task
	 * @return ILanguageModelTask
	 * @throws RuntimeException If the query failed
	 * @throws NotFoundException If the task could not be found
	 */
	public function getTask(int $id): ILanguageModelTask {
		try {
			$taskEntity = $this->taskMapper->find($id);
			return AbstractLanguageModelTask::fromTaskEntity($taskEntity);
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Could not find task with the provided id');
		} catch (MultipleObjectsReturnedException $e) {
			throw new RuntimeException('Could not uniquely identify task with given id');
		} catch (Exception $e) {
			throw new RuntimeException('Failure while trying to find task by id: '.$e->getMessage());
		}
	}
}

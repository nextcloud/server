<?php

namespace OCP\LanguageModel;


use OC\LanguageModel\Db\Task;

abstract class AbstractLanguageModelTask implements ILanguageModelTask {
	protected ?int $id;
	protected int $status = ILanguageModelTask::STATUS_UNKNOWN;

	public final function __construct(
		protected string $input,
		protected string $appId,
		protected ?string $userId,
	) {
	}

	/**
	 * @param ILanguageModelProvider $provider
	 * @return string
	 * @throws \RuntimeException
	 */
	abstract public function visitProvider(ILanguageModelProvider $provider): string;

	abstract public function canUseProvider(ILanguageModelProvider $provider): bool;

	abstract public function getType(): string;

	/**
	 * @return int
	 */
	public final function getStatus(): int {
		return $this->status;
	}

	/**
	 * @param int $status
	 */
	public final function setStatus(int $status): void {
		$this->status = $status;
	}

	/**
	 * @return int|null
	 */
	public final function getId(): ?int {
		return $this->id;
	}

	/**
	 * @param int|null $id
	 */
	public final function setId(?int $id): void {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public final function getInput(): string {
		return $this->input;
	}

	/**
	 * @return string
	 */
	public final function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @return string|null
	 */
	public final function getUserId(): ?string {
		return $this->userId;
	}

	public final static function fromTaskEntity(Task $taskEntity): ILanguageModelTask  {
		$task = self::factory($taskEntity->getType(), $taskEntity->getInput(), $taskEntity->getuserId(), $taskEntity->getAppId());
		$task->setId($taskEntity->getId());
		$task->setStatus($taskEntity->getStatus());
		return $task;
	}

	public final static function factory(string $type, string $input, ?string $userId, string $appId): ILanguageModelTask {
		if (!in_array($type, self::TYPES)) {
			throw new \InvalidArgumentException('Unknown task type');
		}
		return new ILanguageModelTask::TYPES[$type]($input, $userId, $appId);
	}
}

<?php

namespace OCP\LanguageModel;

use OC\LanguageModel\Db\Task;

/**
 * This is an abstract LanguageModel task that implements basic
 * goodies for downstream tasks
 * @since 28.0.
 * @template T of ILanguageModelProvider
 * @template-implements ILanguageModelTask<T>
 */
abstract class AbstractLanguageModelTask implements ILanguageModelTask {
	protected ?int $id;
	protected ?string $output;
	protected int $status = ILanguageModelTask::STATUS_UNKNOWN;

	/**
	 * @param string $input
	 * @param string $appId
	 * @param string|null $userId
	 * @since 28.0.0
	 */
	final public function __construct(
		protected string $input,
		protected string $appId,
		protected ?string $userId,
	) {
	}

	/**
	 * @return string
	 * @since 28.0.0
	 */
	abstract public function getType(): string;

	/**
	 * @return string|null
	 * @since 28.0.0
	 */
	final public function getOutput(): ?string {
		return $this->output;
	}

	/**
	 * @param string|null $output
	 * @since 28.0.0
	 */
	final public function setOutput(?string $output): void {
		$this->output = $output;
	}

	/**
	 * @return int
	 * @since 28.0.0
	 */
	final public function getStatus(): int {
		return $this->status;
	}

	/**
	 * @param int $status
	 * @since 28.0.0
	 */
	final public function setStatus(int $status): void {
		$this->status = $status;
	}

	/**
	 * @return int|null
	 * @since 28.0.0
	 */
	final public function getId(): ?int {
		return $this->id;
	}

	/**
	 * @param int|null $id
	 * @since 28.0.0
	 */
	final public function setId(?int $id): void {
		$this->id = $id;
	}

	/**
	 * @return string
	 * @since 28.0.0
	 */
	final public function getInput(): string {
		return $this->input;
	}

	/**
	 * @return string
	 * @since 28.0.0
	 */
	final public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @return string|null
	 * @since 28.0.0
	 */
	final public function getUserId(): ?string {
		return $this->userId;
	}

	/**
	 * @return array
	 * @since 28.0.0
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'type' => $this->getType(),
			'status' => $this->getStatus(),
			'userId' => $this->getUserId(),
			'appId' => $this->getAppId(),
			'input' => $this->getInput(),
			'output' => $this->getOutput(),
		];
	}


	/**
	 * @param Task $taskEntity
	 * @return ILanguageModelTask
	 * @since 28.0.0
	 */
	final public static function fromTaskEntity(Task $taskEntity): ILanguageModelTask {
		$task = self::factory($taskEntity->getType(), $taskEntity->getInput(), $taskEntity->getuserId(), $taskEntity->getAppId());
		$task->setId($taskEntity->getId());
		$task->setStatus($taskEntity->getStatus());
		return $task;
	}

	/**
	 * @param string $type
	 * @param string $input
	 * @param string|null $userId
	 * @param string $appId
	 * @return ILanguageModelTask
	 * @throws \InvalidArgumentException
	 * @since 28.0.0
	 */
	final public static function factory(string $type, string $input, ?string $userId, string $appId): ILanguageModelTask {
		if (!in_array($type, self::TYPES)) {
			throw new \InvalidArgumentException('Unknown task type');
		}
		return new (ILanguageModelTask::TYPES[$type])($input, $userId, $appId);
	}
}

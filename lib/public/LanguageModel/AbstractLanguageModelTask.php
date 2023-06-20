<?php

namespace OCP\LanguageModel;

use OC\LanguageModel\Db\Task;

abstract class AbstractLanguageModelTask implements ILanguageModelTask {
	protected ?int $id;
	protected ?string $output;
	protected int $status = ILanguageModelTask::STATUS_UNKNOWN;

	final public function __construct(
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
	 * @return string|null
	 */
	final public function getOutput(): ?string {
		return $this->output;
	}

	/**
	 * @param string|null $output
	 */
	final public function setOutput(?string $output): void {
		$this->output = $output;
	}

	/**
	 * @return int
	 */
	final public function getStatus(): int {
		return $this->status;
	}

	/**
	 * @param int $status
	 */
	final public function setStatus(int $status): void {
		$this->status = $status;
	}

	/**
	 * @return int|null
	 */
	final public function getId(): ?int {
		return $this->id;
	}

	/**
	 * @param int|null $id
	 */
	final public function setId(?int $id): void {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	final public function getInput(): string {
		return $this->input;
	}

	/**
	 * @return string
	 */
	final public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @return string|null
	 */
	final public function getUserId(): ?string {
		return $this->userId;
	}

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
	 */
	final public static function factory(string $type, string $input, ?string $userId, string $appId): ILanguageModelTask {
		if (!in_array($type, self::TYPES)) {
			throw new \InvalidArgumentException('Unknown task type');
		}
		return new (ILanguageModelTask::TYPES[$type])($input, $userId, $appId);
	}
}

<?php

namespace OCP\LanguageModel;

abstract class AbstractLanguageModelTask {

	public const STATUS_UNKNOWN = 0;
	public const STATUS_RUNNING = 1;
	public const STATUS_SUCCESSFUL = 2;
	public const STATUS_FAILED = 4;

	protected ?int $id;
	protected int $status = self::STATUS_UNKNOWN;

	public function __construct(
		protected string $input,
		protected string $appId,
		protected ?string $userId,
	) {
	}

	abstract public function visitProvider(ILanguageModelProvider $provider): string;

	/**
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}

	/**
	 * @param int $status
	 */
	public function setStatus(int $status): void {
		$this->status = $status;
	}

	/**
	 * @return int|null
	 */
	public function getId(): ?int {
		return $this->id;
	}

	/**
	 * @param int|null $id
	 */
	public function setId(?int $id): void {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getInput(): string {
		return $this->input;
	}

	/**
	 * @return string
	 */
	public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @return string|null
	 */
	public function getUserId(): ?string {
		return $this->userId;
	}
}

<?php

namespace OCP\LanguageModel;

interface ILanguageModelTask {
	public const STATUS_FAILED = 4;
	public const STATUS_SUCCESSFUL = 3;
	public const STATUS_RUNNING = 2;
	public const STATUS_SCHEDULED = 1;
	public const STATUS_UNKNOWN = 0;

	public const TYPES = [
		SummaryTask::TYPE => SummaryTask::class,
		FreePromptTask::TYPE => FreePromptTask::class,
	];

	/**
	 * @return string
	 */
	public function getType(): string;

	/**
	 * @return int
	 */
	public function getStatus(): int;

	/**
	 * @param int $status
	 */
	public function setStatus(int $status): void;

	/**
	 * @param int|null $id
	 */
	public function setId(?int $id): void;

	/**
	 * @return int|null
	 */
	public function getId(): ?int;

	/**
	 * @return string
	 */
	public function getInput(): string;

	/**
	 * @return string
	 */
	public function getAppId(): string;

	/**
	 * @return string|null
	 */
	public function getUserId(): ?string;
}

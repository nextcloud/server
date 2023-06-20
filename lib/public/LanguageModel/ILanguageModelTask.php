<?php

namespace OCP\LanguageModel;

interface ILanguageModelTask extends \JsonSerializable {
	public const STATUS_FAILED = 4;
	public const STATUS_SUCCESSFUL = 3;
	public const STATUS_RUNNING = 2;
	public const STATUS_SCHEDULED = 1;
	public const STATUS_UNKNOWN = 0;

	public const TYPES = [
		FreePromptTask::TYPE => FreePromptTask::class,
		SummaryTask::TYPE => SummaryTask::class,
		HeadlineTask::TYPE => HeadlineTask::class,
		TopicsTask::TYPE => TopicsTask::class,
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
	 * @param string $output
	 */
	public function setOutput(string $output): void;

	/**
	 * @return string
	 */
	public function getOutput(): string;

	/**
	 * @return string
	 */
	public function getAppId(): string;

	/**
	 * @return string|null
	 */
	public function getUserId(): ?string;
}

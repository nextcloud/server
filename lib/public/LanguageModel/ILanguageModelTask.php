<?php

namespace OCP\LanguageModel;

/**
 * @since 28.0.0
 * @template T of ILanguageModelProvider
 */
interface ILanguageModelTask extends \JsonSerializable {
	/**
	 * @since 28.0.0
	 */
	public const STATUS_FAILED = 4;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_SUCCESSFUL = 3;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_RUNNING = 2;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_SCHEDULED = 1;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_UNKNOWN = 0;

	/**
	 * @since 28.0.0
	 */
	public const TYPES = [
		FreePromptTask::TYPE => FreePromptTask::class,
		SummaryTask::TYPE => SummaryTask::class,
		HeadlineTask::TYPE => HeadlineTask::class,
		TopicsTask::TYPE => TopicsTask::class,
	];

	/**
	 * @param T $provider
	 * @return string
	 * @since 28.0.0
	 */
	public function visitProvider($provider): string;

	/**
	 * @param T $provider
	 * @return bool
	 * @since 28.0.0
	 */
	public function canUseProvider($provider): bool;


	/**
	 * @return string
	 * @since 28.0.0
	 */
	public function getType(): string;

	/**
	 * @return int
	 * @since 28.0.0
	 */
	public function getStatus(): int;

	/**
	 * @param int $status
	 * @since 28.0.0
	 */
	public function setStatus(int $status): void;

	/**
	 * @param int|null $id
	 * @since 28.0.0
	 */
	public function setId(?int $id): void;

	/**
	 * @return int|null
	 * @since 28.0.0
	 */
	public function getId(): ?int;

	/**
	 * @return string
	 * @since 28.0.0
	 */
	public function getInput(): string;

	/**
	 * @param string|null $output
	 * @since 28.0.0
	 */
	public function setOutput(?string $output): void;

	/**
	 * @return null|string
	 * @since 28.0.0
	 */
	public function getOutput(): ?string;

	/**
	 * @return string
	 * @since 28.0.0
	 */
	public function getAppId(): string;

	/**
	 * @return string|null
	 * @since 28.0.0
	 */
	public function getUserId(): ?string;
}

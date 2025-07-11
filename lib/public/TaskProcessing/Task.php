<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TaskProcessing;

use DateTime;
use OCP\TaskProcessing\Exception\ValidationException;

/**
 * This is a task processing task
 *
 * @since 30.0.0
 */
final class Task implements \JsonSerializable {
	protected ?int $id = null;

	protected ?DateTime $completionExpectedAt = null;

	protected ?array $output = null;

	protected ?string $errorMessage = null;

	protected ?float $progress = null;

	protected int $lastUpdated;

	protected ?string $webhookUri = null;
	protected ?string $webhookMethod = null;

	/**
	 * @since 30.0.0
	 */
	public const STATUS_CANCELLED = 5;
	/**
	 * @since 30.0.0
	 */
	public const STATUS_FAILED = 4;
	/**
	 * @since 30.0.0
	 */
	public const STATUS_SUCCESSFUL = 3;
	/**
	 * @since 30.0.0
	 */
	public const STATUS_RUNNING = 2;
	/**
	 * @since 30.0.0
	 */
	public const STATUS_SCHEDULED = 1;
	/**
	 * @since 30.0.0
	 */
	public const STATUS_UNKNOWN = 0;

	/**
	 * @psalm-var self::STATUS_*
	 */
	protected int $status = self::STATUS_UNKNOWN;

	protected ?int $scheduledAt = null;
	protected ?int $startedAt = null;
	protected ?int $endedAt = null;

	/**
	 * @param string $taskTypeId
	 * @param array<string,list<numeric|string>|numeric|string> $input
	 * @param string $appId
	 * @param string|null $userId
	 * @param null|string $customId An arbitrary customId for this task. max length: 255 chars
	 * @since 30.0.0
	 */
	final public function __construct(
		protected readonly string $taskTypeId,
		protected array $input,
		protected readonly string $appId,
		protected readonly ?string $userId,
		protected readonly ?string $customId = '',
	) {
		$this->lastUpdated = time();
	}

	/**
	 * @since 30.0.0
	 */
	final public function getTaskTypeId(): string {
		return $this->taskTypeId;
	}

	/**
	 * @psalm-return self::STATUS_*
	 * @since 30.0.0
	 */
	final public function getStatus(): int {
		return $this->status;
	}

	/**
	 * @psalm-param self::STATUS_* $status
	 * @since 30.0.0
	 */
	final public function setStatus(int $status): void {
		$this->status = $status;
	}

	/**
	 * @param ?DateTime $at
	 * @since 30.0.0
	 */
	final public function setCompletionExpectedAt(?DateTime $at): void {
		$this->completionExpectedAt = $at;
	}

	/**
	 * @return ?DateTime
	 * @since 30.0.0
	 */
	final public function getCompletionExpectedAt(): ?DateTime {
		return $this->completionExpectedAt;
	}

	/**
	 * @return int|null
	 * @since 30.0.0
	 */
	final public function getId(): ?int {
		return $this->id;
	}

	/**
	 * @param int|null $id
	 * @since 30.0.0
	 */
	final public function setId(?int $id): void {
		$this->id = $id;
	}

	/**
	 * @param null|array<array-key, list<numeric|string>|numeric|string> $output
	 * @since 30.0.0
	 */
	final public function setOutput(?array $output): void {
		$this->output = $output;
	}

	/**
	 * @return array<array-key, list<numeric|string>|numeric|string>|null
	 * @since 30.0.0
	 */
	final public function getOutput(): ?array {
		return $this->output;
	}

	/**
	 * @return array<array-key, list<numeric|string>|numeric|string>
	 * @since 30.0.0
	 */
	final public function getInput(): array {
		return $this->input;
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	final public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @return null|string
	 * @since 30.0.0
	 */
	final public function getCustomId(): ?string {
		return $this->customId;
	}

	/**
	 * @return string|null
	 * @since 30.0.0
	 */
	final public function getUserId(): ?string {
		return $this->userId;
	}

	/**
	 * @return int
	 * @since 30.0.0
	 */
	final public function getLastUpdated(): int {
		return $this->lastUpdated;
	}

	/**
	 * @param int $lastUpdated
	 * @since 30.0.0
	 */
	final public function setLastUpdated(int $lastUpdated): void {
		$this->lastUpdated = $lastUpdated;
	}

	/**
	 * @return int|null
	 * @since 30.0.0
	 */
	final public function getScheduledAt(): ?int {
		return $this->scheduledAt;
	}

	/**
	 * @param int|null $scheduledAt
	 * @since 30.0.0
	 */
	final public function setScheduledAt(?int $scheduledAt): void {
		$this->scheduledAt = $scheduledAt;
	}

	/**
	 * @return int|null
	 * @since 30.0.0
	 */
	final public function getStartedAt(): ?int {
		return $this->startedAt;
	}

	/**
	 * @param int|null $startedAt
	 * @since 30.0.0
	 */
	final public function setStartedAt(?int $startedAt): void {
		$this->startedAt = $startedAt;
	}

	/**
	 * @return int|null
	 * @since 30.0.0
	 */
	final public function getEndedAt(): ?int {
		return $this->endedAt;
	}

	/**
	 * @param int|null $endedAt
	 * @since 30.0.0
	 */
	final public function setEndedAt(?int $endedAt): void {
		$this->endedAt = $endedAt;
	}

	/**
	 * @psalm-return array{id: int, lastUpdated: int, type: string, status: 'STATUS_CANCELLED'|'STATUS_FAILED'|'STATUS_SUCCESSFUL'|'STATUS_RUNNING'|'STATUS_SCHEDULED'|'STATUS_UNKNOWN', userId: ?string, appId: string, input: array<string, list<numeric|string>|numeric|string>, output: ?array<string, list<numeric|string>|numeric|string>, customId: ?string, completionExpectedAt: ?int, progress: ?float, scheduledAt: ?int, startedAt: ?int, endedAt: ?int}
	 * @since 30.0.0
	 */
	final public function jsonSerialize(): array {
		return [
			'id' => (int)$this->getId(),
			'type' => $this->getTaskTypeId(),
			'lastUpdated' => $this->getLastUpdated(),
			'status' => self::statusToString($this->getStatus()),
			'userId' => $this->getUserId(),
			'appId' => $this->getAppId(),
			'input' => $this->getInput(),
			'output' => $this->getOutput(),
			'customId' => $this->getCustomId(),
			'completionExpectedAt' => $this->getCompletionExpectedAt()?->getTimestamp(),
			'progress' => $this->getProgress(),
			'scheduledAt' => $this->getScheduledAt(),
			'startedAt' => $this->getStartedAt(),
			'endedAt' => $this->getEndedAt(),
		];
	}

	/**
	 * @param string|null $error
	 * @return void
	 * @since 30.0.0
	 */
	final public function setErrorMessage(?string $error) {
		$this->errorMessage = $error;
	}

	/**
	 * @return string|null
	 * @since 30.0.0
	 */
	final public function getErrorMessage(): ?string {
		return $this->errorMessage;
	}

	/**
	 * @param array $input
	 * @return void
	 * @since 30.0.0
	 */
	final public function setInput(array $input): void {
		$this->input = $input;
	}

	/**
	 * @param float|null $progress
	 * @return void
	 * @throws ValidationException
	 * @since 30.0.0
	 */
	final public function setProgress(?float $progress): void {
		if ($progress < 0 || $progress > 1.0) {
			throw new ValidationException('Progress must be between 0.0 and 1.0 inclusively; ' . $progress . ' given');
		}
		$this->progress = $progress;
	}

	/**
	 * @return float|null
	 * @since 30.0.0
	 */
	final public function getProgress(): ?float {
		return $this->progress;
	}

	/**
	 * @return null|string
	 * @since 30.0.0
	 */
	final public function getWebhookUri(): ?string {
		return $this->webhookUri;
	}

	/**
	 * @param string|null $webhookUri
	 * @return void
	 * @since 30.0.0
	 */
	final public function setWebhookUri(?string $webhookUri): void {
		$this->webhookUri = $webhookUri;
	}

	/**
	 * @return null|string
	 * @since 30.0.0
	 */
	final public function getWebhookMethod(): ?string {
		return $this->webhookMethod;
	}

	/**
	 * @param string|null $webhookMethod
	 * @return void
	 * @since 30.0.0
	 */
	final public function setWebhookMethod(?string $webhookMethod): void {
		$this->webhookMethod = $webhookMethod;
	}

	/**
	 * @param int $status
	 * @return 'STATUS_CANCELLED'|'STATUS_FAILED'|'STATUS_SUCCESSFUL'|'STATUS_RUNNING'|'STATUS_SCHEDULED'|'STATUS_UNKNOWN'
	 * @since 30.0.0
	 */
	final public static function statusToString(int $status): string {
		return match ($status) {
			self::STATUS_CANCELLED => 'STATUS_CANCELLED',
			self::STATUS_FAILED => 'STATUS_FAILED',
			self::STATUS_SUCCESSFUL => 'STATUS_SUCCESSFUL',
			self::STATUS_RUNNING => 'STATUS_RUNNING',
			self::STATUS_SCHEDULED => 'STATUS_SCHEDULED',
			default => 'STATUS_UNKNOWN',
		};
	}
}

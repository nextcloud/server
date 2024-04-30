<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Marcel Klehr <mklehr@gmx.net>
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

	/**
	 * @param array<string,mixed> $input
	 * @param string $appId
	 * @param string|null $userId
	 * @param null|string $identifier An arbitrary identifier for this task. max length: 255 chars
	 * @since 30.0.0
	 */
	final public function __construct(
		protected readonly string $taskType,
		protected array $input,
		protected readonly string $appId,
		protected readonly ?string $userId,
		protected readonly ?string $identifier = '',
	) {
	}

	/**
	 * @since 30.0.0
	 */
	final public function getTaskType(): string {
		return $this->taskType;
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
	 * @since 30.0.0
	 */
	final public function setOutput(?array $output): void {
		$this->output = $output;
	}

	/**
	 * @return array<string, mixed>|null
	 * @since 30.0.0
	 */
	final public function getOutput(): ?array {
		return $this->output;
	}

	/**
	 * @return array<string, mixed>
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
	final public function getIdentifier(): ?string {
		return $this->identifier;
	}

	/**
	 * @return string|null
	 * @since 30.0.0
	 */
	final public function getUserId(): ?string {
		return $this->userId;
	}

	/**
	 * @psalm-return array{id: ?int, status: self::STATUS_*, userId: ?string, appId: string, input: ?array, output: ?array, identifier: ?string, completionExpectedAt: ?int, progress: ?float}
	 * @since 30.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'status' => $this->getStatus(),
			'userId' => $this->getUserId(),
			'appId' => $this->getAppId(),
			'input' => $this->getInput(),
			'output' => $this->getOutput(),
			'identifier' => $this->getIdentifier(),
			'completionExpectedAt' => $this->getCompletionExpectedAt()->getTimestamp(),
			'progress' => $this->getProgress(),
		];
	}

	/**
	 * @param string|null $error
	 * @return void
	 * @since 30.0.0
	 */
	public function setErrorMessage(?string $error) {
		$this->errorMessage = $error;
	}

	/**
	 * @return string|null
	 * @since 30.0.0
	 */
	public function getErrorMessage(): ?string {
		return $this->errorMessage;
	}

	/**
	 * @param array $input
	 * @return void
	 * @since 30.0.0
	 */
	public function setInput(array $input): void {
		$this->input = $input;
	}

	/**
	 * @param float|null $progress
	 * @return void
	 * @throws ValidationException
	 * @since 30.0.0
	 */
	public function setProgress(?float $progress): void {
		if ($progress < 0 || $progress > 1.0) {
			throw new ValidationException('Progress must be between 0.0 and 1.0 inclusively; ' . $progress . ' given');
		}
		$this->progress = $progress;
	}

	/**
	 * @return float|null
	 * @since 30.0.0
	 */
	public function getProgress(): ?float {
		return $this->progress;
	}
}

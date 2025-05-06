<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async;

use OCP\Async\Enum\ProcessExecutionTime;
use OCP\Async\Enum\BlockStatus;

interface IBlockInterface {
	public function getToken(): string;
	public function id(string $id): self;
	public function getId(): string;
	public function name(string $name): self;
	public function getName(): string;
	public function require(string $id): self;
	public function getRequire(): array;
	public function delay(int $delay): self;
	public function getDelay(): int;
	public function getExecutionTime(): ?ProcessExecutionTime;
	public function blocker(bool $blocker = true): self;
	public function isBlocker(): bool;
	public function replayable(bool $replayable = true): self;
	public function isReplayable(): bool;
	public function getStatus(): BlockStatus;
	public function getResult(): ?array;
	public function getError(): ?array;
	public function dataset(array $dataset): self;
	public function getDataset(): array;
	public function async(ProcessExecutionTime $time):  self;
}

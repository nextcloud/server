<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Model;

use OC\Async\AsyncManager;
use OC\Async\IBlockInterface;
use OCP\Async\Enum\ProcessExecutionTime;
use OCP\Async\Enum\BlockStatus;

class BlockInterface implements IBlockInterface, \JsonSerializable {
	private string $id = '';
	private string $name = '';
	private bool $blocker = false;
	private bool $replayable = false;

	/** @var string[] */
	private array $require = [];
	private int $delay = 0;
	private ?ProcessExecutionTime $executionTime = null;
	private array $dataset = [];

	public function __construct(
		private readonly ?AsyncManager $asyncManager,
		private readonly Block $block,
	) {
		$this->import($block->getMetadata()['_iface'] ?? []);
	}

	public function getBlock(): Block {
		return $this->block;
	}

	public function getToken(): string {
		return $this->block->getToken();
	}

	public function getResult(): ?array {
		if ($this->block->getBlockStatus() !== BlockStatus::SUCCESS) {
			return null;
		}

		return $this->block->getResult()['result'];
	}

	public function getError(): ?array {
		if ($this->block->getBlockStatus() !== BlockStatus::ERROR) {
			return null;
		}

		return $this->block->getResult()['error'];
	}

	public function getStatus(): BlockStatus {
		return $this->block->getBlockStatus();
	}

	public function id(string $id): self {
		$this->id = strtoupper($id);
		return $this;
	}

	public function getId(): string {
		return $this->id;
	}
	public function name(string $name): self {
		$this->name = $name;
		return $this;
	}

	public function getName(): string {
		return $this->name;
	}

	public function blocker(bool $blocker = true): self {
		$this->blocker = $blocker;
		return $this;
	}

	public function isBlocker(): bool {
		return $this->blocker;
	}

	public function replayable(bool $replayable = true): self {
		$this->replayable = $replayable;
		return $this;
	}

	public function isReplayable(): bool {
		return $this->replayable;
	}

	public function require(string $id): self {
		$this->require[] = strtoupper($id);
		return $this;
	}

	public function getRequire(): array {
		return $this->require;
	}

	public function delay(int $delay): self {
		$this->delay = $delay;
		return $this;
	}

	public function getDelay(): int {
		return $this->delay;
	}

	public function getExecutionTime(): ?ProcessExecutionTime {
		return $this->executionTime;
	}

	/**
	 * @param array<array> $dataset
	 *
	 * @return $this
	 */
	public function dataset(array $dataset): self {
		$this->dataset = $dataset;
		return $this;
	}

	public function getDataset(): array {
		return $this->dataset;
	}

//	public function getReplayCount(): int {
//		return $this->get
//	}
	/**
	 * only available during the creation of the session
	 *
	 * @return $this
	 */
	public function async(ProcessExecutionTime $time = ProcessExecutionTime::NOW): self {
		$this->asyncManager?->async($time);
		return $this;
	}

	public function import(array $data): void {
		$this->token = $data['token'] ?? '';
		$this->id($data['id'] ?? '');
		$this->name($data['name'] ?? '');
		$this->delay($data['delay'] ?? 0);
		$this->require = $data['require'] ?? [];
		$this->blocker($data['blocker'] ?? false);
		$this->replayable($data['replayable'] ?? false);
	}

	public function jsonSerialize(): array {
		return [
			'token' => $this->getToken(),
			'id' => $this->getId(),
			'name' => $this->getName(),
			'require' => $this->getRequire(),
			'delay' => $this->getDelay(),
			'blocker' => $this->isBlocker(),
			'replayable' => $this->isReplayable(),
		];
	}

	/**
	 * @param Block[] $processes
	 *
	 * @return BlockInterface[]
	 */
	public static function asBlockInterfaces(array $processes): array {
		$interfaces = [];
		foreach ($processes as $process) {
			$interfaces[] = new BlockInterface(null, $process);
		}

		return $interfaces;
	}
}

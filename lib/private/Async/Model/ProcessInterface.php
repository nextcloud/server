<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Model;

use OC\Async\AsyncManager;
use OC\Async\Enum\ProcessExecutionTime;
use OC\Async\Enum\ProcessStatus;
use OC\Async\IProcessInterface;

class ProcessInterface implements IProcessInterface, \JsonSerializable {
	private string $id = '';
	private string $name = '';
	private bool $blocker = false;
	private bool $repeatable = false;

	/** @var string[] */
	private array $require = [];
	private ?ProcessExecutionTime $delay = null;
	private array $dataset = [];

	public function __construct(
		private AsyncManager $asyncManager,
		private Process $process,
	) {
		$this->import($process->getMetadata()['_iface'] ?? []);
	}

	public function getProcess(): Process {
		return $this->process;
	}

	public function getToken(): string {
		return $this->process->getToken();
	}

	public function getResult(): ?array {
		if ($this->process->getProcessStatus() !== ProcessStatus::SUCCESS) {
			return null;
		}

		return $this->process->getResult();
	}

	public function getError(): ?array {
		if ($this->process->getProcessStatus() !== ProcessStatus::ERROR) {
			return null;
		}

		return $this->process->getResult();
	}

	public function getStatus(): ProcessStatus {
		return $this->process->getProcessStatus();
	}

	public function id(string $id): self {
		$this->id = $id;
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

	public function repeatable(bool $repeatable = true): self {
		$this->repeatable = $repeatable;
		return $this;
	}

	public function isRepeatable(): bool {
		return $this->repeatable;
	}

	public function require(string $id): self {
		$this->require[] = $id;
		return $this;
	}

	public function getRequire(): array {
		return $this->require;
	}

	public function delay(?ProcessExecutionTime $delay): self {
		$this->delay = $delay;
		return $this;
	}

	public function getDelay(): ?ProcessExecutionTime {
		return $this->delay;
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

	public function async(ProcessExecutionTime $time = ProcessExecutionTime::NOW): self {
		$this->asyncManager->async($time);
		return $this;
	}

	public function import(array $data): void {
		$this->id($data['id'] ?? '');
		$this->token = $data['token'] ?? '';
		$this->name($data['name'] ?? '');
		$this->delay(ProcessExecutionTime::tryFrom($data['delay'] ?? -1));
		$this->require = $data['require'] ?? [];
		$this->blocker($data['blocker'] ?? false);
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'token' => $this->getToken(),
			'name' => $this->getName(),
			'delay' => $this->getDelay(),
			'require' => $this->getRequire(),
			'blocker' => $this->isBlocker()
		];
	}
}

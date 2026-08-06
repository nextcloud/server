<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Console;

use OCP\Console\IOutput;
use OCP\Console\Verbosity;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutputAdapter implements IOutput {
	public function __construct(
		public readonly OutputInterface $output,
		public readonly InputInterface $input,
		public readonly CommandAdapter $commandAdapter,
	) {
	}

	#[Override]
	public function write(iterable|string $messages, bool $newline = false, Verbosity $verbosity = Verbosity::Normal): void {
		$this->output->write($messages, $newline, $verbosity->value);
	}

	#[Override]
	public function writeln(iterable|string $messages, Verbosity $verbosity = Verbosity::Normal): void {
		$this->output->writeln($messages, $verbosity->value);
	}

	#[Override]
	public function isQuiet(): bool {
		return $this->output->isQuiet();
	}

	#[Override]
	public function isVerbose(): bool {
		return $this->output->isVerbose();
	}

	#[Override]
	public function isVeryVerbose(): bool {
		return $this->output->isVeryVerbose();
	}

	#[Override]
	public function isDebug(): bool {
		return $this->output->isDebug();
	}

	#[Override]
	public function writeArrayInOutputFormat(iterable $items, string $prefix = '  - '): void {
		$this->commandAdapter->writeArrayInOutputFormat($this->input, $this->output, $items, $prefix);
	}

	#[Override]
	public function writeTableInOutputFormat(array $items): void {
		$this->commandAdapter->writeTableInOutputFormat($this->input, $this->output, $items);
	}

	#[Override]
	public function writeStreamingTableInOutputFormat(\Iterator $items, int $tableGroupSize): void {
		$this->commandAdapter->writeStreamingTableInOutputFormat($this->input, $this->output, $items, $tableGroupSize);
	}

	#[Override]
	public function setVerbosity(Verbosity $level): void {
		$this->output->setVerbosity($level->value);
	}

	#[Override]
	public function getVerbosity(): Verbosity {
		return Verbosity::from($this->output->getVerbosity());
	}
}

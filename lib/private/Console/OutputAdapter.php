<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Console;

use OCP\Console\IOutput;
use Override;
use Symfony\Component\Console\Output\OutputInterface;

class OutputAdapter implements IOutput {
	public function __construct(
		public readonly OutputInterface $output,
	) {
	}

	#[Override]
	public function write(iterable|string $messages, bool $newline = false): void {
		$this->output->write($messages, $newline);
	}

	#[Override]
	public function writeln(iterable|string $messages): void {
		$this->output->writeln($messages);
	}
}

<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Console;

use OCP\Console\IOutput;
use OCP\Console\Verbosity;
use Override;

/**
 * IOutput implementation that discards everything written to it, for callers
 * that need a default when no real console output is available.
 */
class NullOutput implements IOutput {
	#[Override]
	public function write(iterable|string $messages, bool $newline = false, Verbosity $verbosity = Verbosity::Normal): void {
	}

	#[Override]
	public function writeln(iterable|string $messages, Verbosity $verbosity = Verbosity::Normal): void {
	}

	#[Override]
	public function setVerbosity(Verbosity $level): void {
	}

	#[Override]
	public function getVerbosity(): Verbosity {
		return Verbosity::Normal;
	}

	#[Override]
	public function isQuiet(): bool {
		return false;
	}

	#[Override]
	public function isVerbose(): bool {
		return false;
	}

	#[Override]
	public function isVeryVerbose(): bool {
		return false;
	}

	#[Override]
	public function isDebug(): bool {
		return false;
	}

	#[Override]
	public function writeArrayInOutputFormat(iterable $items, string $prefix = '  - '): void {
	}

	#[Override]
	public function writeTableInOutputFormat(array $items): void {
	}

	#[Override]
	public function writeStreamingTableInOutputFormat(\Iterator $items, int $tableGroupSize): void {
	}

	#[Override]
	public function progressStart(int $max = 0): void {
	}

	#[Override]
	public function progressAdvance(int $step = 1): void {
	}

	#[Override]
	public function progressFinish(): void {
	}

	#[Override]
	public function progressIterate(iterable $iterable, ?int $max = null): iterable {
		yield from $iterable;
	}
}

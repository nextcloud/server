<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Async;

use OC\Async\IBlockInterface;
use OCP\Async\Enum\ProcessExecutionTime;

interface IAsyncProcess {
	public function exec(\Closure $closure, ...$params): IBlockInterface;

	public function invoke(callable $obj, ...$params): IBlockInterface;

	public function call(string $class, ...$params): IBlockInterface;

	public function async(ProcessExecutionTime $time = ProcessExecutionTime::NOW): string;
}

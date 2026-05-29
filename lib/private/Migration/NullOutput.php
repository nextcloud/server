<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Migration;

use OCP\Migration\IOutput;

/**
 * Class NullOutput
 *
 * A simple IOutput that discards all output
 *
 * @package OC\Migration
 */
class NullOutput implements IOutput {
	#[\Override]
	public function debug(string $message): void {
	}

	#[\Override]
	public function info($message): void {
	}

	#[\Override]
	public function warning($message): void {
	}

	#[\Override]
	public function startProgress($max = 0): void {
	}

	#[\Override]
	public function advance($step = 1, $description = ''): void {
	}

	#[\Override]
	public function finishProgress(): void {
	}
}

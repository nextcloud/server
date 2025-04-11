<?php
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
	public function debug(string $message): void {
	}

	public function info($message): void {
	}

	public function warning($message): void {
	}

	public function startProgress($max = 0): void {
	}

	public function advance($step = 1, $description = ''): void {
	}

	public function finishProgress(): void {
	}
}

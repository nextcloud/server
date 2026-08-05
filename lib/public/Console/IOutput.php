<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface IOutput {
	/**
	 * Writes a message to the output.
	 *
	 * @param bool $newline Whether to add a newline
	 * @since 35.0.0
	 */
	public function write(string|iterable $messages, bool $newline = false): void;

	/**
	 * Writes a message to the output and adds a newline at the end.
	 * @since 35.0.0
	 */
	public function writeln(string|iterable $messages): void;
}

<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Interface that lets you output content from a command.
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface IOutput {
	/**
	 * Writes a message to the output.
	 *
	 * @param bool $newline Whether to add a newline
	 * @param Verbosity $verbosity Only write messages if verbosity is set to at least that level.
	 * @since 35.0.0
	 */
	public function write(string|iterable $messages, bool $newline = false, Verbosity $verbosity = Verbosity::Normal): void;

	/**
	 * Writes a message to the output and adds a newline at the end.
	 *
	 * @param Verbosity $verbosity Only write messages if verbosity is set to at least that level.
	 * @since 35.0.0
	 */
	public function writeln(string|iterable $messages, Verbosity $verbosity = Verbosity::Normal): void;

	/**
	 * Sets the verbosity of the output.
	 * @since 35.0.0
	 */
	public function setVerbosity(Verbosity $level): void;

	/**
	 * Gets the current verbosity of the output.
	 * @since 35.0.0
	 */
	public function getVerbosity(): Verbosity;

	/**
	 * Returns whether verbosity is quiet (-q).
	 * @since 35.0.0
	 */
	public function isQuiet() : bool;

	/**
	 * Returns whether verbosity is verbose (-v).
	 * @since 35.0.0
	 */
	public function isVerbose() : bool;

	/**
	 * Returns whether verbosity is very verbose (-vv).
	 * @since 35.0.0
	 */
	public function isVeryVerbose() : bool;

	/**
	 * Returns whether verbosity is debug (-vvv).
	 * @since 35.0.0
	 */
	public function isDebug() : bool;

	/**
	 * Write a list of items in the format specified with --output
	 *
	 * @param array $items
	 * @since 35.0.0
	 */
	public function writeArrayInOutputFormat(iterable $items, string $prefix = '  - '): void;

	/**
	 * Write a multidimensional array of items in the format specified with --output
	 *
	 * @param list<array<string, mixed>> $items
	 * @since 35.0.0
	 */
	public function writeTableInOutputFormat(array $items): void;

	/**
	 * Write a multidimensional iterator of items in the format specified with --output
	 *
	 * @param \Iterator<array<string, mixed>> $items
	 * @since 35.0.0
	 */
	public function writeStreamingTableInOutputFormat(\Iterator $items, int $tableGroupSize): void;
}

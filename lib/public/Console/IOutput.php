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

	/**
	 * Displays a progress bar with a number of steps equal to the argument passed to the method (don't pass any value if the length of the progress bar is unknown):
	 *
	 * ```
	 * // displays a progress bar of unknown length
	 * $output->progressStart();
	 * ```
	 *
	 * ```
	 * // displays a 100-step length progress bar
	 * $output->progressStart(100);
	 * ```
	 * @since 35.0.0
	 */
	public function progressStart(int $max = 0): void;

	/**
	 * Make the progress bar advance the given number of steps (or 1 step if no argument is passed):
	 *
	 * ```
	 * // advances the progress bar 1 step
	 * $output->progressAdvance();
	 * ```
	 *
	 * ```
	 * // advances the progress bar 10 steps
	 * $output->progressAdvance(10);
	 * ```
	 * @since 35.0.0
	 */
	public function progressAdvance(int $step = 1): void;

	/**
	 * Finish the progress bar (filling up all the remaining steps when its length is known):
	 *
	 * ```
	 * $output->progressFinish();
	 * ```
	 * @since 35.0.0
	 */
	public function progressFinish(): void;

	/**
	 * If your progress bar loops over an iterable collection, use the progressIterate() helper:
	 *
	 * ```
	 * $iterable = [1, 2];
	 *
	 * foreach ($output->progressIterate($iterable) as $value) {
	 *     // ... do some work
	 * }
	 * ```
	 * @template TKey
	 * @template TValue
	 *
	 * @param iterable<TKey, TValue> $iterable
	 * @param int|null $max Number of steps to complete the bar (0 if indeterminate), if null it will be inferred from $iterable
	 *
	 * @return iterable<TKey, TValue>
	 * @since 35.0.0
	 */
	public function progressIterate(iterable $iterable, ?int $max = null): iterable;
}

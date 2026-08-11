<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console;

use InvalidArgumentException;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Interface that lets you retrieve the input from a command.
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface IInput {
	/**
	 * Returns all the given arguments merged with the default values.
	 *
	 * @return array<string|bool|int|float|array|null>
	 * @since 35.0.0
	 */
	public function getArguments(): array;

	/**
	 * Returns the argument value for a given argument name.
	 *
	 * @throws InvalidArgumentException When argument given doesn't exist
	 * @since 35.0.0
	 */
	public function getArgument(string $name): string|bool|int|float|array|null;

	/**
	 * Returns true if an argument exists by name or position.
	 * @since 35.0.0
	 */
	public function hasArgument(string $name): bool;

	/**
	 * Returns all the given options merged with the default values.
	 *
	 * @return array<string|bool|int|float|array|null>
	 * @since 35.0.0
	 */
	public function getOptions(): array;

	/**
	 * Returns the option value for a given option name.
	 *
	 * @throws InvalidArgumentException When option given doesn't exist
	 * @since 35.0.0
	 */
	public function getOption(string $name): string|bool|int|float|array|null;

	/**
	 * Returns true if an option exists by name.
	 * @since 35.0.0
	 */
	public function hasOption(string $name): bool;

	/**
	 * Asks the user to provide some value.
	 *
	 * ```
	 * $input->ask('What is your name?');
	 * ```
	 *
	 * You can pass the default value as the second argument so the user can hit the <Enter> key to select that value:
	 *
	 * ```
	 * $input->ask('Where are you from?', 'United States');
	 * ```
	 *
	 * In case you need to validate the given value, pass a callback validator as the third argument:
	 *
	 * ```
	 * $input->ask('Number of workers to start', '1', function (string $number): int {
	 *     if (!is_numeric($number)) {
	 *         throw new \RuntimeException('You must type a number.');
	 *     }
	 *
	 *     return (int) $number;
	 * });
	 * ```
	 *
	 * @param callable(string):mixed|null $validator
	 * @since 35.0.0
	 */
	public function ask(string $question, ?string $default = null, ?callable $validator = null): mixed;

	/**
	 * Ask the user to provide some value but the user's input will be hidden, and it cannot define a default value.
	 *
	 * Use it when asking for sensitive information:
	 *
	 * ```
	 * $input->askHidden('What is your password?');
	 * ```
	 *
	 * In case you need to validate the given value, pass a callback validator as the second argument:
	 *
	 * ```
	 * $input->askHidden('What is your password?', function (string $password): string {
	 *     if (empty($password)) {
	 *         throw new \RuntimeException('Password cannot be empty.');
	 *     }
	 *
	 *     return $password;
	 * });
	 * ```
	 *
	 * @param callable(string):mixed|null $validator
	 * @since 35.0.0
	 */
	public function askHidden(string $question, ?callable $validator = null): mixed;

	/**
	 * Ask a Yes/No question to the user, and it only returns true or false:
	 *
	 * ```
	 * $input->confirm('Restart the web server?');
	 * ```
	 *
	 * You can pass the default value as the second argument so the user can hit the <Enter> key to select that value:
	 *
	 * ```
	 * $input->confirm('Restart the web server?', true);
	 * ```
	 *
	 * @param string $question
	 * @since 35.0.0
	 */
	public function confirm(string $question, bool $default = true): bool;

	/**
	 * Ask a question whose answer is constrained to the given list of valid answers:
	 *
	 * ```
	 * $input->choice('Select the queue to analyze', ['queue1', 'queue2', 'queue3']);
	 * ```
	 *
	 * You can pass the default value as the third argument so the user can hit the <Enter> key to select that value:
	 *
	 * ```
	 * $input->choice('Select the queue to analyze', ['queue1', 'queue2', 'queue3'], 'queue1');
	 * ```
	 *
	 * Choice questions display both the choice value and a numeric index, which starts from 0 by default. To use custom indices, pass an array with custom numeric keys as the choice values:
	 *
	 * ```
	 * $input->choice('Select the queue to analyze', [5 => 'queue1', 6 => 'queue2', 7 => 'queue3']);
	 * ```
	 *
	 * Finally, you can allow users to select multiple choices. To do so, users must separate each choice with a comma (e.g. typing 1, 2 will select choice 1 and 2):
	 *
	 * ```
	 * $input->choice('Select the queue to analyze', ['queue1', 'queue2', 'queue3'], multiSelect: true);
	 * ```
	 *
	 * @param array<string> $choices
	 * @since 35.0.0
	 */
	public function choice(string $question, array $choices, mixed $default = null, bool $multiSelect = false): mixed;
}

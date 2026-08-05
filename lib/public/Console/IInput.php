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
}

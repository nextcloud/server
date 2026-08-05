<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console;

use InvalidArgumentException;
use OCP\AppFramework\Attribute\Consumable;

#[Consumable(since: '35.0.0')]
interface IInput {
	/**
	 * Returns all the given arguments merged with the default values.
	 *
	 * @return array<string|bool|int|float|array|null>
	 */
	public function getArguments(): array;

	/**
	 * Returns the argument value for a given argument name.
	 *
	 * @return string|bool|int|float|array|null
	 *
	 * @throws InvalidArgumentException When argument given doesn't exist
	 */
	public function getArgument(string $name): string|bool|int|float|array|null;
}

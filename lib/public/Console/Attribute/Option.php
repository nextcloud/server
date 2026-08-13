<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console\Attribute;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Represents a console command <option> definition.
 *
 * Can be used in the parameters of the invoke method.
 *
 * ```
 * #[AsCommand(name: 'app:user:created')
 * class CreateUserCommand {
 *     public function __invoke(
 *         #[Option(description: "The username of the user")] string $userId,
 *         IOutput $output,
 *     ): ExitCode {
 *         // ...
 *         return ExitCode::Success;
 *     }
 * }
 * ```
 *
 * Or on methods parameters:
 *
 * ```
 * class UserCommands {
 *     #[AsCommand('app:user:create')]
 *     public function create(
 *         #[Option(description: "The username of the user")] string $userId,
 *         IOutput $output,
 *     ): ExitCode {
 *         // ...
 *
 *         return ExitCode::Success;
 *     }
 *
 *     #[AsCommand('app:user:delete')]
 *     public function delete(
 *         #[Option(description: "The username of the user")] string $userId,
 *         IOutput $output,
 *     ): ExitCode {
 *         // ...
 *
 *         return ExitCode::Success;
 *     }
 * }
 * ```
 *
 * @since 35.0.0
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
#[Consumable(since: '35.0.0')]
final class Option {
	/**
	 * If unset, the `name` value will be inferred from the parameter definition.
	 *
	 * To declare an option whose value is itself optional (e.g. "--foo" alone returns
	 * `true`, "--foo=bar" returns `'bar'`, and omitting the option returns `false`),
	 * type the parameter as a union of `bool` with `string`, `int` or `float`
	 * (e.g. `string|bool $foo = false`).
	 *
	 * @param string $description The description of the option, displayed with the help page
	 * @param string $name The name of the option
	 * @param array|string|null $shortcut The shortcuts, can be null, a string of shortcuts delimited by | or an array of shortcuts
	 * @param array|\Closure $suggestedValues An array or a closure that provides suggested values for the option.
	 * @since 35.0.0
	 */
	public function __construct(
		public string $description = '',
		public string $name = '',
		public array|string|null $shortcut = null,
		public array|\Closure $suggestedValues = [],
	) {
	}
}

<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console\Attribute;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Represents a console command <argument> definition.
 *
 * Can be used in the parameters of the invoke method.
 *
 * ```
 * #[AsCommand(name: 'app:user:created')
 * class CreateUserCommand {
 *     public function __invoke(
 *         #[Argument(description: "The username of the user")] string $userId,
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
 *         #[Argument(description: "The username of the user")] string $userId,
 *         IOutput $output,
 *     ): ExitCode {
 *         // ...
 *
 *         return ExitCode::Success;
 *     }
 *
 *     #[AsCommand('app:user:delete')]
 *     public function delete(
 *         #[Argument(description: "The username of the user")] string $userId,
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
final class Argument {
	/**
	 * If unset, the `name` value will be inferred from the parameter definition.
	 *
	 * @param string $description The description of the argument, displayed with the help page
	 * @param string $name The name of the argument
	 * @since 35.0.0
	 */
	public function __construct(
		public string $description = '',
		public string $name = '',
	) {
	}
}

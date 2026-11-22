<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console\Attribute;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Attribute to declare that the class is a command.
 *
 * Can be used either on a class:
 *
 * ```
 * #[AsCommand(
 *      name: 'app:create-user',
 *      // this short description is shown when running "occ list"
 *      description: 'Creates a new user.',
 *      // this is shown when running the command with the "--help" option
 *      help: 'This command allows you to create a user...',
 *      // this allows you to show one or more usage examples (no need to add the command name)
 *      usages: ['bob', 'alice --as-admin'],
 * )]
 * class CreateUserCommand {
 *     public function __invoke(): ExitCode {
 *         // ...
 *         return ExitCode::Success;
 *     }
 * }
 * ```
 *
 * Or on methods:
 *
 * ```
 * class UserCommands {
 *     #[AsCommand('app:user:create')]
 *     public function create(IOutput $output): ExitCode {
 *         // ...
 *
 *         return ExitCode::Success;
 *     }
 *
 *     #[AsCommand('app:user:delete')]
 *     public function delete(IOutput $output): int
 *     {
 *         // ...
 *
 *         return ExitCode::Success;
 *     }
 * }
 * ```
 *
 * @since 35.0.0
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
#[Consumable(since: '35.0.0')]
final class AsCommand {
	/**
	 * @param string $name The name of the command, used when calling it (i.e. "cache:clear")
	 * @param string|null $description The description of the command, displayed with the help page
	 * @param bool $hidden If true, the command won't be shown when listing all the available commands, but it can still be run as any other command
	 * @param string|null $help The help content of the command, displayed with the help page
	 * @param string[] $usages The list of usage examples, displayed with the help page
	 * @param bool $supportsOutputFormat If true, the command can output the result in JSON
	 * @since 35.0.0
	 */
	public function __construct(
		public string $name,
		public ?string $description = null,
		public ?string $help = null,
		public array $usages = [],
		public bool $hidden = false,
		public bool $supportsOutputFormat = false,
	) {
	}
}

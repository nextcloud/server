<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console;

/**
 * Output format for a command.
 *
 * The value can be queried in the __invoke method of a command.
 *
 * ```
 * #[AsCommand(
 *     name: 'app:user:created',
 *     supportsOutputFormat: true,
 * )
 * class CreateUserCommand {
 *     public function __invoke(
 *         #[Option(description: "The username of the user")] string $userId,
 *         IOutput $output,
 *         OutputFormat $outputFormat,
 *     ): ExitCode {
 *         // ...
 *         return ExitCode::Success;
 *     }
 * }
 *
 * @since 35.0.0
 */
enum OutputFormat: string {
	/** @since 35.0.0 */
	case Plain = 'plain';
	/** @since 35.0.0 */
	case Json = 'json';
	/** @since 35.0.0 */
	case JsonPretty = 'json_pretty';
}

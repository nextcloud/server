<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Console\Fixtures;

use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

#[AsCommand(
	name: 'test:completion-fixture',
	description: 'Fixture command for CommandAdapter completion tests',
	supportsOutputFormat: true,
)]
class CompletionFixtureCommand {
	public function __construct(
		private readonly ?FixtureDependency $dependency = null,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Argument(description: 'A dynamically suggested argument', suggestedValues: [self::class, 'suggestDynamically'])]
		string $dynamic,
		#[Argument(description: 'An instance-resolved dynamically suggested argument', suggestedValues: [self::class, 'suggestFromInstance'])]
		string $instanceBased,
		#[Argument(description: 'A statically suggested argument', suggestedValues: ['foo', 'bar'])]
		string $static,
		#[Option(description: 'A statically suggested option', suggestedValues: ['x', 'y'])]
		string $option = '',
	): ExitCode {
		return ExitCode::Success;
	}

	public static function suggestDynamically(string $currentWord): array {
		return array_values(array_filter(['alpha', 'beta', 'gamma'], static fn (string $v) => str_starts_with($v, $currentWord)));
	}

	public function suggestFromInstance(string $currentWord): array {
		return [$this->dependency?->getValue() ?? 'no-dependency'];
	}
}

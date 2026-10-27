<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Console;

use OCP\Console\IInput;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InputAdapter implements IInput {
	public function __construct(
		public readonly InputInterface $input,
		public readonly SymfonyStyle $symfonyStyle,
	) {
	}

	#[Override]
	public function getArguments(): array {
		return $this->input->getArguments();
	}

	#[Override]
	public function getArgument(string $name): string|bool|int|float|array|null {
		return $this->input->getArgument($name);
	}

	#[Override]
	public function hasArgument(string $name): bool {
		return $this->input->hasArgument($name);
	}

	#[Override]
	public function getOptions(): array {
		return $this->input->getOptions();
	}

	#[Override]
	public function getOption(string $name): string|bool|int|float|array|null {
		return $this->input->getOption($name);
	}

	#[Override]
	public function hasOption(string $name): bool {
		return $this->input->hasOption($name);
	}

	#[Override]
	public function ask(string $question, ?string $default = null, ?callable $validator = null): mixed {
		return $this->symfonyStyle->ask($question, $default, $validator);
	}

	#[Override]
	public function askHidden(string $question, ?callable $validator = null): mixed {
		return $this->symfonyStyle->askHidden($question, $validator);
	}

	#[Override]
	public function confirm(string $question, bool $default = true): bool {
		return $this->symfonyStyle->confirm($question, $default);
	}

	#[Override]
	public function choice(string $question, array $choices, mixed $default = null, bool $multiSelect = false): mixed {
		return $this->symfonyStyle->choice($question, $choices, $default, $multiSelect);
	}
}

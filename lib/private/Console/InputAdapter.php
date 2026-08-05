<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Console;

use OCP\Console\IInput;
use Override;
use Symfony\Component\Console\Input\InputInterface;

class InputAdapter implements IInput {
	public function __construct(
		public readonly InputInterface $input,
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
}

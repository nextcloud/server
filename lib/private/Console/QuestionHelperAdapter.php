<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Console;

use OCP\Console\IQuestionHelper;
use Override;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QuestionHelperAdapter implements IQuestionHelper {
	public function __construct(
		public readonly InputInterface $input,
		public readonly OutputInterface $output,
		public readonly QuestionHelper $questionHelper,
	) {
	}

	#[Override]
	public function ask(mixed $question): mixed {
		return $this->questionHelper->ask($this->input, $this->output, $question);
	}
}

<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Testing\Provider;

use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IProvider;
use OCP\TextProcessing\ITaskType;

/** @template-implements IProvider<FreePromptTaskType|ITaskType> */
class FakeTextProcessingProvider implements IProvider {

	public function getName(): string {
		return 'Fake text processing provider (asynchronous)';
	}

	public function process(string $prompt): string {
		return strrev($prompt) . ' (done with FakeTextProcessingProvider)';
	}

	public function getTaskType(): string {
		return FreePromptTaskType::class;
	}
}

<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Testing\Provider;

use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IProviderWithExpectedRuntime;
use OCP\TextProcessing\ITaskType;

/**
 * @template-implements IProviderWithExpectedRuntime<FreePromptTaskType|ITaskType>
 */
class FakeTextProcessingProviderSync implements IProviderWithExpectedRuntime {

	public function getName(): string {
		return 'Fake text processing provider (synchronous)';
	}

	public function process(string $prompt): string {
		return $this->mb_strrev($prompt) . ' (done with FakeTextProcessingProviderSync)';
	}

	public function getTaskType(): string {
		return FreePromptTaskType::class;
	}

	public function getExpectedRuntime(): int {
		return 1;
	}

	/**
	 * Reverse a miltibyte string.
	 *
	 * @param string $string The string to be reversed.
	 * @return string The reversed string
	 */
	private function mb_strrev(string $string): string {
		$chars = mb_str_split($string, 1);
		return implode('', array_reverse($chars));
	}
}

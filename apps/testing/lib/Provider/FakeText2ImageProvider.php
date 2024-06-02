<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Testing\Provider;

use OCP\TextToImage\IProvider;

class FakeText2ImageProvider implements IProvider {

	public function getName(): string {
		return 'Fake Text2Image provider';
	}

	public function generate(string $prompt, array $resources): void {
		foreach ($resources as $resource) {
			$read = fopen(__DIR__ . '/../../img/logo.png', 'r');
			stream_copy_to_stream($read, $resource);
			fclose($read);
		}
	}

	public function getExpectedRuntime(): int {
		return 1;
	}

	public function getId(): string {
		return 'testing-fake-text2image-provider';
	}
}

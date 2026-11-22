<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http\IOutput;
use OCP\AppFramework\Http\StreamTraversableResponse;

class StreamTraversableResponseTest extends \Test\TestCase {

	protected function setUp(): void {
		parent::setUp();
	}

	public function testConstructor() {
		$generator = function () {
			yield 'chunk1';
			yield 'chunk2';
		};
		$response = new StreamTraversableResponse($generator(), 200, ['Content-Type' => 'text/plain']);

		$headers = $response->getHeaders();
		$this->assertEquals('text/plain', $headers['Content-Type']);
		$this->assertEquals(200, $response->getStatus());
	}

	public function testCallback() {
		$count = 0;
		$generator = function () use (&$count) {
			$count++;
			yield 'chunk1';
			$count++;
			yield 'chunk2';
		};
		$response = new StreamTraversableResponse($generator(), 200, ['Content-Type' => 'text/plain']);
		$output = $this->createMock(IOutput::class);
		$output->expects($this->exactly(2))
			->method('setOutput')
			->with($this->callback(function ($chunk) {
				return in_array($chunk, ['chunk1', 'chunk2'], true);
			}));

		$response->callback($output);
		$this->assertEquals($count, 2);
	}

}

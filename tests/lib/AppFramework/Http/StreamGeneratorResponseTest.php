<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http\StreamGeneratorResponse;

class StreamGeneratorResponseTest extends \Test\TestCase {

	protected function setUp(): void {
		parent::setUp();
	}

	public function testConstructor() {
		$generator = function () {
			yield 'chunk1';
			yield 'chunk2';
		};
		$response = new StreamGeneratorResponse($generator(), 'text/plain');

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
		$response = new StreamGeneratorResponse($generator(), 'text/plain');
		$output = $this->createMock(\OCP\AppFramework\Http\IOutput::class);
	
		$response->callback($output);
		$this->assertEquals($count, 2);
	}

}

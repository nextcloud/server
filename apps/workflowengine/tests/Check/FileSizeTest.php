<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\Tests\Check;

use OCA\WorkflowEngine\Check\FileSize;
use OCP\IL10N;
use OCP\IRequest;
use Test\TestCase;

class FileSizeTest extends TestCase {
	public static function dataExecuteCheck(): array {
		// Expected results: less, !greater, !less, greater.
		return [
			'missing PUT headers'
				=> ['PUT', '', '', '5 B', [false, false, false, false]],
			'missing POST headers'
				=> ['POST', '', '', '5 B', [false, false, false, false]],
			'missing size at zero threshold'
				=> ['PUT', '', '', '0 B', [false, false, false, false]],
			'invalid total length does not fall back'
				=> ['PUT', 'invalid', '3', '5 B', [false, false, false, false]],
			'invalid content length'
				=> ['PUT', '', 'invalid', '5 B', [false, false, false, false]],
			'no content length fallback for MOVE'
				=> ['MOVE', '', '3', '5 B', [false, false, false, false]],
			'total length takes precedence'
				=> ['PUT', '7', '3', '5 B', [false, false, true, true]],
			'PUT content length fallback'
				=> ['PUT', '', '3', '5 B', [true, true, false, false]],
			'POST content length fallback'
				=> ['POST', '', '3', '5 B', [true, true, false, false]],
			'size equals threshold'
				=> ['PUT', '5', '', '5 B', [false, true, true, false]],
			'zero total length'
				=> ['PUT', '0', '7', '5 B', [true, true, false, false]],
			'zero content length'
				=> ['PUT', '', '0', '0 B', [false, true, true, false]],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataExecuteCheck')]
	public function testExecuteCheck(
		string $method,
		string $totalLength,
		string $contentLength,
		string $value,
		array $expected,
	): void {
		$request = $this->createMock(IRequest::class);
		$usesFallback = $totalLength === '' && in_array($method, ['POST', 'PUT'], true);
		$request->expects($this->exactly($usesFallback ? 2 : 1))
			->method('getHeader')
			->willReturnMap([
				['OC-Total-Length', $totalLength],
				['Content-Length', $contentLength],
			]);
		$request->expects($totalLength === '' ? $this->once() : $this->never())
			->method('getMethod')
			->willReturn($method);

		$check = new FileSize($this->createMock(IL10N::class), $request);

		// Repeated checks must reuse the cached size, including false for unknown sizes.
		for ($run = 0; $run < 2; $run++) {
			foreach (['less', '!greater', '!less', 'greater'] as $index => $operator) {
				$this->assertSame($expected[$index], $check->executeCheck($operator, $value), $operator);
			}
		}
	}
}

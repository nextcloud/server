<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\ContentDisposition;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class ContentDispositionTest extends TestCase {
	public static function dataAttachment(): array {
		return [
			'no name' => [null, 'attachment'],
			'empty name' => ['', 'attachment'],
			'dot' => ['.', 'attachment'],
			'dot dot' => ['..', 'attachment'],
			'plain name' => ['report.pdf', 'attachment; filename="report.pdf"; filename*=UTF-8\'\'report.pdf'],
			'quotes and spaces' => ['a "b".txt', 'attachment; filename="a%20%22b%22.txt"; filename*=UTF-8\'\'a%20%22b%22.txt'],
			'non ascii' => ['Grüße.txt', 'attachment; filename="Gr%C3%BC%C3%9Fe.txt"; filename*=UTF-8\'\'Gr%C3%BC%C3%9Fe.txt'],
			'header injection' => ["a\r\nX-Evil: 1.txt", 'attachment; filename="aX-Evil%3A%201.txt"; filename*=UTF-8\'\'aX-Evil%3A%201.txt'],
		];
	}

	#[DataProvider('dataAttachment')]
	public function testAttachment(?string $filename, string $expected): void {
		$this->assertSame($expected, ContentDisposition::attachment($filename));
	}
}

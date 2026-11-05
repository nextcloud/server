<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Utils;

use OC\Files\Utils\PathHelper;
use Test\TestCase;

class PathHelperTest extends TestCase {
	public static function normalizePathData(): array {
		return [
			['/', ''],
			['/', '/'],
			['/foo/bar', 'foo//bar/'],
			['/foo/bar', '\\foo\\bar'],
			['/foobar', "/foo\0bar"],
			["/B\u{00FC}cher", "/Bu\u{0308}cher"],
			["/B\u{00FC}cher", "/B\u{00FC}cher"],
			["/a\xFFb", "/a\xFFb"],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('normalizePathData')]
	public function testNormalizePath(string $expected, string $path): void {
		$this->assertSame($expected, PathHelper::normalizePath($path));
	}
}

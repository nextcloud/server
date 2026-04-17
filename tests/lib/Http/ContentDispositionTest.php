<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\Http;

use OC\Http\ContentDisposition;
use Test\TestCase;

class ContentDispositionTest extends TestCase {

	#[\PHPUnit\Framework\Attributes\DataProvider('provideAttachmentCases')]
	public function testAttachment(string $filename, string $expected): void {
		$this->assertEquals($expected, ContentDisposition::make('attachment', $filename));
	}

	public static function provideAttachmentCases(): array {
		return [
			'simple ASCII' => [
				'report.pdf',
				'attachment; filename="report.pdf"',
			],
			'ASCII with spaces' => [
				'my report.pdf',
				'attachment; filename="my report.pdf"',
			],
			'non-ASCII produces fallback and filename*' => [
				'Ässembly Ünits.pdf',
				"attachment; filename=\"_ssembly _nits.pdf\"; filename*=utf-8''%C3%84ssembly%20%C3%9Cnits.pdf",
			],
			'CJK filename' => [
				'テスト.txt',
				"attachment; filename=\"___.txt\"; filename*=utf-8''%E3%83%86%E3%82%B9%E3%83%88.txt",
			],
			'emoji filename' => [
				'🎉party.txt',
				"attachment; filename=\"_party.txt\"; filename*=utf-8''%F0%9F%8E%89party.txt",
			],
			'percent in filename' => [
				'100% done.txt',
				"attachment; filename=\"100_ done.txt\"; filename*=utf-8''100%25%20done.txt",
			],
			'quotes in filename' => [
				'say "hello".txt',
				"attachment; filename=\"say \\\"hello\\\".txt\"",
			],
		];
	}

	public function testInline(): void {
		$result = ContentDisposition::make('inline', 'image.png');
		$this->assertEquals('inline; filename="image.png"', $result);
	}

	public function testInlineNonAscii(): void {
		$result = ContentDisposition::make('inline', 'Ürlaub.jpg');
		$this->assertStringContainsString('inline', $result);
		$this->assertStringContainsString("filename*=utf-8''", $result);
		$this->assertStringContainsString('filename="_rlaub.jpg"', $result);
	}
}

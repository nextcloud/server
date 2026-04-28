<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http\DownloadResponse;

class ChildDownloadResponse extends DownloadResponse {
};


class DownloadResponseTest extends \Test\TestCase {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();
	}

	public function testHeaders(): void {
		$response = new ChildDownloadResponse('file', 'content');
		$headers = $response->getHeaders();

		$this->assertEquals('attachment; filename=file', $headers['Content-Disposition']);
		$this->assertEquals('content', $headers['Content-Type']);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('filenameEncodingProvider')]
	public function testFilenameEncoding(string $input, string $expectedDisposition): void {
		$response = new ChildDownloadResponse($input, 'content');
		$headers = $response->getHeaders();

		$this->assertEquals($expectedDisposition, $headers['Content-Disposition']);
	}

	public static function filenameEncodingProvider(): array {
		return [
			['TestName.txt', 'attachment; filename=TestName.txt'],
			['A "Quoted" Filename.txt', 'attachment; filename="A \"Quoted\" Filename.txt"'],
			['A "Quoted" Filename.txt', 'attachment; filename="A \"Quoted\" Filename.txt"'],
			['A "Quoted" Filename With A Backslash \\.txt', 'attachment; filename="A \"Quoted\" Filename With A Backslash -.txt"'],
			['A "Very" Weird Filename \ / & <> " >\'""""\.text', 'attachment; filename="A \"Very\" Weird Filename - - & <> \" >\'\"\"\"\"-.text"'],
			['\\\\\\\\\\\\', 'attachment; filename=------'],
		];
	}

	public function testSpecialCharactersInFilename(): void {
		$filename = 'document "draft" with; special&chars.pdf';
		$response = new ChildDownloadResponse($filename, 'application/pdf');
		$headers = $response->getHeaders();

		$this->assertEquals(
			'attachment; filename="document \"draft\" with; special&chars.pdf"',
			$headers['Content-Disposition']
		);
	}
}

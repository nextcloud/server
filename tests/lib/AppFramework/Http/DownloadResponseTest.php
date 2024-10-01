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
	protected function setUp(): void {
		parent::setUp();
	}

	public function testHeaders(): void {
		$response = new ChildDownloadResponse('file', 'content');
		$headers = $response->getHeaders();

		$this->assertEquals('attachment; filename="file"', $headers['Content-Disposition']);
		$this->assertEquals('content', $headers['Content-Type']);
	}

	/**
	 * @dataProvider filenameEncodingProvider
	 */
	public function testFilenameEncoding(string $input, string $expected): void {
		$response = new ChildDownloadResponse($input, 'content');
		$headers = $response->getHeaders();

		$this->assertEquals('attachment; filename="' . $expected . '"', $headers['Content-Disposition']);
	}

	public function filenameEncodingProvider() : array {
		return [
			['TestName.txt', 'TestName.txt'],
			['A "Quoted" Filename.txt', 'A \\"Quoted\\" Filename.txt'],
			['A "Quoted" Filename.txt', 'A \\"Quoted\\" Filename.txt'],
			['A "Quoted" Filename With A Backslash \\.txt', 'A \\"Quoted\\" Filename With A Backslash \\\\.txt'],
			['A "Very" Weird Filename \ / & <> " >\'""""\.text', 'A \\"Very\\" Weird Filename \\\\ / & <> \\" >\'\\"\\"\\"\\"\\\\.text'],
			['\\\\\\\\\\\\', '\\\\\\\\\\\\\\\\\\\\\\\\'],
		];
	}
}

<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http\DownloadResponse;

class ChildDownloadResponse extends DownloadResponse {
};


class DownloadResponseTest extends \Test\TestCase {
	protected function setUp(): void {
		parent::setUp();
	}

	public function testHeaders() {
		$response = new ChildDownloadResponse('file', 'content');
		$headers = $response->getHeaders();

		$this->assertEquals('attachment; filename="file"', $headers['Content-Disposition']);
		$this->assertEquals('content', $headers['Content-Type']);
	}

	/**
	 * @dataProvider filenameEncodingProvider
	 */
	public function testFilenameEncoding(string $input, string $expected) {
		$response = new ChildDownloadResponse($input, 'content');
		$headers = $response->getHeaders();

		$this->assertEquals('attachment; filename="'.$expected.'"', $headers['Content-Disposition']);
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

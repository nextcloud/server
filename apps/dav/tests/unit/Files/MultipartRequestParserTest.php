<?php
/**
 * @copyright Copyright (c) 2021, Louis Chemineau <louis@chmn.me>
 *
 * @author Louis Chemineau <louis@chmn.me>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\unit\DAV;

use Test\TestCase;
use \OCA\DAV\BulkUpload\MultipartRequestParser;

class MultipartRequestParserTest extends TestCase {
	private function getValidBodyObject() {
		return [
			[
				"headers" => [
					"Content-Length" => 7,
					"X-File-MD5" => "4f2377b4d911f7ec46325fe603c3af03",
					"X-File-Path" => "/coucou.txt"
				],
				"content" => "Coucou\n"
			]
		];
	}

	private function getMultipartParser(array $parts, array $headers = [], string $boundary = "boundary_azertyuiop"): MultipartRequestParser {
		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();

		$headers = array_merge(['Content-Type' => 'multipart/related; boundary='.$boundary], $headers);
		$request->expects($this->any())
			->method('getHeader')
			->willReturnCallback(function (string $key) use (&$headers) {
				return $headers[$key];
			});

		$body = "";
		foreach ($parts as $part) {
			$body .= '--'.$boundary."\r\n";

			foreach ($part['headers'] as $headerKey => $headerPart) {
				$body .= $headerKey.": ".$headerPart."\r\n";
			}

			$body .= "\r\n";
			$body .= $part['content']."\r\n";
		}

		$body .= '--'.$boundary."--";

		$stream = fopen('php://temp','r+');
		fwrite($stream, $body);
		rewind($stream);

		$request->expects($this->any())
			->method('getBody')
			->willReturn($stream);

		return new MultipartRequestParser($request);
	}


	/**
	 * Test validation of the request's body type
	 */
	public function testBodyTypeValidation() {
		$bodyStream = "I am not a stream, but pretend to be";
		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$request->expects($this->any())
			->method('getBody')
			->willReturn($bodyStream);

		$this->expectExceptionMessage('Body should be of type resource');
		new MultipartRequestParser($request);
	}

	/**
	 * Test with valid request.
	 * - valid boundary
	 * - valid md5 hash
	 * - valid content-length
	 * - valid file content
	 * - valid file path
	 */
	public function testValidRequest() {
		$multipartParser = $this->getMultipartParser(
			$this->getValidBodyObject()
		);

		[$headers, $content] = $multipartParser->parseNextPart();

		$this->assertSame((int)$headers["content-length"], 7, "Content-Length header should be the same as provided.");
		$this->assertSame($headers["x-file-md5"], "4f2377b4d911f7ec46325fe603c3af03", "X-File-MD5 header should be the same as provided.");
		$this->assertSame($headers["x-file-path"], "/coucou.txt", "X-File-Path header should be the same as provided.");

		$this->assertSame($content, "Coucou\n", "Content should be the same");
	}

	/**
	 * Test with invalid md5 hash.
	 */
	public function testInvalidMd5Hash() {
		$bodyObject = $this->getValidBodyObject();
		$bodyObject["0"]["headers"]["X-File-MD5"] = "f2377b4d911f7ec46325fe603c3af03";
		$multipartParser = $this->getMultipartParser(
			$bodyObject
		);

		$this->expectExceptionMessage('Computed md5 hash is incorrect.');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with a null md5 hash.
	 */
	public function testNullMd5Hash() {
		$bodyObject = $this->getValidBodyObject();
		unset($bodyObject["0"]["headers"]["X-File-MD5"]);
		$multipartParser = $this->getMultipartParser(
			$bodyObject
		);

		$this->expectExceptionMessage('The X-File-MD5 header must not be null.');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with a null Content-Length.
	 */
	public function testNullContentLength() {
		$bodyObject = $this->getValidBodyObject();
		unset($bodyObject["0"]["headers"]["Content-Length"]);
		$multipartParser = $this->getMultipartParser(
			$bodyObject
		);

		$this->expectExceptionMessage('The Content-Length header must not be null.');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with a lower Content-Length.
	 */
	public function testLowerContentLength() {
		$bodyObject = $this->getValidBodyObject();
		$bodyObject["0"]["headers"]["Content-Length"] = 6;
		$multipartParser = $this->getMultipartParser(
			$bodyObject
		);

		$this->expectExceptionMessage('Computed md5 hash is incorrect.');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with a higher Content-Length.
	 */
	public function testHigherContentLength() {
		$bodyObject = $this->getValidBodyObject();
		$bodyObject["0"]["headers"]["Content-Length"] = 8;
		$multipartParser = $this->getMultipartParser(
			$bodyObject
		);

		$this->expectExceptionMessage('Computed md5 hash is incorrect.');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with wrong boundary in body.
	 */
	public function testWrongBoundary() {
		$bodyObject = $this->getValidBodyObject();
		$multipartParser = $this->getMultipartParser(
			$bodyObject,
			['Content-Type' => 'multipart/related; boundary=boundary_poiuytreza']
		);

		$this->expectExceptionMessage('Boundary not found where it should be.');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with no boundary in request headers.
	 */
	public function testNoBoundaryInHeader() {
		$bodyObject = $this->getValidBodyObject();
		$this->expectExceptionMessage('Error while parsing boundary in Content-Type header.');
		$this->getMultipartParser(
			$bodyObject,
			['Content-Type' => 'multipart/related']
		);
	}

	/**
	 * Test with no boundary in the request's headers.
	 */
	public function testNoBoundaryInBody() {
		$bodyObject = $this->getValidBodyObject();
		$multipartParser = $this->getMultipartParser(
			$bodyObject,
			['Content-Type' => 'multipart/related; boundary=boundary_azertyuiop'],
			''
		);

		$this->expectExceptionMessage('Boundary not found where it should be.');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with a boundary with quotes in the request's headers.
	 */
	public function testBoundaryWithQuotes() {
		$bodyObject = $this->getValidBodyObject();
		$multipartParser = $this->getMultipartParser(
			$bodyObject,
			['Content-Type' => 'multipart/related; boundary="boundary_azertyuiop"'],
		);

		$multipartParser->parseNextPart();

		// Dummy assertion, we just want to test that the parsing works.
		$this->assertTrue(true);
	}

	/**
	 * Test with a wrong Content-Type in the request's headers.
	 */
	public function testWrongContentType() {
		$bodyObject = $this->getValidBodyObject();
		$this->expectExceptionMessage('Content-Type must be multipart/related');
		$this->getMultipartParser(
			$bodyObject,
			['Content-Type' => 'multipart/form-data; boundary="boundary_azertyuiop"'],
		);
	}

	/**
	 * Test with a wrong key after the content type in the request's headers.
	 */
	public function testWrongKeyInContentType() {
		$bodyObject = $this->getValidBodyObject();
		$this->expectExceptionMessage('Boundary is invalid');
		$this->getMultipartParser(
			$bodyObject,
			['Content-Type' => 'multipart/related; wrongkey="boundary_azertyuiop"'],
		);
	}

	/**
	 * Test with a null Content-Type in the request's headers.
	 */
	public function testNullContentType() {
		$bodyObject = $this->getValidBodyObject();
		$this->expectExceptionMessage('Content-Type can not be null');
		$this->getMultipartParser(
			$bodyObject,
			['Content-Type' => null],

		);
	}
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\Files;

use OCA\DAV\BulkUpload\MultipartRequestParser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\HTTP\RequestInterface;
use Test\TestCase;

class MultipartRequestParserTest extends TestCase {

	protected LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->logger = $this->createMock(LoggerInterface::class);
	}

	private static function getValidBodyObject(): array {
		return [
			[
				'headers' => [
					'Content-Length' => 7,
					'X-File-MD5' => '4f2377b4d911f7ec46325fe603c3af03',
					'OC-Checksum' => 'md5:4f2377b4d911f7ec46325fe603c3af03',
					'X-File-Path' => '/coucou.txt'
				],
				'content' => "Coucou\n"
			]
		];
	}

	private function getMultipartParser(array $parts, array $headers = [], string $boundary = 'boundary_azertyuiop'): MultipartRequestParser {
		/** @var RequestInterface&MockObject $request */
		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$headers = array_merge(['Content-Type' => 'multipart/related; boundary=' . $boundary], $headers);
		$request->expects($this->any())
			->method('getHeader')
			->willReturnCallback(function (string $key) use (&$headers) {
				return $headers[$key];
			});

		$body = '';
		foreach ($parts as $part) {
			$body .= '--' . $boundary . "\r\n";

			foreach ($part['headers'] as $headerKey => $headerPart) {
				$body .= $headerKey . ': ' . $headerPart . "\r\n";
			}

			$body .= "\r\n";
			$body .= $part['content'] . "\r\n";
		}

		$body .= '--' . $boundary . '--';

		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $body);
		rewind($stream);

		$request->expects($this->any())
			->method('getBody')
			->willReturn($stream);

		return new MultipartRequestParser($request, $this->logger);
	}


	/**
	 * Test validation of the request's body type
	 */
	public function testBodyTypeValidation(): void {
		$bodyStream = 'I am not a stream, but pretend to be';
		/** @var RequestInterface&MockObject $request */
		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$request->expects($this->any())
			->method('getBody')
			->willReturn($bodyStream);

		$this->expectExceptionMessage('Body should be of type resource');
		new MultipartRequestParser($request, $this->logger);
	}

	/**
	 * Test with valid request.
	 * - valid boundary
	 * - valid hash
	 * - valid content-length
	 * - valid file content
	 * - valid file path
	 */
	public function testValidRequest(): void {
		$bodyObject = self::getValidBodyObject();
		unset($bodyObject['0']['headers']['X-File-MD5']);

		$multipartParser = $this->getMultipartParser($bodyObject);

		[$headers, $content] = $multipartParser->parseNextPart();

		$this->assertSame((int)$headers['content-length'], 7, 'Content-Length header should be the same as provided.');
		$this->assertSame($headers['oc-checksum'], 'md5:4f2377b4d911f7ec46325fe603c3af03', 'OC-Checksum header should be the same as provided.');
		$this->assertSame($headers['x-file-path'], '/coucou.txt', 'X-File-Path header should be the same as provided.');

		$this->assertSame($content, "Coucou\n", 'Content should be the same');
	}

	/**
	 * Test with valid request.
	 * - valid boundary
	 * - valid md5 hash
	 * - valid content-length
	 * - valid file content
	 * - valid file path
	 */
	public function testValidRequestWithMd5(): void {
		$bodyObject = self::getValidBodyObject();
		unset($bodyObject['0']['headers']['OC-Checksum']);

		$multipartParser = $this->getMultipartParser($bodyObject);

		[$headers, $content] = $multipartParser->parseNextPart();

		$this->assertSame((int)$headers['content-length'], 7, 'Content-Length header should be the same as provided.');
		$this->assertSame($headers['x-file-md5'], '4f2377b4d911f7ec46325fe603c3af03', 'X-File-MD5 header should be the same as provided.');
		$this->assertSame($headers['x-file-path'], '/coucou.txt', 'X-File-Path header should be the same as provided.');

		$this->assertSame($content, "Coucou\n", 'Content should be the same');
	}

	/**
	 * Test with invalid hash.
	 */
	public function testInvalidHash(): void {
		$bodyObject = self::getValidBodyObject();
		$bodyObject['0']['headers']['OC-Checksum'] = 'md5:f2377b4d911f7ec46325fe603c3af03';
		unset($bodyObject['0']['headers']['X-File-MD5']);
		$multipartParser = $this->getMultipartParser(
			$bodyObject
		);

		$this->expectExceptionMessage('Computed md5 hash is incorrect (4f2377b4d911f7ec46325fe603c3af03).');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with invalid md5 hash.
	 */
	public function testInvalidMd5Hash(): void {
		$bodyObject = self::getValidBodyObject();
		unset($bodyObject['0']['headers']['OC-Checksum']);
		$bodyObject['0']['headers']['X-File-MD5'] = 'f2377b4d911f7ec46325fe603c3af03';
		$multipartParser = $this->getMultipartParser(
			$bodyObject
		);

		$this->expectExceptionMessage('Computed md5 hash is incorrect (4f2377b4d911f7ec46325fe603c3af03).');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with a null hash headers.
	 */
	public function testNullHash(): void {
		$bodyObject = self::getValidBodyObject();
		unset($bodyObject['0']['headers']['OC-Checksum']);
		unset($bodyObject['0']['headers']['X-File-MD5']);
		$multipartParser = $this->getMultipartParser(
			$bodyObject
		);

		$this->expectExceptionMessage('The hash headers must not be null.');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with a null Content-Length.
	 */
	public function testNullContentLength(): void {
		$bodyObject = self::getValidBodyObject();
		unset($bodyObject['0']['headers']['Content-Length']);
		$multipartParser = $this->getMultipartParser(
			$bodyObject
		);

		$this->expectExceptionMessage('The Content-Length header must not be null.');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with a lower Content-Length.
	 */
	public function testLowerContentLength(): void {
		$bodyObject = self::getValidBodyObject();
		$bodyObject['0']['headers']['Content-Length'] = 6;
		$multipartParser = $this->getMultipartParser(
			$bodyObject
		);

		$this->expectExceptionMessage('Computed md5 hash is incorrect (41060d3ddfdf63e68fc2bf196f652ee9).');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with a higher Content-Length.
	 */
	public function testHigherContentLength(): void {
		$bodyObject = self::getValidBodyObject();
		$bodyObject['0']['headers']['Content-Length'] = 8;
		$multipartParser = $this->getMultipartParser(
			$bodyObject
		);

		$this->expectExceptionMessage('Computed md5 hash is incorrect (0161002bbee6a744f18741b8a914e413).');
		$multipartParser->parseNextPart();
	}

	/**
	 * Test with wrong boundary in body.
	 */
	public function testWrongBoundary(): void {
		$bodyObject = self::getValidBodyObject();
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
	public function testNoBoundaryInHeader(): void {
		$bodyObject = self::getValidBodyObject();
		$this->expectExceptionMessage('Error while parsing boundary in Content-Type header.');
		$this->getMultipartParser(
			$bodyObject,
			['Content-Type' => 'multipart/related']
		);
	}

	/**
	 * Test with no boundary in the request's headers.
	 */
	public function testNoBoundaryInBody(): void {
		$bodyObject = self::getValidBodyObject();
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
	public function testBoundaryWithQuotes(): void {
		$bodyObject = self::getValidBodyObject();
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
	public function testWrongContentType(): void {
		$bodyObject = self::getValidBodyObject();
		$this->expectExceptionMessage('Content-Type must be multipart/related');
		$this->getMultipartParser(
			$bodyObject,
			['Content-Type' => 'multipart/form-data; boundary="boundary_azertyuiop"'],
		);
	}

	/**
	 * Test with a wrong key after the content type in the request's headers.
	 */
	public function testWrongKeyInContentType(): void {
		$bodyObject = self::getValidBodyObject();
		$this->expectExceptionMessage('Boundary is invalid');
		$this->getMultipartParser(
			$bodyObject,
			['Content-Type' => 'multipart/related; wrongkey="boundary_azertyuiop"'],
		);
	}

	/**
	 * Test with a null Content-Type in the request's headers.
	 */
	public function testNullContentType(): void {
		$bodyObject = self::getValidBodyObject();
		$this->expectExceptionMessage('Content-Type can not be null');
		$this->getMultipartParser(
			$bodyObject,
			['Content-Type' => null],

		);
	}
}

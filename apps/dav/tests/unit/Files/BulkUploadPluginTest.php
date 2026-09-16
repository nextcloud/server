<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\Files;

use OCA\DAV\BulkUpload\BulkUploadPlugin;
use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class BulkUploadPluginTest extends TestCase {
	private Folder&MockObject $userFolder;
	private LoggerInterface&MockObject $logger;
	private BulkUploadPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->userFolder = $this->createMock(Folder::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->plugin = new BulkUploadPlugin($this->userFolder, $this->logger);
	}

	public function testPathLookupFailureIsReportedForPart(): void {
		$request = $this->createBulkRequest('/coucou.txt', "Coucou\n");
		$response = $this->createMock(ResponseInterface::class);

		/** @var File&MockObject $createdFile */
		$createdFile = $this->createMock(File::class);
		$createdFile->expects(self::once())
			->method('touch')
			->with(null);

		$this->userFolder->expects(self::once())
			->method('newFile')
			->with('/coucou.txt', "Coucou\n")
			->willReturn($createdFile);

		// The fix must reload the exact upload path, rather than resolving an
		// arbitrary accessible node using the file ID.
		$this->userFolder->expects(self::once())
			->method('get')
			->with('/coucou.txt')
			->willThrowException(new NotFoundException('Uploaded file could not be reloaded'));

		$this->userFolder->expects(self::never())
			->method('getFirstNodeById');

		$this->logger->expects(self::once())
			->method('error')
			->with(
				'Uploaded file could not be reloaded',
				['path' => '/coucou.txt'],
			);

		$response->expects(self::once())
			->method('setStatus')
			->with(Http::STATUS_OK);

		$response->expects(self::once())
			->method('setBody')
			->with(json_encode([
				'/coucou.txt' => [
					'error' => true,
					'message' => 'Uploaded file could not be reloaded',
				],
			], JSON_THROW_ON_ERROR));

		self::assertFalse($this->plugin->httpPost($request, $response));
	}

	private function createBulkRequest(string $path, string $content): RequestInterface {
		$boundary = 'bulk-upload-test-boundary';
		$body = '--' . $boundary . "\r\n"
			. 'X-File-Path: ' . $path . "\r\n"
			. 'X-File-MD5: ' . md5($content) . "\r\n"
			. 'Content-Length: ' . strlen($content) . "\r\n"
			. "\r\n"
			. $content . "\r\n"
			. '--' . $boundary . "--\r\n";

		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $body);
		rewind($stream);

		/** @var RequestInterface&MockObject $request */
		$request = $this->createMock(RequestInterface::class);
		$request->method('getPath')->willReturn('bulk');
		$request->method('getHeader')
			->with('Content-Type')
			->willReturn('multipart/related; boundary=' . $boundary);
		$request->method('getBody')->willReturn($stream);

		return $request;
	}
}

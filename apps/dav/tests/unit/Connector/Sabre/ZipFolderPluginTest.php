<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use Exception;
use OC\L10N\L10N;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\Node;
use OCA\DAV\Connector\Sabre\ZipFolderPlugin;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\BeforeZipCreatedEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node as OCPNode;
use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\IL10N;
use OCP\L10N\IFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Tree;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;

class ZipFolderPluginTest extends TestCase {
	private Tree&MockObject $tree;
	private LoggerInterface&MockObject $logger;
	private IEventDispatcher&MockObject $eventDispatcher;
	private IDateTimeZone&MockObject $timezoneFactory;
	private IConfig&MockObject $config;
	private IFactory&MockObject $l10nFactory;
	private Response&MockObject $response;
	private IL10N&MockObject $l10n;

	protected function setUp(): void {
		parent::setUp();

		$this->tree = $this->createMock(Tree::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->timezoneFactory = $this->createMock(IDateTimeZone::class);
		$this->config = $this->createMock(IConfig::class);
		$this->response = $this->createMock(Response::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnCallback(static fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->l10nFactory->method('get')->willReturn($this->l10n);
	}

	public static function dataDownloadingABlockedFolderShouldFail(): array {
		return [
			'missing files reporting feature off' => [ false ],
			'missing files reporting feature on' => [ true ],
		];
	}

	/*
	 * Tests that the plugin throws a Forbidden exception when the user is trying
	 * to download a collection they have no access to.
	 */
	#[DataProvider(methodName: 'dataDownloadingABlockedFolderShouldFail')]
	public function testDownloadingABlockedFolderShouldFail(bool $reportMissingFiles): void {
		$plugin = $this->createPlugin($reportMissingFiles);
		$folderPath = '/user/files/folder';
		$folder = $this->createFolderNode($folderPath, []);
		$directory = $this->createDirectoryNode($folder);

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($folderPath)
			->willReturn($directory);

		$errorMessage = 'Blocked by ACL';
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function (BeforeZipCreatedEvent $event) use ($reportMissingFiles, $errorMessage): BeforeZipCreatedEvent {
				$this->assertSame([], $event->getFiles());
				$this->assertEquals($reportMissingFiles, $event->allowPartialArchive);
				$event->setSuccessful(false);
				$event->setErrorMessage($errorMessage);

				return $event;
			});

		$this->expectException(Forbidden::class);
		$this->expectExceptionMessage($errorMessage);
		$directory->expects($this->never())->method('getChild');
		$folder->expects($this->never())->method('getDirectoryListing');

		$plugin->handleDownload($this->createRequest($folderPath), $this->response);
	}

	public static function dataDownloadingAFolderShouldFailWhenItemsAreBlocked(): array {
		return [
			'no files filtering' => [ [] ],
			'files filtering' => [ ['allowed.txt', 'blocked.txt'] ],
		];
	}

	/*
	 * Tests that when `archive_report_missing_files` is disabled, downloading
	 * a directory which contains a non-downloadable item stops the entire
	 * download.
	 */
	#[DataProvider(methodName: 'dataDownloadingAFolderShouldFailWhenItemsAreBlocked')]
	public function testDownloadingAFolderShouldFailWhenItemsAreBlocked(array $filesFilter): void {
		$plugin = $this->createPlugin(false);
		$folderPath = '/user/files/folder';
		$allowedFile = $this->createFile("{$folderPath}/allowed.txt", 'allowed');
		$blockedFile = $this->createFile("{$folderPath}/blocked.txt", 'secret');
		$files = [$allowedFile, $blockedFile];
		$childNodes = [
			'allowed.txt' => $this->createNode($allowedFile),
			'blocked.txt' => $this->createNode($blockedFile),
		];

		$folder = $this->createFolderNode($folderPath, $files);
		$directory = $this->createDirectoryNode($folder, $childNodes);

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($folderPath)
			->willReturn($directory);

		$errorMessage = 'Blocked by ACL';
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function (BeforeZipCreatedEvent $event) use ($errorMessage, $filesFilter): BeforeZipCreatedEvent {
				$this->assertSame($filesFilter, $event->getFiles());
				$this->assertFalse($event->allowPartialArchive);
				$event->setSuccessful(false);
				$event->setErrorMessage($errorMessage);

				return $event;
			});

		$this->expectException(Forbidden::class);
		$this->expectExceptionMessage($errorMessage);

		$plugin->handleDownload($this->createRequest($folderPath, $filesFilter), $this->response);
	}


	public static function dataDownloadingAFolderWithMissingFilesReportingShouldSucceed(): array {
		return [
			// files are reporting as missing either because they are download-blocked or because some error happened
			'full directory download' => [
				'children' => ['allowed.txt' => 'allowed', 'blocked.txt' => 'blocked', 'error.txt' => new \RuntimeException('read error')],
				'filesFilter' => [],
				'downloadBlocked' => [ 'blocked.txt' ],
				'expectedMissingFiles' => [ 'blocked.txt' => 'blocked', 'error.txt' => 'Error while opening the file: RuntimeException' ],
			],
			// files filtered out should not be reported as missing
			'filtering some files' => [
				'children' => [ 'allowed.txt' => 'allowed', 'blocked.txt' => 'blocked', 'error.txt' => new \RuntimeException('read error') ],
				'filesFilter' => ['allowed.txt', 'blocked.txt'],
				'downloadBlocked' => ['blocked.txt'],
				'expectedMissingFiles' => [ 'blocked.txt' => 'blocked' ],
			],
		];
	}

	/*
	 * Tests that when files in a directory cannot be downloaded and the
	 * `archive_report_missing_files` is enabled, an entry is added to the
	 * `missing_files.json` file.
	 */
	#[DataProvider(methodName: 'dataDownloadingAFolderWithMissingFilesReportingShouldSucceed')]
	public function testDownloadingAFolderWithMissingFilesReportingShouldSucceed(array $children, array $filesFilter, array $downloadBlocked, array $expectedMissingFiles): void {
		$plugin = $this->createPlugin(true);

		$folderPath = '/user/files/folder';
		$childFiles = [];
		$childNodes = [];
		foreach ($children as $childName => $content) {
			$childFile = $this->createFile("{$folderPath}/{$childName}", $content);
			$childFiles[$childName] = $childFile;
			$childNodes[$childName] = $this->createNode($childFile);
		}

		$folder = $this->createFolderNode($folderPath, array_values($childFiles));
		$directory = $this->createDirectoryNode($folder, $childNodes);

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($folderPath)
			->willReturn($directory);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function (BeforeZipCreatedEvent $event) use ($downloadBlocked, $filesFilter): BeforeZipCreatedEvent {
				$this->assertSame($filesFilter, $event->getFiles());
				$this->assertTrue($event->allowPartialArchive);
				$event->addNodeFilter(static function ($node) use ($downloadBlocked): array {
					if (in_array($node->getName(), $downloadBlocked)) {
						return [false, 'blocked'];
					}

					return [true, null];
				});

				return $event;
			});

		ob_start();
		$request = $this->createRequest($folderPath, $filesFilter);
		$continueHandling = $plugin->handleDownload($request, $this->response);

		$output = $this->getActualOutputForAssertion();
		$this->assertStringContainsString('missing_files.json', $output, "$output does not contain missin_files.json");
		foreach ($expectedMissingFiles as $file => $error) {
			$stringToMatch = sprintf('%s": "%s"', $file, $error);
			$this->assertStringContainsString($stringToMatch, $output, "$output does not contain $stringToMatch");
		}

		// assert that the handling should be stopped
		$this->assertFalse($continueHandling);
	}


	private function createPlugin(bool $reportMissingFiles): ZipFolderPlugin {
		$this->config->method('getSystemValueBool')
			->with('archive_report_missing_files', false)
			->willReturn($reportMissingFiles);

		return new ZipFolderPlugin(
			$this->tree,
			$this->logger,
			$this->eventDispatcher,
			$this->timezoneFactory,
			$this->config,
			$this->l10nFactory,
		);
	}

	/**
	 * @param list<string> $filesFilter
	 * @throws \JsonException
	 */
	private function createRequest(string $resource, array $filesFilter = []): Request&MockObject {
		$query = [];
		if ($filesFilter !== []) {
			$query['files'] = json_encode($filesFilter, JSON_THROW_ON_ERROR);
		}

		$request = $this->createMock(Request::class);
		$request->method('getPath')->willReturn($resource);
		$request->method('getQueryParameters')->willReturn($query);

		// file filtering can be done via header or QS parameters. Use only one.
		$request->method('getHeaderAsArray')->willReturnMap([
			['Accept', ['application/zip']],
			['X-NC-Files', []],
		]);

		return $request;
	}

	/**
	 * @param list<File> $children
	 * @param array<string, Node&MockObject> $childNodes
	 */
	private function createDirectoryNode(Folder $folder, array $childNodes = []): Directory&MockObject {
		$directory = $this->createMock(Directory::class);
		$directory->method('getNode')->willReturn($folder);
		$directory->method('getChild')->willReturnCallback(static fn (string $name, ...$_): Node => $childNodes[$name]);

		return $directory;
	}

	/**
	 * @param list<OCPNode> $children
	 */
	private function createFolderNode(string $path, array $children): Folder&MockObject {
		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn($path);
		$folder->method('getName')->willReturn(basename($path));
		$folder->method('getDirectoryListing')->willReturn($children);

		return $folder;
	}

	private function createNode(File $node): Node&MockObject {
		$child = $this->createMock(Node::class);
		$child->method('getNode')->willReturn($node);
		$child->method('getPath')->willReturn($node->getPath());

		return $child;
	}

	private function createFile(string $path, string|Exception $contents): File&MockObject {
		$length = is_string($contents) ? strlen($contents) : 0;
		$file = $this->createMock(File::class);
		$file->method('getPath')->willReturn($path);
		$file->method('getName')->willReturn(basename($path));
		$file->method('getSize')->willReturn($length);
		$file->method('getMTime')->willReturn(123);
		$file->method('fopen')->with('rb')->willReturnCallback(static function () use ($contents) {
			if (!is_string($contents)) {
				throw $contents;
			}

			$stream = fopen('php://temp', 'r+');
			fwrite($stream, $contents);
			rewind($stream);

			return $stream;
		});

		return $file;
	}
}

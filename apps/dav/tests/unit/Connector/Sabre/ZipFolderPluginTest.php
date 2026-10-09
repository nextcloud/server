<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use Exception;
use Icewind\Streams\CallbackWrapper;
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
				$this->assertSame($reportMissingFiles, $event->allowsPartialArchive());
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
		$allowedFile = $this->createFile("{$folderPath}/allowed.txt", 'allowed', strlen('allowed'));
		$blockedFile = $this->createFile("{$folderPath}/blocked.txt", 'secret', strlen('secret'));
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
				$this->assertFalse($event->allowsPartialArchive());
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
				'children' => [
					['name' => 'allowed.txt', 'content' => 'allowed', 'readSize' => null],
					[
						'name' => 'blocked.txt',
						'content' => 'blocked',
						'readSize' => null
					],
					[
						'name' => 'error.txt',
						'content' => new \RuntimeException('read error'),
						'readSize' => null
					],
				],
				'filesFilter' => [],
				'downloadBlocked' => ['blocked.txt'],
				'expectedMissingFiles' => [
					'blocked.txt' => 'blocked',
					'error.txt' => 'File could not be added to the archive. Please check the server logs for more information.'
				],
			],
			// files filtered out should not be reported as missing
			'filtering some files' => [
				'children' => [
					['name' => 'allowed.txt', 'content' => 'allowed', 'readSize' => null],
					['name' => 'blocked.txt', 'content' => 'blocked', 'readSize' => null],
					['name' => 'error.txt', 'content' => new \RuntimeException('read error'), 'readSize' => null],
				],
				'filesFilter' => ['allowed.txt', 'blocked.txt'],
				'downloadBlocked' => ['blocked.txt'],
				'expectedMissingFiles' => ['blocked.txt' => 'blocked'],
			],
			// incomplete download
			'incomplete file download' => [
				'children' => [
					['name' => 'allowed.txt', 'content' => 'allowed', 'readSize' => 2],
				],
				'filesFilter' => ['allowed.txt'],
				'downloadBlocked' => [],
				'expectedMissingFiles' => ['allowed.txt' => 'Read 2 out of 7 bytes from storage. This means the connection may have been closed due to a network/storage error.'],
			],
			// failed fopen
			'fopen failed' => [
				'children' => [
					['name' => 'allowed.txt', 'content' => false, 'readSize' => null],
				],
				'filesFilter' => ['allowed.txt'],
				'downloadBlocked' => [],
				'expectedMissingFiles' => ['allowed.txt' => 'File could not be opened (fopen). Please check the server logs for more information.'],
			],
			// colliding missing_files.json
			'colliding missing files file' => [
				'children' => [
					['name' => 'missing_files.json', 'content' => 'allowed', 'readSize' => null],
					['name' => 'blocked.txt', 'content' => 'blocked', 'readSize' => null],
				],
				'filesFilter' => [ZipFolderPlugin::MISSING_FILES_FULL_FILENAME, 'blocked.txt'],
				'downloadBlocked' => ['blocked.txt'],
				'expectedMissingFiles' => ['blocked.txt' => 'blocked'],
				'expectedMissingFilesFilename' => ZipFolderPlugin::MISSING_FILES_FILENAME . ' (1)' . ZipFolderPlugin::MISSING_FILES_EXTENSION,
			],
		];
	}

	/**
	 * Tests that when files in a directory cannot be downloaded and the
	 * `archive_report_missing_files` is enabled, an entry is added to the
	 * {@see ZipFolderPlugin::MISSING_FILES_FULL_FILENAME} file.
	 *
	 * @param list<array{name: string, content: (string|false|Exception), readSize: ?int}> $children
	 */
	#[DataProvider(methodName: 'dataDownloadingAFolderWithMissingFilesReportingShouldSucceed')]
	public function testDownloadingAFolderWithMissingFilesReportingShouldSucceed(
		array $children,
		array $filesFilter,
		array $downloadBlocked,
		array $expectedMissingFiles,
		string $expectedMissingFilesFilename = ZipFolderPlugin::MISSING_FILES_FULL_FILENAME,
	): void {
		$plugin = $this->createPlugin(true);

		$folderPath = '/user/files/folder';
		$childFiles = [];
		$childNodes = [];
		foreach ($children as $childData) {
			['name' => $childName, 'content' => $content, 'readSize' => $readSize] = $childData;
			$childFile = $this->createFile("{$folderPath}/{$childName}", $content, $readSize);
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
				$this->assertTrue($event->allowsPartialArchive());
				$event->addNodeFilter(static fn (OCPNode $node): ?string => in_array($node->getName(), $downloadBlocked, true) ? 'blocked' : null);

				return $event;
			});

		ob_start();
		$request = $this->createRequest($folderPath, $filesFilter);
		$continueHandling = $plugin->handleDownload($request, $this->response);

		$output = $this->getActualOutputForAssertion();
		$this->assertStringContainsString($expectedMissingFilesFilename, $output, "$output does not contain expected missing files file");
		foreach ($expectedMissingFiles as $file => $error) {
			$stringToMatch = sprintf('%s": "%s"', $file, $error);
			$this->assertStringContainsString($stringToMatch, $output, "$output does not contain $stringToMatch");
		}

		// assert that the handling should be stopped
		$this->assertFalse($continueHandling);
	}

	/*
	 * Tests that the content of an excluded folder is not listed, and that a
	 * folder whose content cannot be listed is reported as missing without
	 * aborting the archive.
	 */
	public function testDownloadingAFolderWithExcludedAndUnreadableSubfolders(): void {
		$plugin = $this->createPlugin(true);

		$folderPath = '/user/files/folder';
		$allowedFile = $this->createFile("{$folderPath}/allowed.txt", 'allowed', strlen('allowed'));
		$blockedFolder = $this->createFolderNode("{$folderPath}/blocked", []);
		$blockedFolder->expects($this->never())->method('getDirectoryListing');
		$brokenFolder = $this->createMock(Folder::class);
		$brokenFolder->method('getPath')->willReturn("{$folderPath}/broken");
		$brokenFolder->method('getName')->willReturn('broken');
		$brokenFolder->method('getMTime')->willReturn(123);
		$brokenFolder->method('getDirectoryListing')->willThrowException(new \RuntimeException('storage not available'));

		$folder = $this->createFolderNode($folderPath, [$allowedFile, $blockedFolder, $brokenFolder]);
		$directory = $this->createDirectoryNode($folder);

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($folderPath)
			->willReturn($directory);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(static function (BeforeZipCreatedEvent $event): BeforeZipCreatedEvent {
				$event->addNodeFilter(static fn (OCPNode $node): ?string => $node->getName() === 'blocked' ? 'blocked' : null);
				return $event;
			});

		ob_start();
		$continueHandling = $plugin->handleDownload($this->createRequest($folderPath), $this->response);

		$output = $this->getActualOutputForAssertion();
		$this->assertStringContainsString('folder/blocked": "blocked"', $output);
		$this->assertStringContainsString('folder/broken": "File could not be added to the archive. Please check the server logs for more information."', $output);
		$this->assertStringNotContainsString('allowed.txt":', $output);
		$this->assertFalse($continueHandling);
	}

	/*
	 * Tests that a file whose storage stream throws after part of it was
	 * written is completed and reported, and the entries after it are intact.
	 */
	public function testFileFailingMidReadKeepsArchiveValid(): void {
		$plugin = $this->createPlugin(true);

		$folderPath = '/user/files/folder';
		$firstFile = $this->createFile("{$folderPath}/first.txt", 'first');
		$brokenFile = $this->createFile("{$folderPath}/broken.bin", str_repeat('b', 10000), null, 2);
		$lastFile = $this->createFile("{$folderPath}/last.txt", 'last');
		$folder = $this->createFolderNode($folderPath, [$firstFile, $brokenFile, $lastFile]);

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($folderPath)
			->willReturn($this->createDirectoryNode($folder));
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnArgument(0);
		$this->logger->expects($this->once())
			->method('error')
			->with('Reading the file failed while adding it to the archive', $this->callback(
				static fn (array $context): bool => $context['exception'] instanceof \RuntimeException && $context['path'] === "{$folderPath}/broken.bin"
			));

		ob_start();
		$continueHandling = $plugin->handleDownload($this->createRequest($folderPath), $this->response);
		$this->assertFalse($continueHandling);

		$archivePath = tempnam(sys_get_temp_dir(), 'zipfolderplugin') . '.zip';
		file_put_contents($archivePath, $this->getActualOutputForAssertion());
		$zip = new \ZipArchive();
		try {
			$this->assertTrue($zip->open($archivePath), 'Archive is not a valid zip file');
			$this->assertSame('first', $zip->getFromName('folder/first.txt'));
			$this->assertSame(str_repeat('b', 8192), $zip->getFromName('folder/broken.bin'));
			$this->assertSame('last', $zip->getFromName('folder/last.txt'));
			$this->assertSame(
				['folder/broken.bin' => 'Reading the file failed after 8192 of 10000 bytes.'],
				json_decode($zip->getFromName('missing_files.json'), true),
			);
			$zip->close();
		} finally {
			unlink($archivePath);
		}
	}

	private function createPlugin(bool $reportMissingFiles): ZipFolderPlugin {
		$this->config->method('getSystemValueBool')
			->with('archive_report_missing_files', true)
			->willReturn($reportMissingFiles);

		return new ZipFolderPlugin(
			$this->tree,
			$this->logger,
			$this->eventDispatcher,
			$this->timezoneFactory,
			$this->config,
			$this->l10n,
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
		$folder->method('getMTime')->willReturn(123);
		$folder->method('getDirectoryListing')->willReturn($children);
		$folder->method('get')->willReturnCallback(
			function (string $path) use ($children) {
				foreach ($children as $child) {
					if ($child->getName() === $path) {
						return $child;
					}
				}
				return null;
			}
		);

		return $folder;
	}

	private function createNode(File $node): Node&MockObject {
		$child = $this->createMock(Node::class);
		$child->method('getNode')->willReturn($node);
		$child->method('getPath')->willReturn($node->getPath());

		return $child;
	}

	/**
	 * @param ?int $throwOnRead number of the stream read that throws, null to never throw
	 */
	private function createFile(string $path, string|false|Exception $fopenReturns, ?int $readSize = null, ?int $throwOnRead = null): File&MockObject {
		$fileSize = is_string($fopenReturns) ? strlen($fopenReturns) : 0;
		$file = $this->createMock(File::class);
		$file->method('getPath')->willReturn($path);
		$file->method('getName')->willReturn(basename($path));
		$file->method('getSize')->willReturn($fileSize);
		$file->method('getMTime')->willReturn(123);
		$file->method('fopen')->with('rb')->willReturnCallback(static function () use ($readSize, $fopenReturns, $throwOnRead) {
			if ($fopenReturns instanceof Exception) {
				throw $fopenReturns;
			}

			if ($fopenReturns === false) {
				return $fopenReturns;
			}

			$stream = fopen('php://temp', 'r+');
			fwrite($stream, $readSize !== null ? substr($fopenReturns, 0, $readSize) : $fopenReturns);
			rewind($stream);
			if ($throwOnRead === null) {
				return $stream;
			}

			$reads = 0;
			return CallbackWrapper::wrap($stream, static function () use (&$reads, $throwOnRead): void {
				if (++$reads === $throwOnRead) {
					throw new \RuntimeException('storage read failed');
				}
			});
		});

		return $file;
	}
}

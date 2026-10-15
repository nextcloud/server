<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Command;

use OC\Files\Mount\ObjectHomeMountProvider;
use OC\Files\SetupManager;
use OC\Files\Utils\Scanner;
use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\PreviewService;
use OC\Preview\Storage\StorageFactory;
use OCA\Files\Command\ScanAppData;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Console\ISignalHandler;
use OCP\Console\Verbosity;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorageFactory;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[Group(name: 'DB')]
class ScanAppDataTest extends TestCase {
	private IRootFolder $rootFolder;
	private IConfig $config;
	private StorageFactory $storageFactory;
	private IOutput&MockObject $output;
	private ISignalHandler&MockObject $signalHandler;
	private Scanner&MockObject $internalScanner;
	private ScanAppData $scanner;
	private string $user;

	public function setUp(): void {
		$this->config = Server::get(IConfig::class);
		$this->rootFolder = Server::get(IRootFolder::class);
		$this->storageFactory = Server::get(StorageFactory::class);
		$this->user = static::getUniqueID('user');
		$user = Server::get(IUserManager::class)->createUser($this->user, 'test');
		Server::get(ISetupManager::class)->setupForUser($user);
		Server::get(IUserSession::class)->setUser($user);
		$this->output = $this->createMock(IOutput::class);
		$this->output->method('getVerbosity')->willReturn(Verbosity::Normal);
		$this->signalHandler = $this->createMock(ISignalHandler::class);
		$this->scanner = $this->getMockBuilder(ScanAppData::class)
			->onlyMethods(['initTools', 'getScanner'])
			->setConstructorArgs([
				$this->rootFolder,
				$this->config,
				$this->storageFactory,
				Server::get(IEventDispatcher::class),
				Server::get(LoggerInterface::class),
				Server::get(SetupManager::class),
			])
			->getMock();
		$this->internalScanner = $this->getMockBuilder(Scanner::class)
			->onlyMethods(['scan'])
			->disableOriginalConstructor()
			->getMock();
		$this->scanner->method('getScanner')->willReturn($this->internalScanner);

		$this->scanner->method('initTools')
			->willReturnCallback(function (): void {
			});
		try {
			$this->rootFolder->get($this->rootFolder->getAppDataDirectoryName() . '/preview')->delete();
		} catch (NotFoundException) {
		}

		Server::get(PreviewService::class)->deleteAll();

		try {
			$appDataFolder = $this->rootFolder->get($this->rootFolder->getAppDataDirectoryName());
		} catch (NotFoundException) {
			$appDataFolder = $this->rootFolder->newFolder($this->rootFolder->getAppDataDirectoryName());
		}

		$appDataFolder->newFolder('preview');
	}

	public function tearDown(): void {
		Server::get(IUserManager::class)->get($this->user)->delete();
		Server::get(IUserSession::class)->setUser(null);
		$this->rootFolder->get($this->rootFolder->getAppDataDirectoryName())->delete();
		parent::tearDown();
	}

	public function testScanAppDataRoot(): void {
		$homeProvider = Server::get(ObjectHomeMountProvider::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('foo');
		if ($homeProvider->getHomeMountForUser($user, $this->createMock(IStorageFactory::class)) !== null) {
			$this->markTestSkipped();
		}

		$this->internalScanner->method('scan')->willReturnCallback(function (): void {
			$this->internalScanner->emit('\OC\Files\Utils\Scanner', 'scanFile', ['path42']);
			$this->internalScanner->emit('\OC\Files\Utils\Scanner', 'scanFolder', ['path42']);
			$this->internalScanner->emit('\OC\Files\Utils\Scanner', 'scanFolder', ['path42']);
		});
		$this->output->expects($this->once())->method('writeTableInOutputFormat')
			->willReturnCallback(function (array $items): void {
				$this->assertCount(1, $items);
				$row = $items[0];
				$this->assertEquals(0, $row['Previews']);
				$this->assertEquals(2, $row['Folders']);
				$this->assertEquals(1, $row['Files']);
			});

		$exitCode = ($this->scanner)($this->output, $this->signalHandler, '');
		$this->assertEquals(ExitCode::Success, $exitCode);
	}

	public static function scanPreviewLocalData(): \Generator {
		yield 'initial migration done' => [true, null];
		yield 'initial migration not done' => [false, false];
		yield 'initial migration not done with legacy paths' => [false, true];
	}

	#[DataProvider(methodName: 'scanPreviewLocalData')]
	public function testScanAppDataPreviewOnlyLocalFile(bool $migrationDone, ?bool $legacy): void {
		$homeProvider = Server::get(ObjectHomeMountProvider::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('foo');
		if ($homeProvider->getHomeMountForUser($user, $this->createMock(IStorageFactory::class)) !== null) {
			$this->markTestSkipped();
		}

		$file = $this->rootFolder->getUserFolder($this->user)->newFile('myfile.jpeg');

		if ($migrationDone) {
			Server::get(IAppConfig::class)->setValueBool('core', 'previewMovedDone', true);
			$preview = new Preview();
			$preview->generateId();
			$preview->setFileId($file->getId());
			$preview->setStorageId($file->getStorage()->getCache()->getNumericStorageId());
			$preview->setEtag('abc');
			$preview->setMtime(1);
			$preview->setWidth(1024);
			$preview->setHeight(1024);
			$preview->setMimeType('image/jpeg');
			$preview->setSize($this->storageFactory->writePreview($preview, 'preview content'));
			Server::get(PreviewMapper::class)->insert($preview);

			$preview = new Preview();
			$preview->generateId();
			$preview->setFileId($file->getId());
			$preview->setStorageId($file->getStorage()->getCache()->getNumericStorageId());
			$preview->setEtag('abc');
			$preview->setMtime(1);
			$preview->setWidth(2024);
			$preview->setHeight(2024);
			$preview->setMax(true);
			$preview->setMimeType('image/jpeg');
			$preview->setSize($this->storageFactory->writePreview($preview, 'preview content'));
			Server::get(PreviewMapper::class)->insert($preview);

			$preview = new Preview();
			$preview->generateId();
			$preview->setFileId($file->getId());
			$preview->setStorageId($file->getStorage()->getCache()->getNumericStorageId());
			$preview->setEtag('abc');
			$preview->setMtime(1);
			$preview->setWidth(2024);
			$preview->setHeight(2024);
			$preview->setMax(true);
			$preview->setCropped(true);
			$preview->setMimeType('image/jpeg');
			$preview->setSize($this->storageFactory->writePreview($preview, 'preview content'));
			Server::get(PreviewMapper::class)->insert($preview);

			$previews = Server::get(PreviewService::class)->getAvailablePreviews([$file->getId()]);
			$this->assertCount(3, $previews[$file->getId()]);
		} else {
			Server::get(IAppConfig::class)->setValueBool('core', 'previewMovedDone', false);
			/** @var Folder $previewFolder */
			$previewFolder = $this->rootFolder->get($this->rootFolder->getAppDataDirectoryName() . '/preview');
			if (!$legacy) {
				foreach (str_split(substr(md5((string)$file->getId()), 0, 7)) as $subPath) {
					$previewFolder = $previewFolder->newFolder($subPath);
				}
			}
			$previewFolder = $previewFolder->newFolder((string)$file->getId());
			$previewFolder->newFile('1024-1024.jpg');
			$previewFolder->newFile('2024-2024-max.jpg');
			$previewFolder->newFile('2024-2024-max-crop.jpg');

			$this->assertCount(3, $previewFolder->getDirectoryListing());

			$previews = Server::get(PreviewService::class)->getAvailablePreviews([$file->getId()]);
			$this->assertCount(0, $previews[$file->getId()]);
		}

		$mimetypeDetector = $this->createMock(IMimeTypeDetector::class);
		$mimetypeDetector->method('detectPath')->willReturn('image/jpeg');

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueBool')->with('core', 'previewMovedDone')->willReturn($migrationDone);

		$mimetypeLoader = $this->createMock(IMimeTypeLoader::class);
		$mimetypeLoader->method('getMimetypeById')->willReturn('image/jpeg');

		$this->output->expects($this->once())->method('writeTableInOutputFormat')
			->willReturnCallback(function (array $items): void {
				$this->assertCount(1, $items);
				$row = $items[0];
				$this->assertEquals(3, $row['Previews']);
				$this->assertEquals(0, $row['Folders']);
				$this->assertEquals(0, $row['Files']);
			});
		$exitCode = ($this->scanner)($this->output, $this->signalHandler, 'preview');
		$this->assertEquals(ExitCode::Success, $exitCode);

		/** @var Folder $previewFolder */
		$previewFolder = $this->rootFolder->get($this->rootFolder->getAppDataDirectoryName() . '/preview');
		$children = $previewFolder->getDirectoryListing();
		$this->assertCount(0, $children);

		Server::get(PreviewService::class)->deleteAll();
	}
}

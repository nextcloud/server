<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Core\Command\Preview;

use OC\Core\Command\Preview\Cleanup;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class CleanupTest extends TestCase {
	private IRootFolder&MockObject $rootFolder;
	private LoggerInterface&MockObject $logger;
	private InputInterface&MockObject $input;
	private OutputInterface&MockObject $output;
	private Cleanup $repair;

	protected function setUp(): void {
		parent::setUp();
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->repair = new Cleanup(
			$this->rootFolder,
			$this->logger,
		);

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testCleanup(): void {
		$previewFolder = $this->createMock(Folder::class);
		$previewFolder->expects($this->once())
			->method('isDeletable')
			->willReturn(true);

		$previewFolder->expects($this->once())
			->method('delete');

		$appDataFolder = $this->createMock(Folder::class);
		$appDataFolder->expects($this->once())->method('get')->with('preview')->willReturn($previewFolder);
		$appDataFolder->expects($this->once())->method('newFolder')->with('preview');

		$this->rootFolder->expects($this->once())
			->method('getAppDataDirectoryName')
			->willReturn('appdata_some_id');

		$this->rootFolder->expects($this->once())
			->method('get')
			->with('appdata_some_id')
			->willReturn($appDataFolder);

		$this->output->expects($this->exactly(3))->method('writeln')
			->with(self::callback(function (string $message): bool {
				static $i = 0;
				return match (++$i) {
					1 => $message === 'Preview folder deleted',
					2 => $message === 'Preview folder recreated',
					3 => $message === 'Previews removed'
				};
			}));

		$this->assertEquals(0, $this->repair->run($this->input, $this->output));
	}

	public function testCleanupWhenNotDeletable(): void {
		$previewFolder = $this->createMock(Folder::class);
		$previewFolder->expects($this->once())
			->method('isDeletable')
			->willReturn(false);

		$previewFolder->expects($this->never())
			->method('delete');

		$appDataFolder = $this->createMock(Folder::class);
		$appDataFolder->expects($this->once())->method('get')->with('preview')->willReturn($previewFolder);
		$appDataFolder->expects($this->never())->method('newFolder')->with('preview');

		$this->rootFolder->expects($this->once())
			->method('getAppDataDirectoryName')
			->willReturn('appdata_some_id');

		$this->rootFolder->expects($this->once())
			->method('get')
			->with('appdata_some_id')
			->willReturn($appDataFolder);

		$this->logger->expects($this->once())->method('error')->with("Previews can't be removed: preview folder isn't deletable");
		$this->output->expects($this->once())->method('writeln')->with("Previews can't be removed: preview folder isn't deletable");

		$this->assertEquals(1, $this->repair->run($this->input, $this->output));
	}

	/**
	 * @dataProvider dataForTestCleanupWithDeleteException
	 */
	public function testCleanupWithDeleteException(string $exceptionClass, string $errorMessage): void {
		$previewFolder = $this->createMock(Folder::class);
		$previewFolder->expects($this->once())
			->method('isDeletable')
			->willReturn(true);

		$previewFolder->expects($this->once())
			->method('delete')
			->willThrowException(new $exceptionClass());

		$appDataFolder = $this->createMock(Folder::class);
		$appDataFolder->expects($this->once())->method('get')->with('preview')->willReturn($previewFolder);
		$appDataFolder->expects($this->never())->method('newFolder')->with('preview');

		$this->rootFolder->expects($this->once())
			->method('getAppDataDirectoryName')
			->willReturn('appdata_some_id');

		$this->rootFolder->expects($this->once())
			->method('get')
			->with('appdata_some_id')
			->willReturn($appDataFolder);

		$this->logger->expects($this->once())->method('error')->with($errorMessage);
		$this->output->expects($this->once())->method('writeln')->with($errorMessage);

		$this->assertEquals(1, $this->repair->run($this->input, $this->output));
	}

	public static function dataForTestCleanupWithDeleteException(): array {
		return [
			[NotFoundException::class, "Previews weren't deleted: preview folder was not found while deleting it"],
			[NotPermittedException::class, "Previews weren't deleted: you don't have the permission to delete preview folder"],
		];
	}

	public function testCleanupWithCreateException(): void {
		$previewFolder = $this->createMock(Folder::class);
		$previewFolder->expects($this->once())
			->method('isDeletable')
			->willReturn(true);

		$previewFolder->expects($this->once())
			->method('delete');

		$appDataFolder = $this->createMock(Folder::class);
		$appDataFolder->expects($this->once())->method('get')->with('preview')->willReturn($previewFolder);
		$appDataFolder->expects($this->once())->method('newFolder')->with('preview')->willThrowException(new NotPermittedException());

		$this->rootFolder->expects($this->once())
			->method('getAppDataDirectoryName')
			->willReturn('appdata_some_id');

		$this->rootFolder->expects($this->once())
			->method('get')
			->with('appdata_some_id')
			->willReturn($appDataFolder);

		$this->output->expects($this->exactly(2))->method('writeln')
			->with(self::callback(function (string $message): bool {
				static $i = 0;
				return match (++$i) {
					1 => $message === 'Preview folder deleted',
					2 => $message === "Preview folder was deleted, but you don't have the permission to create preview folder",
				};
			}));

		$this->logger->expects($this->once())->method('error')->with("Preview folder was deleted, but you don't have the permission to create preview folder");

		$this->assertEquals(1, $this->repair->run($this->input, $this->output));
	}
}

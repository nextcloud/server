<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Preview;

use OC\Core\Command\Preview\Cleanup;
use OC\Preview\PreviewService;
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
	private InputInterface&MockObject $input;
	private OutputInterface&MockObject $output;
	private Cleanup $repair;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->repair = $this->createInstanceWithMocks(Cleanup::class);

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testCleanup(): void {
		$this->mocks[PreviewService::class]->expects($this->once())->method('deleteAll');

		$previewFolder = $this->createMock(Folder::class);
		$previewFolder->expects($this->once())
			->method('isDeletable')
			->willReturn(true);

		$previewFolder->expects($this->once())
			->method('delete');

		$appDataFolder = $this->createMock(Folder::class);
		$appDataFolder->expects($this->once())->method('get')->with('preview')->willReturn($previewFolder);

		$this->mocks[IRootFolder::class]->expects($this->once())
			->method('getAppDataDirectoryName')
			->willReturn('appdata_some_id');

		$this->mocks[IRootFolder::class]->expects($this->once())
			->method('get')
			->with('appdata_some_id')
			->willReturn($appDataFolder);

		$this->output->expects($this->exactly(2))->method('writeln')
			->with(self::callback(function (string $message): bool {
				static $i = 0;
				return match (++$i) {
					1 => $message === 'Preview folder deleted',
					2 => $message === 'Previews removed'
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

		$this->mocks[IRootFolder::class]->expects($this->once())
			->method('getAppDataDirectoryName')
			->willReturn('appdata_some_id');

		$this->mocks[IRootFolder::class]->expects($this->once())
			->method('get')
			->with('appdata_some_id')
			->willReturn($appDataFolder);

		$this->mocks[LoggerInterface::class]->expects($this->once())->method('error')->with("Previews can't be removed: preview folder isn't deletable");
		$this->output->expects($this->once())->method('writeln')->with("Previews can't be removed: preview folder isn't deletable");

		$this->assertEquals(1, $this->repair->run($this->input, $this->output));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataForTestCleanupWithDeleteException')]
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

		$this->mocks[IRootFolder::class]->expects($this->once())
			->method('getAppDataDirectoryName')
			->willReturn('appdata_some_id');

		$this->mocks[IRootFolder::class]->expects($this->once())
			->method('get')
			->with('appdata_some_id')
			->willReturn($appDataFolder);

		$this->mocks[LoggerInterface::class]->expects($this->once())->method('error')->with($errorMessage);
		$this->output->expects($this->once())->method('writeln')->with($errorMessage);

		$this->assertEquals(1, $this->repair->run($this->input, $this->output));
	}

	public static function dataForTestCleanupWithDeleteException(): array {
		return [
			[NotFoundException::class, "Previews weren't deleted: preview folder was not found while deleting it"],
			[NotPermittedException::class, "Previews weren't deleted: you don't have the permission to delete preview folder"],
		];
	}

	public function testCleanupWithPreviewServiceException(): void {
		$this->mocks[IRootFolder::class]->method('getAppDataDirectoryName')
			->willThrowException(new NotFoundException());

		$this->mocks[PreviewService::class]->expects($this->once())->method('deleteAll')
			->willThrowException(new NotPermittedException('abc'));

		$this->mocks[LoggerInterface::class]->expects($this->once())->method('info')->with("Legacy previews can't be removed: appdata folder can't be found");
		$this->mocks[LoggerInterface::class]->expects($this->once())->method('error')->with("Previews can't be removed: exception occurred: abc");

		$this->assertEquals(1, $this->repair->run($this->input, $this->output));
	}
}

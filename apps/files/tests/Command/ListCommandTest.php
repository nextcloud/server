<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Command;

use OC\Core\Command\Info\FileUtils;
use OCA\Files\Command\ListCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ListCommandTest extends TestCase {
	private FileUtils&MockObject $fileUtils;
	private IUserManager&MockObject $userManager;
	private IOutput&MockObject $output;
	private ListCommand $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->fileUtils = $this->createMock(FileUtils::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->output = $this->createMock(IOutput::class);
		$this->command = new ListCommand($this->fileUtils, $this->userManager);
	}

	public function testFailsWhenNodeIsNotFound(): void {
		$this->fileUtils->method('getNode')->with('/does-not-exist')->willReturn(null);

		$this->output->expects($this->once())
			->method('writeln')
			->with($this->stringContains('not found'));
		$this->output->expects($this->never())->method('writeTableInOutputFormat');

		$result = ($this->command)($this->output, '/does-not-exist');
		$this->assertEquals(ExitCode::Failure, $result);
	}

	public function testFailsWhenNodeIsAFile(): void {
		$file = $this->createMock(File::class);
		$this->fileUtils->method('getNode')->with('/some/file.txt')->willReturn($file);

		$this->output->expects($this->once())
			->method('writeln')
			->with($this->stringContains('is not a folder'));
		$this->output->expects($this->never())->method('writeTableInOutputFormat');

		$result = ($this->command)($this->output, '/some/file.txt');
		$this->assertEquals(ExitCode::Failure, $result);
	}

	public function testListsDirectoryContentsSortedByName(): void {
		$subFolder = $this->createMock(Folder::class);
		$subFolder->method('getId')->willReturn(10);
		$subFolder->method('getName')->willReturn('b-folder');
		$subFolder->method('getSize')->willReturn(1234);
		$subFolder->method('getMTime')->willReturn(1000);
		$subFolder->method('getType')->willReturn('dir');
		$subFolder->method('getPermissions')->willReturn(31);

		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(11);
		$file->method('getName')->willReturn('a-file.txt');
		$file->method('getMimetype')->willReturn('text/plain');
		$file->method('getSize')->willReturn(42);
		$file->method('getMTime')->willReturn(2000);
		$file->method('getType')->willReturn('file');
		$file->method('getPermissions')->willReturn(27);

		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn([$subFolder, $file]);
		$this->fileUtils->method('getNode')->with('/some/folder')->willReturn($folder);
		$this->fileUtils->method('formatPermissions')->willReturnCallback(
			fn (string $type, int $permissions) => "$type:$permissions",
		);

		$this->output->expects($this->once())
			->method('writeTableInOutputFormat')
			->with([
				[
					'fileid' => 11,
					'name' => 'a-file.txt',
					'type' => 'text/plain',
					'size' => 42,
					'mtime' => (new \DateTimeImmutable('@2000'))->format(\DATE_ATOM),
					'permissions' => 'file:27',
				],
				[
					'fileid' => 10,
					'name' => 'b-folder',
					'type' => 'folder',
					'size' => 1234,
					'mtime' => (new \DateTimeImmutable('@1000'))->format(\DATE_ATOM),
					'permissions' => 'dir:31',
				],
			]);

		$result = ($this->command)($this->output, '/some/folder');
		$this->assertEquals(ExitCode::Success, $result);
	}

	public function testListsRegisteredUsersAfterTheRootsOwnEntriesWhenAtTheTrueRoot(): void {
		$appdata = $this->createMock(Folder::class);
		$appdata->method('getId')->willReturn(5);
		$appdata->method('getName')->willReturn('appdata_oc498lxm75hw');
		$appdata->method('getSize')->willReturn(0);
		$appdata->method('getMTime')->willReturn(500);
		$appdata->method('getType')->willReturn('dir');
		$appdata->method('getPermissions')->willReturn(31);

		$root = $this->createMock(Folder::class);
		$root->method('getPath')->willReturn('/');
		$root->method('getDirectoryListing')->willReturn([$appdata]);

		$userB = $this->createMock(IUser::class);
		$userB->method('getUID')->willReturn('bob');
		$userA = $this->createMock(IUser::class);
		$userA->method('getUID')->willReturn('alice');
		$this->userManager->method('search')->with('')->willReturn([$userB, $userA]);

		$bobHome = $this->createMock(Folder::class);
		$bobHome->method('getId')->willReturn(20);
		$bobHome->method('getSize')->willReturn(100);
		$bobHome->method('getMTime')->willReturn(3000);
		$bobHome->method('getType')->willReturn('dir');
		$bobHome->method('getPermissions')->willReturn(31);

		$aliceHome = $this->createMock(Folder::class);
		$aliceHome->method('getId')->willReturn(21);
		$aliceHome->method('getSize')->willReturn(200);
		$aliceHome->method('getMTime')->willReturn(4000);
		$aliceHome->method('getType')->willReturn('dir');
		$aliceHome->method('getPermissions')->willReturn(31);

		$this->fileUtils->method('getNode')->willReturnMap([
			['/', $root],
			['bob/files', $bobHome],
			['alice/files', $aliceHome],
		]);
		$this->fileUtils->method('formatPermissions')->willReturn('full permissions');

		$this->output->expects($this->once())
			->method('writeTableInOutputFormat')
			->with([
				[
					'fileid' => 5,
					'name' => 'appdata_oc498lxm75hw',
					'type' => 'folder',
					'size' => 0,
					'mtime' => (new \DateTimeImmutable('@500'))->format(\DATE_ATOM),
					'permissions' => 'full permissions',
				],
				[
					'fileid' => 21,
					'name' => 'alice',
					'type' => 'folder',
					'size' => 200,
					'mtime' => (new \DateTimeImmutable('@4000'))->format(\DATE_ATOM),
					'permissions' => 'full permissions',
				],
				[
					'fileid' => 20,
					'name' => 'bob',
					'type' => 'folder',
					'size' => 100,
					'mtime' => (new \DateTimeImmutable('@3000'))->format(\DATE_ATOM),
					'permissions' => 'full permissions',
				],
			]);

		$result = ($this->command)($this->output, '/');
		$this->assertEquals(ExitCode::Success, $result);
	}

	public function testSuggestPathsSuggestsMatchingUsernamesForTheFirstSegment(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userManager->method('search')->with('ali')->willReturn([$user]);

		$this->assertEquals(['alice/'], $this->command->suggestPaths('ali'));
	}

	public function testSuggestPathsSuggestsMatchingChildrenForALaterSegment(): void {
		$matchingFile = $this->createMock(File::class);
		$matchingFile->method('getName')->willReturn('report.pdf');

		$matchingFolder = $this->createMock(Folder::class);
		$matchingFolder->method('getName')->willReturn('reports');

		$nonMatching = $this->createMock(File::class);
		$nonMatching->method('getName')->willReturn('other.txt');

		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn([$matchingFile, $matchingFolder, $nonMatching]);
		$this->fileUtils->method('getNode')->with('alice/files')->willReturn($folder);

		$this->assertEquals(
			['alice/files/report.pdf', 'alice/files/reports/'],
			$this->command->suggestPaths('alice/files/re'),
		);
	}

	public function testSuggestPathsReturnsNothingWhenTheParentIsNotAFolder(): void {
		$this->fileUtils->method('getNode')->with('alice/files/report.pdf')->willReturn($this->createMock(File::class));

		$this->assertEquals([], $this->command->suggestPaths('alice/files/report.pdf/x'));
	}
}

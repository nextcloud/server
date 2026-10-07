<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Reference\File;

use OC\Collaboration\Reference\File\FileReferenceProvider;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use Test\TestCase;

class FileReferenceProviderTest extends TestCase {
	private const BASE = 'https://cloud.example.com';

	private File&Stub $file;
	private FileReferenceProvider $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->getAutoMock(IURLGenerator::class)->method('getAbsoluteURL')
			->willReturnCallback(fn (string $url): string => self::BASE . $url);

		$this->file = $this->createStub(File::class);
		$this->file->method('getId')->willReturn(42);
		$this->file->method('getName')->willReturn('report.pdf');
		$this->file->method('getPath')->willReturn('/alice/files/Documents/report.pdf');
		$this->file->method('getMimetype')->willReturn('application/pdf');

		$userFolder = $this->createStub(IUserFolder::class);
		$userFolder->method('getFirstNodeById')->willReturn($this->file);
		$userFolder->method('getRelativePath')->willReturn('/Documents/report.pdf');
		$this->getAutoMock(IRootFolder::class)->method('getUserFolder')->willReturn($userFolder);

		// Read by the constructor, so set before the provider is built
		$user = $this->createStub(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->getAutoMock(IUserSession::class)->method('getUser')->willReturn($user);

		$this->provider = $this->createInstanceWithMocks(FileReferenceProvider::class);
	}

	private function sharedStorage(bool $canDownload): ISharedStorage {
		$share = $this->createStub(IShare::class);
		$share->method('canDownload')->willReturn($canDownload);
		$storage = $this->createStub(ISharedStorage::class);
		$storage->method('instanceOfStorage')->willReturnCallback(fn (string $class): bool => $class === ISharedStorage::class);
		$storage->method('getShare')->willReturn($share);
		return $storage;
	}

	public static function dataFiles(): array {
		return [
			'own file' => [false, true, Constants::PERMISSION_ALL, 'no'],
			'received share' => [true, true, Constants::PERMISSION_READ, 'no'],
			'received share that forbids downloading' => [true, false, Constants::PERMISSION_READ, 'yes'],
		];
	}

	/**
	 * What the user may do with the file, for a preview to offer only that
	 */
	#[DataProvider('dataFiles')]
	public function testTellsWhatTheUserMayDo(bool $shared, bool $canDownload, int $permissions, string $hideDownload): void {
		$this->file->method('getStorage')->willReturn($shared ? $this->sharedStorage($canDownload) : $this->createStub(IStorage::class));
		$this->file->method('getPermissions')->willReturn($permissions);

		$reference = $this->provider->resolveReference(self::BASE . '/index.php/f/42');

		$file = $reference?->getRichObject();
		$this->assertSame($permissions, $file['permissions'] ?? null);
		$this->assertSame($hideDownload, $file['hide-download'] ?? null);
	}
}

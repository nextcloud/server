<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Search;

use OC\Search\FilterCollection;
use OC\Search\SearchQuery;
use OCA\Files\Search\FilesSearchProvider;
use OCP\Files\File;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FilesSearchProviderTest extends TestCase {
	private IRootFolder&MockObject $rootFolder;
	private FilesSearchProvider $provider;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$mimeTypeDetector->method('mimeTypeIcon')->willReturn('icon-text');
		$this->rootFolder = $this->createMock(IRootFolder::class);

		$this->provider = new FilesSearchProvider(
			$l10n,
			$this->createMock(IURLGenerator::class),
			$mimeTypeDetector,
			$this->rootFolder,
			$this->createMock(IPreview::class),
		);
	}

	public function testSearchExposesLastModified(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(42);
		$file->method('getName')->willReturn('report.txt');
		$file->method('getPath')->willReturn('/alice/files/Docs/report.txt');
		$file->method('getMimetype')->willReturn('text/plain');
		$file->method('getMTime')->willReturn(1761866199);

		$userFolder = $this->createMock(IUserFolder::class);
		$userFolder->method('search')->willReturn([$file]);
		$userFolder->method('getRelativePath')->with('/alice/files/Docs/report.txt')->willReturn('/Docs/report.txt');
		$this->rootFolder->method('getUserFolder')->with('alice')->willReturn($userFolder);

		$result = $this->provider->search($user, new SearchQuery(new FilterCollection()))->jsonSerialize();

		$this->assertCount(1, $result['entries']);
		$this->assertSame([
			'fileId' => '42',
			'path' => '/Docs/report.txt',
			'lastModified' => '2025-10-30T23:16:39+00:00',
		], $result['entries'][0]->jsonSerialize()['attributes']);
	}
}

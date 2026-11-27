<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Search;

use OC\Search\Filter\StringFilter;
use OC\Search\FilterCollection;
use OC\Search\SearchQuery;
use OCA\Files\Search\FilesSearchProvider;
use OCP\Files\File;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchQuery;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\IUser;
use PHPUnit\Framework\Attributes\DataProvider;
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

	public static function termProvider(): array {
		return [
			'single token' => ['first', ['%first%']],
			'adjacent tokens' => ['first second', ['%first%', '%second%']],
			'non-adjacent tokens' => ['first third', ['%first%', '%third%']],
			'reversed tokens' => ['third first', ['%third%', '%first%']],
			'three tokens' => ['first second third', ['%first%', '%second%', '%third%']],
			'repeated token' => ['first first', ['%first%']],
			'multiple spaces' => ['  first    third  ', ['%first%', '%third%']],
			'tab separator' => ["first\tthird", ['%first%', '%third%']],
			'whitespace only' => ['   ', ['%   %']],
			'invalid utf-8' => ["a\xB1 b", ["%a\xB1 b%"]],
		];
	}

	#[DataProvider('termProvider')]
	public function testTermIsSplitIntoNameComparisons(string $term, array $expectedLikes): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$userFolder = $this->createMock(IUserFolder::class);
		$userFolder->expects($this->once())
			->method('search')
			->with($this->callback(function (ISearchQuery $query) use ($expectedLikes): bool {
				$operator = $query->getSearchOperation();
				$this->assertInstanceOf(ISearchBinaryOperator::class, $operator);
				$this->assertSame(ISearchBinaryOperator::OPERATOR_AND, $operator->getType());
				$likes = array_map(function (ISearchComparison $comparison): string {
					$this->assertSame(ISearchComparison::COMPARE_LIKE, $comparison->getType());
					$this->assertSame('name', $comparison->getField());
					return $comparison->getValue();
				}, $operator->getArguments());
				$this->assertSame($expectedLikes, $likes);
				return true;
			}))
			->willReturn([]);
		$this->rootFolder->method('getUserFolder')->with('alice')->willReturn($userFolder);

		$this->provider->search($user, new SearchQuery(new FilterCollection(...['term' => new StringFilter($term)])));
	}
}

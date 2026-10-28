<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Josh Duffy <joshduff.jd@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Search;

use ArrayIterator;
use OC\Files\Search\FilesSearchQuery;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchQuery as FilesQuery;
use OC\Search\Filter\StringFilter;
use OC\SystemConfig;
use OCA\Files\Search\FilesSearchProvider;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\Search\ISearchQuery as IFilesSearchQuery;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IFilter;
use OCP\Search\IFilterCollection;
use OCP\Search\ISearchQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class FilesSearchProviderTest extends TestCase {
	/**
	 * @param array<string, IFilter> $filters
	 */
	private function searchAndCaptureQuery(bool $enabled, array $filters, int $sortOrder = ISearchQuery::SORT_DATE_DESC, int $limit = 50, int|string|null $cursor = 0): FilesQuery {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$folder = $this->createMock(IUserFolder::class);
		$captured = null;
		$folder->expects($this->once())
			->method('search')
			->willReturnCallback(function (IFilesSearchQuery $query) use (&$captured): array {
				$captured = $query;
				return [];
			});

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('user')
			->willReturn($folder);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$config = $this->createMock(SystemConfig::class);
		$config->method('getValue')
			->with('files_search_bounded_name', false)
			->willReturn($enabled);

		$provider = new FilesSearchProvider(
			$l10n,
			$this->createMock(IURLGenerator::class),
			$this->createMock(IMimeTypeDetector::class),
			$rootFolder,
			$this->createMock(IPreview::class),
			$config,
		);
		$query = $this->createMock(ISearchQuery::class);
		$query->method('getFilters')->willReturn($this->filters($filters));
		$query->method('getSortOrder')->willReturn($sortOrder);
		$query->method('getLimit')->willReturn($limit);
		$query->method('getCursor')->willReturn($cursor);
		$provider->search($user, $query);

		self::assertInstanceOf(FilesQuery::class, $captured);
		return $captured;
	}

	/** @param array<string, IFilter> $filters */
	private function filters(array $filters): IFilterCollection {
		return new class($filters) implements IFilterCollection {
			/** @param array<string, IFilter> $filters */
			public function __construct(
				private array $filters,
			) {
			}

			#[\Override]
			public function has(string $name): bool {
				return isset($this->filters[$name]);
			}

			#[\Override]
			public function get(string $name): ?IFilter {
				return $this->filters[$name] ?? null;
			}

			#[\Override]
			public function getIterator(): \Traversable {
				return new ArrayIterator($this->filters);
			}

			#[\Override]
			public function count(): int {
				return count($this->filters);
			}
		};
	}

	public function testBoundedSearchIsOffByDefault(): void {
		$query = $this->searchAndCaptureQuery(false, ['term' => new StringFilter('invoice')]);

		self::assertNotInstanceOf(FilesSearchQuery::class, $query);
		self::assertSame(1, count($query->getOrder()));
		self::assertSame('mtime', $query->getOrder()[0]->getField());
	}

	public function testEnabledSoleTermUsesBoundedQueryAndStableOrder(): void {
		$query = $this->searchAndCaptureQuery(true, ['term' => new StringFilter('invoice')], ISearchQuery::SORT_DATE_DESC, 25, 12);

		self::assertInstanceOf(FilesSearchQuery::class, $query);
		self::assertSame(25, $query->getLimit());
		self::assertSame(12, $query->getOffset());
		self::assertCount(2, $query->getOrder());
		self::assertSame('mtime', $query->getOrder()[0]->getField());
		self::assertSame(ISearchOrder::DIRECTION_DESCENDING, $query->getOrder()[0]->getDirection());
		self::assertSame('fileid', $query->getOrder()[1]->getField());
		self::assertSame(ISearchOrder::DIRECTION_DESCENDING, $query->getOrder()[1]->getDirection());
		$operation = $query->getSearchOperation();
		self::assertInstanceOf(SearchBinaryOperator::class, $operation);
		self::assertSame(ISearchBinaryOperator::OPERATOR_AND, $operation->getType());
		self::assertCount(1, $operation->getArguments());
		self::assertInstanceOf(SearchComparison::class, $operation->getArguments()[0]);
		self::assertSame(ISearchComparison::COMPARE_LIKE, $operation->getArguments()[0]->getType());
		self::assertSame('name', $operation->getArguments()[0]->getField());
		self::assertSame('%invoice%', $operation->getArguments()[0]->getValue());
	}

	/**
	 * @return array<string, array{array<string, IFilter>, int, int, int|string|null}>
	 */
	public static function boundedFallbackProvider(): array {
		return [
			'non-date sort' => [['term' => new StringFilter('invoice')], 0, 25, 0],
			'extra request filter' => [['term' => new StringFilter('invoice'), 'type' => new StringFilter('text')], ISearchQuery::SORT_DATE_DESC, 25, 0],
			'zero limit' => [['term' => new StringFilter('invoice')], ISearchQuery::SORT_DATE_DESC, 0, 0],
			'limit over maximum' => [['term' => new StringFilter('invoice')], ISearchQuery::SORT_DATE_DESC, 101, 0],
			'negative cursor' => [['term' => new StringFilter('invoice')], ISearchQuery::SORT_DATE_DESC, 25, -1],
			'cursor beyond bounded window' => [['term' => new StringFilter('invoice')], ISearchQuery::SORT_DATE_DESC, 100, 925],
		];
	}

	#[DataProvider('boundedFallbackProvider')]
	public function testEnabledSearchFallsBackWhenRequestIsOutsideBoundedContract(array $filters, int $sortOrder, int $limit, int|string|null $cursor): void {
		$query = $this->searchAndCaptureQuery(true, $filters, $sortOrder, $limit, $cursor);

		self::assertNotInstanceOf(FilesSearchQuery::class, $query);
		self::assertCount($sortOrder === ISearchQuery::SORT_DATE_DESC ? 1 : 0, $query->getOrder());
	}
}

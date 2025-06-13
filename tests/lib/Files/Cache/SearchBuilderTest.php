<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache;

use OC\DB\QueryBuilder\Literal;
use OC\Files\Cache\SearchBuilder;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class SearchBuilderTest extends TestCase {
	/** @var IQueryBuilder */
	private $builder;

	/** @var IMimeTypeLoader&\PHPUnit\Framework\MockObject\MockObject */
	private $mimetypeLoader;

	/** @var IFilesMetadataManager&\PHPUnit\Framework\MockObject\MockObject */
	private $filesMetadataManager;

	/** @var SearchBuilder */
	private $searchBuilder;

	/** @var integer */
	private $numericStorageId;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = Server::get(IDBConnection::class)->getQueryBuilder();
		$this->mimetypeLoader = $this->createMock(IMimeTypeLoader::class);
		$this->filesMetadataManager = $this->createMock(IFilesMetadataManager::class);

		$this->mimetypeLoader->expects($this->any())
			->method('getId')
			->willReturnMap([
				['text', 1],
				['text/plain', 2],
				['text/xml', 3],
				['image/jpg', 4],
				['image/png', 5],
				['image', 6],
			]);

		$this->mimetypeLoader->expects($this->any())
			->method('getMimetypeById')
			->willReturnMap([
				[1, 'text'],
				[2, 'text/plain'],
				[3, 'text/xml'],
				[4, 'image/jpg'],
				[5, 'image/png'],
				[6, 'image']
			]);

		$this->searchBuilder = new SearchBuilder($this->mimetypeLoader, $this->filesMetadataManager);
		$this->numericStorageId = 10000;

		$this->builder->select(['fileid'])
			->from('filecache', 'file') // alias needed for QuerySearchHelper#getOperatorFieldAndValue
			->where($this->builder->expr()->eq('storage', new Literal($this->numericStorageId)));
	}

	protected function tearDown(): void {
		parent::tearDown();

		$builder = Server::get(IDBConnection::class)->getQueryBuilder();

		$builder->delete('filecache')
			->where($builder->expr()->eq('storage', $builder->createNamedParameter($this->numericStorageId, IQueryBuilder::PARAM_INT)));

		$builder->execute();
	}

	private function addCacheEntry(array $data) {
		$data['storage'] = $this->numericStorageId;
		$data['etag'] = 'unimportant';
		$data['storage_mtime'] = $data['mtime'];
		if (!isset($data['path'])) {
			$data['path'] = 'random/' . $this->getUniqueID();
		}
		$data['path_hash'] = md5($data['path']);
		if (!isset($data['mtime'])) {
			$data['mtime'] = 100;
		}
		if (!isset($data['size'])) {
			$data['size'] = 100;
		}
		$data['name'] = basename($data['path']);
		$data['parent'] = -1;
		if (isset($data['mimetype'])) {
			[$mimepart,] = explode('/', $data['mimetype']);
			$data['mimepart'] = $this->mimetypeLoader->getId($mimepart);
			$data['mimetype'] = $this->mimetypeLoader->getId($data['mimetype']);
		} else {
			$data['mimepart'] = 1;
			$data['mimetype'] = 1;
		}

		$builder = Server::get(IDBConnection::class)->getQueryBuilder();

		$values = [];
		foreach ($data as $key => $value) {
			$values[$key] = $builder->createNamedParameter($value);
		}

		$builder->insert('filecache')
			->values($values)
			->execute();

		return $builder->getLastInsertId();
	}

	private function search(ISearchOperator $operator) {
		$dbOperator = $this->searchBuilder->searchOperatorToDBExpr($this->builder, $operator);
		$this->builder->andWhere($dbOperator);

		$result = $this->builder->execute();
		$rows = $result->fetchAll(\PDO::FETCH_COLUMN);
		$result->closeCursor();

		return $rows;
	}

	public static function comparisonProvider(): array {
		return [
			[new SearchComparison(ISearchComparison::COMPARE_GREATER_THAN, 'mtime', 125), [1]],
			[new SearchComparison(ISearchComparison::COMPARE_LESS_THAN, 'mtime', 125), [0]],
			[new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'size', 125), []],
			[new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'size', 50), [0, 1]],
			[new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'name', 'foobar'), [0]],
			[new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', 'foo%'), [0, 1]],
			[new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', 'image/jpg'), [0]],
			[new SearchComparison(ISearchComparison::COMPARE_LIKE, 'mimetype', 'image/%'), [0, 1]],
			[new SearchComparison(ISearchComparison::COMPARE_IN, 'mimetype', ['image/jpg', 'image/png']), [0, 1]],
			[new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'size', 50),
				new SearchComparison(ISearchComparison::COMPARE_LESS_THAN, 'mtime', 125)
			]), [0]],
			[new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'size', 50),
				new SearchComparison(ISearchComparison::COMPARE_LESS_THAN, 'mtime', 125),
				new SearchComparison(ISearchComparison::COMPARE_LIKE, 'mimetype', 'text/%')
			]), []],
			[new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mtime', 100),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mtime', 150),
			]), [0, 1]],
			[new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mtime', 150),
			]), [0]],
			[new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
				new SearchComparison(ISearchComparison::COMPARE_GREATER_THAN, 'mtime', 125),
			]), [0]],
			[new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
				new SearchComparison(ISearchComparison::COMPARE_LESS_THAN, 'mtime', 125),
			]), [1]],
			[new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
				new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', '%bar'),
			]), [1]],

		];
	}

	/**
	 * @dataProvider comparisonProvider
	 *
	 * @param ISearchOperator $operator
	 * @param array $fileIds
	 */
	public function testComparison(ISearchOperator $operator, array $fileIds): void {
		$fileId = [];
		$fileId[] = $this->addCacheEntry([
			'path' => 'foobar',
			'mtime' => 100,
			'size' => 50,
			'mimetype' => 'image/jpg'
		]);

		$fileId[] = $this->addCacheEntry([
			'path' => 'fooasd',
			'mtime' => 150,
			'size' => 50,
			'mimetype' => 'image/png'
		]);

		$fileIds = array_map(function ($i) use ($fileId) {
			return $fileId[$i];
		}, $fileIds);

		$results = $this->search($operator);

		sort($fileIds);
		sort($results);

		$this->assertEquals($fileIds, $results);
	}
}

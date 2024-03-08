<?php

namespace Test\Files\Search;

use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchQuery;
use OC\Files\Storage\Temporary;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use Test\TestCase;

/**
 * @group DB
 */
class SearchIntegrationTest extends TestCase {
	private $cache;
	private $storage;

	protected function setUp(): void {
		parent::setUp();

		$this->storage = new Temporary([]);
		$this->cache = $this->storage->getCache();
		$this->storage->getScanner()->scan('');
	}


	public function testThousandAndOneFilters() {
		$id = $this->cache->put("file10", ['size' => 1, 'mtime' => 50, 'mimetype' => 'foo/folder']);

		$comparisons = [];
		for($i = 1; $i <= 1001; $i++) {
			$comparisons[] = new SearchComparison(ISearchComparison::COMPARE_EQUAL, "name", "file$i");
		}
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, $comparisons);
		$query = new SearchQuery($operator, 10, 0, []);

		$results = $this->cache->searchQuery($query);

		$this->assertCount(1, $results);
		$this->assertEquals($id, $results[0]->getId());
	}
}

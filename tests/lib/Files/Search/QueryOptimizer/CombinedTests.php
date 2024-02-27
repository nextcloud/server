<?php

namespace Test\Files\Search\QueryOptimizer;

use OC\Files\Search\QueryOptimizer\QueryOptimizer;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use Test\TestCase;

class CombinedTests extends TestCase {
	private QueryOptimizer $optimizer;

	protected function setUp(): void {
		parent::setUp();

		$this->optimizer = new QueryOptimizer();
	}

	public function testBasicOrOfAnds() {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, "storage", 1),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, "path", "foo"),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, "storage", 1),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, "path", "bar"),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, "storage", 1),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, "path", "asd"),
				])
			]
		);
		$this->assertEquals('((storage eq 1 and path eq "foo") or (storage eq 1 and path eq "bar") or (storage eq 1 and path eq "asd"))', $operator->__toString());

		$this->optimizer->processOperator($operator);

		$this->assertEquals('(storage eq 1 and path in ["foo","bar","asd"])', $operator->__toString());
	}
}

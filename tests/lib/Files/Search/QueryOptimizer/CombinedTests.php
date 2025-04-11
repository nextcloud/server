<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
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

	public function testBasicOrOfAnds(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 1),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 1),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'bar'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 1),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'asd'),
				])
			]
		);
		$this->assertEquals('((storage eq 1 and path eq "foo") or (storage eq 1 and path eq "bar") or (storage eq 1 and path eq "asd"))', $operator->__toString());

		$this->optimizer->processOperator($operator);

		$this->assertEquals('(storage eq 1 and path in ["foo","bar","asd"])', $operator->__toString());
	}

	public function testComplexSearchPattern1(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 1),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 2),
					new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
						new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', '201'),
						new SearchComparison(ISearchComparison::COMPARE_LIKE, 'path', '201/%'),
					]),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 3),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', '301'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 4),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', '401'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 3),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', '302'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 4),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', '402'),
				]),
			]
		);
		$this->assertEquals('((storage eq 1) or (storage eq 2 and (path eq "201" or path like "201\/%")) or (storage eq 3 and path eq "301") or (storage eq 4 and path eq "401") or (storage eq 3 and path eq "302") or (storage eq 4 and path eq "402"))', $operator->__toString());

		$this->optimizer->processOperator($operator);

		$this->assertEquals('(storage eq 1 or (storage eq 2 and (path eq "201" or path like "201\/%")) or (storage eq 3 and path in ["301","302"]) or (storage eq 4 and path in ["401","402"]))', $operator->__toString());
	}

	public function testComplexSearchPattern2(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 1),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 2),
					new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
						new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', '201'),
						new SearchComparison(ISearchComparison::COMPARE_LIKE, 'path', '201/%'),
					]),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 3),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', '301'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 4),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', '401'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 3),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', '302'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 4),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', '402'),
				]),
			]
		);
		$this->assertEquals('(storage eq 1 or (storage eq 2 and (path eq "201" or path like "201\/%")) or (storage eq 3 and path eq "301") or (storage eq 4 and path eq "401") or (storage eq 3 and path eq "302") or (storage eq 4 and path eq "402"))', $operator->__toString());

		$this->optimizer->processOperator($operator);

		$this->assertEquals('(storage eq 1 or (storage eq 2 and (path eq "201" or path like "201\/%")) or (storage eq 3 and path in ["301","302"]) or (storage eq 4 and path in ["401","402"]))', $operator->__toString());
	}

	public function testApplySearchConstraints1(): void {
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', 'image/png'),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', 'image/jpeg'),
				]),
			]),
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 1),
					new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
						new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'files'),
						new SearchComparison(ISearchComparison::COMPARE_LIKE, 'path', 'files/%'),
					]),
				]),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 2),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 3),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'files/301'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 3),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'files/302'),
				]),
			]),
		]);
		$this->assertEquals('(((mimetype eq "image\/png" or mimetype eq "image\/jpeg")) and ((storage eq 1 and (path eq "files" or path like "files\/%")) or storage eq 2 or (storage eq 3 and path eq "files\/301") or (storage eq 3 and path eq "files\/302")))', $operator->__toString());

		$this->optimizer->processOperator($operator);

		$this->assertEquals('(mimetype in ["image\/png","image\/jpeg"] and ((storage eq 1 and (path eq "files" or path like "files\/%")) or storage eq 2 or (storage eq 3 and path in ["files\/301","files\/302"])))', $operator->__toString());
	}

	public function testApplySearchConstraints2(): void {
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', 'image/png'),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', 'image/jpeg'),
				]),
			]),
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 1),
					new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
						new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'files'),
						new SearchComparison(ISearchComparison::COMPARE_LIKE, 'path', 'files/%'),
					]),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 2),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 3),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'files/301'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 3),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'files/302'),
				]),
			]),
		]);
		$this->assertEquals('(((mimetype eq "image\/png" or mimetype eq "image\/jpeg")) and ((storage eq 1 and (path eq "files" or path like "files\/%")) or (storage eq 2) or (storage eq 3 and path eq "files\/301") or (storage eq 3 and path eq "files\/302")))', $operator->__toString());

		$this->optimizer->processOperator($operator);

		$this->assertEquals('(mimetype in ["image\/png","image\/jpeg"] and ((storage eq 1 and (path eq "files" or path like "files\/%")) or storage eq 2 or (storage eq 3 and path in ["files\/301","files\/302"])))', $operator->__toString());
	}
}

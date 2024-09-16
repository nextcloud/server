<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\Files\Search\QueryOptimizer;

use OC\Files\Search\QueryOptimizer\FlattenSingleArgumentBinaryOperation;
use OC\Files\Search\QueryOptimizer\MergeDistributiveOperations;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use Test\TestCase;

class MergeDistributiveOperationsTest extends TestCase {
	private $optimizer;
	private $simplifier;

	protected function setUp(): void {
		parent::setUp();

		$this->optimizer = new MergeDistributiveOperations();
		$this->simplifier = new FlattenSingleArgumentBinaryOperation();
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
		$this->simplifier->processOperator($operator);

		$this->assertEquals('(storage eq 1 and (path eq "foo" or path eq "bar" or path eq "asd"))', $operator->__toString());
	}

	public function testDontTouchIfNotSame(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 1),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 2),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'bar'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 3),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'asd'),
				])
			]
		);
		$this->assertEquals('((storage eq 1 and path eq "foo") or (storage eq 2 and path eq "bar") or (storage eq 3 and path eq "asd"))', $operator->__toString());

		$this->optimizer->processOperator($operator);
		$this->simplifier->processOperator($operator);

		$this->assertEquals('((storage eq 1 and path eq "foo") or (storage eq 2 and path eq "bar") or (storage eq 3 and path eq "asd"))', $operator->__toString());
	}

	public function testMergePartial(): void {
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
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 2),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'asd'),
				])
			]
		);
		$this->assertEquals('((storage eq 1 and path eq "foo") or (storage eq 1 and path eq "bar") or (storage eq 2 and path eq "asd"))', $operator->__toString());

		$this->optimizer->processOperator($operator);
		$this->simplifier->processOperator($operator);

		$this->assertEquals('((storage eq 1 and (path eq "foo" or path eq "bar")) or (storage eq 2 and path eq "asd"))', $operator->__toString());
	}

	public function testOptimizeInside(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_AND,
			[
				new SearchBinaryOperator(
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
				),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', 'text')
			]
		);
		$this->assertEquals('(((storage eq 1 and path eq "foo") or (storage eq 1 and path eq "bar") or (storage eq 1 and path eq "asd")) and mimetype eq "text")', $operator->__toString());

		$this->optimizer->processOperator($operator);
		$this->simplifier->processOperator($operator);

		$this->assertEquals('((storage eq 1 and (path eq "foo" or path eq "bar" or path eq "asd")) and mimetype eq "text")', $operator->__toString());
	}

	public function testMoveInnerOperations(): void {
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
					new SearchComparison(ISearchComparison::COMPARE_GREATER_THAN, 'size', '100'),
				])
			]
		);
		$this->assertEquals('((storage eq 1 and path eq "foo") or (storage eq 1 and path eq "bar") or (storage eq 1 and path eq "asd" and size gt "100"))', $operator->__toString());

		$this->optimizer->processOperator($operator);
		$this->simplifier->processOperator($operator);

		$this->assertEquals('(storage eq 1 and (path eq "foo" or path eq "bar" or (path eq "asd" and size gt "100")))', $operator->__toString());
	}
}

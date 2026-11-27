<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Search\QueryOptimizer;

use OC\Files\Search\QueryOptimizer\FlattenSingleArgumentBinaryOperation;
use OC\Files\Search\QueryOptimizer\RepeatMergeDistributiveOperations;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use Test\TestCase;

class RepeatMergeDistributiveOperationsTest extends TestCase {
	private $optimizer;
	private $simplifier;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->optimizer = new RepeatMergeDistributiveOperations();
		$this->simplifier = new FlattenSingleArgumentBinaryOperation();
	}

	public function testBasicOrOfAndsMixedArgument(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 1),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 2),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 3),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 4),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'bar'),
				]),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', 4),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'asd'),
				])
			]
		);
		$this->assertEquals('((storage eq 1 and path eq "foo") or (storage eq 2 and path eq "foo") or (storage eq 3 and path eq "foo") or (storage eq 4 and path eq "bar") or (storage eq 4 and path eq "asd"))', $operator->__toString());

		$this->optimizer->processOperator($operator);
		$this->simplifier->processOperator($operator);

		$this->assertEquals('((path eq "foo" and (storage eq 1 or storage eq 2 or storage eq 3)) or (storage eq 4 and (path eq "bar" or path eq "asd")))', $operator->__toString());
	}
}

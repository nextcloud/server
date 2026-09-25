<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Search\QueryOptimizer;

use OC\Files\Search\QueryOptimizer\PushDownNegation;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use Test\TestCase;

class PushDownNegationTest extends TestCase {
	private PushDownNegation $optimizer;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->optimizer = new PushDownNegation();
	}

	public function testNotOfComparisonIsLeftAlone(): void {
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
			new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
		]);

		$this->optimizer->processOperator($operator);

		$this->assertEquals('(not path eq "foo")', $operator->__toString());
	}

	public function testNotOfAndBecomesOrOfNots(): void {
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'bar'),
			]),
		]);

		$this->optimizer->processOperator($operator);

		$this->assertEquals('((not path eq "foo") or (not path eq "bar"))', $operator->__toString());
	}

	public function testNotOfOrBecomesAndOfNots(): void {
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'bar'),
			]),
		]);

		$this->optimizer->processOperator($operator);

		$this->assertEquals('((not path eq "foo") and (not path eq "bar"))', $operator->__toString());
	}

	public function testDoubleNegationCancelsOut(): void {
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
			]),
		]);

		$this->optimizer->processOperator($operator);

		$this->assertEquals('path eq "foo"', $operator->__toString());
	}

	public function testTripleNegationLeavesASingleNot(): void {
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				]),
			]),
		]);

		$this->optimizer->processOperator($operator);

		$this->assertEquals('(not path eq "foo")', $operator->__toString());
	}

	public function testNegatedNestedGroupIsFullyPushedDown(): void {
		// not (a and (b or c)) == (not a) or ((not b) and (not c))
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'a'),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'b'),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'c'),
				]),
			]),
		]);

		$this->optimizer->processOperator($operator);

		$this->assertEquals(
			'((not path eq "a") or ((not path eq "b") and (not path eq "c")))',
			$operator->__toString(),
		);
	}
}

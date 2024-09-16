<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\Files\Search\QueryOptimizer;

use OC\Files\Search\QueryOptimizer\FlattenNestedBool;
use OC\Files\Search\QueryOptimizer\FlattenSingleArgumentBinaryOperation;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use Test\TestCase;

class FlattenNestedBoolTest extends TestCase {
	private $optimizer;
	private $simplifier;

	protected function setUp(): void {
		parent::setUp();

		$this->optimizer = new FlattenNestedBool();
		$this->simplifier = new FlattenSingleArgumentBinaryOperation();
	}

	public function testOrs(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'bar'),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'asd'),
				])
			]
		);
		$this->assertEquals('(path eq "foo" or (path eq "bar" or path eq "asd"))', $operator->__toString());

		$this->optimizer->processOperator($operator);
		$this->simplifier->processOperator($operator);

		$this->assertEquals('(path eq "foo" or path eq "bar" or path eq "asd")', $operator->__toString());
	}
}

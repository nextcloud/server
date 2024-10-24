<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\Files\Search\QueryOptimizer;

use OC\Files\Search\QueryOptimizer\FlattenSingleArgumentBinaryOperation;
use OC\Files\Search\QueryOptimizer\OrEqualsToIn;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use Test\TestCase;

class OrEqualsToInTest extends TestCase {
	private $optimizer;
	private $simplifier;

	protected function setUp(): void {
		parent::setUp();

		$this->optimizer = new OrEqualsToIn();
		$this->simplifier = new FlattenSingleArgumentBinaryOperation();
	}

	public function testOrs(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'bar'),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'asd'),
			]
		);
		$this->assertEquals('(path eq "foo" or path eq "bar" or path eq "asd")', $operator->__toString());

		$this->optimizer->processOperator($operator);
		$this->simplifier->processOperator($operator);

		$this->assertEquals('path in ["foo","bar","asd"]', $operator->__toString());
	}

	public function testOrsMultipleFields(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'bar'),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'fileid', 1),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'fileid', 2),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', 'asd'),
			]
		);
		$this->assertEquals('(path eq "foo" or path eq "bar" or fileid eq 1 or fileid eq 2 or mimetype eq "asd")', $operator->__toString());

		$this->optimizer->processOperator($operator);
		$this->simplifier->processOperator($operator);

		$this->assertEquals('(path in ["foo","bar"] or fileid in [1,2] or mimetype eq "asd")', $operator->__toString());
	}

	public function testPreserveHints(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'bar'),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'asd'),
			]
		);
		foreach ($operator->getArguments() as $argument) {
			$argument->setQueryHint(ISearchComparison::HINT_PATH_EQ_HASH, false);
		}
		$this->assertEquals('(path eq "foo" or path eq "bar" or path eq "asd")', $operator->__toString());

		$this->optimizer->processOperator($operator);
		$this->simplifier->processOperator($operator);

		$this->assertEquals('path in ["foo","bar","asd"]', $operator->__toString());
		$this->assertEquals(false, $operator->getQueryHint(ISearchComparison::HINT_PATH_EQ_HASH, true));
	}

	public function testOrSomeEq(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
				new SearchComparison(ISearchComparison::COMPARE_LIKE, 'path', 'foo%'),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'bar'),
			]
		);
		$this->assertEquals('(path eq "foo" or path like "foo%" or path eq "bar")', $operator->__toString());

		$this->optimizer->processOperator($operator);
		$this->simplifier->processOperator($operator);

		$this->assertEquals('(path in ["foo","bar"] or path like "foo%")', $operator->__toString());
	}

	public function testOrsInside(): void {
		$operator = new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_AND,
			[
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', 'text'),
				new SearchBinaryOperator(
					ISearchBinaryOperator::OPERATOR_OR,
					[
						new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'foo'),
						new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'bar'),
						new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', 'asd'),
					]
				)
			]
		);
		$this->assertEquals('(mimetype eq "text" and (path eq "foo" or path eq "bar" or path eq "asd"))', $operator->__toString());

		$this->optimizer->processOperator($operator);
		$this->simplifier->processOperator($operator);

		$this->assertEquals('(mimetype eq "text" and path in ["foo","bar","asd"])', $operator->__toString());
	}
}

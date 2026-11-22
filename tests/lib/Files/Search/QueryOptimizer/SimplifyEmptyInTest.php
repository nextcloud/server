<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Search\QueryOptimizer;

use OC\Files\Search\QueryOptimizer\QueryOptimizer;
use OC\Files\Search\QueryOptimizer\SimplifyEmptyIn;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class SimplifyEmptyInTest extends TestCase {
	private static function eq(string $path): SearchComparison {
		return new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', $path);
	}

	private static function nothing(): SearchComparison {
		return new SearchComparison(ISearchComparison::COMPARE_IN, 'fileid', []);
	}

	private static function everything(): SearchBinaryOperator {
		return new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [self::nothing()]);
	}

	private static function and(ISearchOperator ...$arguments): SearchBinaryOperator {
		return new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, $arguments);
	}

	private static function or(ISearchOperator ...$arguments): SearchBinaryOperator {
		return new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, $arguments);
	}

	public static function operatorProvider(): array {
		return [
			'and with nothing' => [self::and(self::eq('a'), self::nothing()), 'fileid in []'],
			'and with everything' => [self::and(self::eq('a'), self::everything(), self::eq('b')), '(path eq "a" and path eq "b")'],
			'and with only everything' => [self::and(self::everything(), self::everything()), '(not fileid in [])'],
			'or with nothing' => [self::or(self::eq('a'), self::nothing()), 'path eq "a"'],
			'or with everything' => [self::or(self::eq('a'), self::everything()), '(not fileid in [])'],
			'or with only nothing' => [self::or(self::nothing(), self::nothing()), 'fileid in []'],
			'nested' => [self::and(self::eq('a'), self::or(self::nothing(), self::nothing())), 'fileid in []'],
			'non-empty in is kept' => [
				self::and(self::eq('a'), new SearchComparison(ISearchComparison::COMPARE_IN, 'fileid', [1])),
				'(path eq "a" and fileid in [1])',
			],
		];
	}

	#[DataProvider('operatorProvider')]
	public function testSimplify(ISearchOperator $operator, string $expected): void {
		(new SimplifyEmptyIn())->processOperator($operator);

		$this->assertEquals($expected, $operator->__toString());
	}

	public function testNegatedGroupWithNothingMatchesEverythingAfterFullPipeline(): void {
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
			self::and(self::eq('a'), self::nothing()),
		]);

		(new QueryOptimizer())->processOperator($operator);

		$this->assertEquals('(not fileid in [])', $operator->__toString());
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\DB\QueryBuilder;

use OC\DB\QueryBuilder\Literal;
use OC\DB\QueryBuilder\Parameter;
use OC\DB\QueryBuilder\QuoteHelper;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
use PHPUnit\Framework\Attributes\DataProvider;

class QuoteHelperTest extends \Test\TestCase {
	protected QuoteHelper $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new QuoteHelper();
	}

	public static function dataQuoteColumnName(): array {
		return [
			['column', '`column`'],
			[new Literal('literal'), 'literal'],
			[new Literal(1), '1'],
			[new Parameter(':param'), ':param'],

			// (string) 'null' is Doctrines way to set columns to null
			// See https://github.com/owncloud/core/issues/19314
			['null', 'null'],
		];
	}

	#[DataProvider(methodName: 'dataQuoteColumnName')]
	public function testQuoteColumnName(string|Literal|Parameter $input, string $expected): void {
		$this->assertSame(
			$expected,
			$this->helper->quoteColumnName($input)
		);
	}

	public static function dataQuoteColumnNames(): array {
		return [
			// Single case
			['d.column', '`d`.`column`'],
			['column', '`column`'],
			[new Literal('literal'), 'literal'],
			[new Literal(1), '1'],
			[new Parameter(':param'), ':param'],

			// Array case
			[['column'], ['`column`']],
			[[new Literal('literal')], ['literal']],
			[[new Literal(1)], ['1']],
			[[new Parameter(':param')], [':param']],

			// Array mixed cases
			[['column1', 'column2'], ['`column1`', '`column2`']],
			[['column', new Literal('literal')], ['`column`', 'literal']],
			[['column', new Literal(1)], ['`column`', '1']],
			[['column', new Parameter(':param')], ['`column`', ':param']],
		];
	}

	#[DataProvider(methodName: 'dataQuoteColumnNames')]
	public function testQuoteColumnNames(string|Literal|Parameter|array $input, string|array $expected): void {
		$this->assertSame(
			$expected,
			$this->helper->quoteColumnNames($input)
		);
	}

	public function quoteColumnNames(array|string|ILiteral|IParameter $strings): array|string {
		if (!is_array($strings)) {
			return $this->quoteColumnName($strings);
		}

		$return = [];
		foreach ($strings as $string) {
			$return[] = $this->quoteColumnName($string);
		}

		return $return;
	}

	public function quoteColumnName(string|ILiteral|IParameter $string): string {
		if ($string instanceof ILiteral || $string instanceof IParameter) {
			return (string)$string;
		}

		if (substr_count($string, '.')) {
			[$alias, $columnName] = explode('.', $string);
			return '`' . $alias . '`.`' . $columnName . '`';
		}

		return '`' . $string . '`';
	}
}

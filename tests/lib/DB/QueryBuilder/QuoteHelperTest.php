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

class QuoteHelperTest extends \Test\TestCase {
	/** @var QuoteHelper */
	protected $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new QuoteHelper();
	}

	public function dataQuoteColumnName() {
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

	/**
	 * @dataProvider dataQuoteColumnName
	 * @param mixed $input
	 * @param string $expected
	 */
	public function testQuoteColumnName($input, $expected): void {
		$this->assertSame(
			$expected,
			$this->helper->quoteColumnName($input)
		);
	}

	public function dataQuoteColumnNames() {
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

	/**
	 * @dataProvider dataQuoteColumnNames
	 * @param mixed $input
	 * @param string $expected
	 */
	public function testQuoteColumnNames($input, $expected): void {
		$this->assertSame(
			$expected,
			$this->helper->quoteColumnNames($input)
		);
	}

	/**
	 * @param array|string|ILiteral|IParameter $strings string, Literal or Parameter
	 * @return array|string
	 */
	public function quoteColumnNames($strings) {
		if (!is_array($strings)) {
			return $this->quoteColumnName($strings);
		}

		$return = [];
		foreach ($strings as $string) {
			$return[] = $this->quoteColumnName($string);
		}

		return $return;
	}

	/**
	 * @param string|ILiteral|IParameter $string string, Literal or Parameter
	 * @return string
	 */
	public function quoteColumnName($string) {
		if ($string instanceof IParameter) {
			return $string->getName();
		}

		if ($string instanceof ILiteral) {
			return $string->getLiteral();
		}

		if ($string === null) {
			return $string;
		}

		if (!is_string($string)) {
			throw new \InvalidArgumentException('Only strings, Literals and Parameters are allowed');
		}

		if (substr_count($string, '.')) {
			[$alias, $columnName] = explode('.', $string);
			return '`' . $alias . '`.`' . $columnName . '`';
		}

		return '`' . $string . '`';
	}
}

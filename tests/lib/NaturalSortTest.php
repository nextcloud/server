<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\NaturalSort;
use OC\NaturalSort_DefaultCollator;

class NaturalSortTest extends \Test\TestCase {
	/**
	 * @dataProvider naturalSortDataProvider
	 */
	public function testNaturalSortCompare($array, $sorted): void {
		if (!class_exists('Collator')) {
			$this->markTestSkipped('The intl module is not available, natural sorting might not work as expected.');
			return;
		}
		$comparator = NaturalSort::getInstance();
		usort($array, [$comparator, 'compare']);
		$this->assertEquals($sorted, $array);
	}

	/**
	 * @dataProvider defaultCollatorDataProvider
	 */
	public function testDefaultCollatorCompare($array, $sorted): void {
		$comparator = new NaturalSort(new NaturalSort_DefaultCollator());
		usort($array, [$comparator, 'compare']);
		$this->assertEquals($sorted, $array);
	}

	/**
	 * Data provider for natural sorting with php5-intl's Collator.
	 * Must provide the same result as in core/js/tests/specs/coreSpec.js
	 * @return array test cases
	 */
	public static function naturalSortDataProvider(): array {
		return [
			// different casing
			[
				// unsorted
				[
					'aaa',
					'bbb',
					'BBB',
					'AAA'
				],
				// sorted
				[
					'aaa',
					'AAA',
					'bbb',
					'BBB'
				]
			],
			// numbers
			[
				// unsorted
				[
					'124.txt',
					'abc1',
					'123.txt',
					'abc',
					'abc2',
					'def (2).txt',
					'ghi 10.txt',
					'abc12',
					'def.txt',
					'def (1).txt',
					'ghi 2.txt',
					'def (10).txt',
					'abc10',
					'def (12).txt',
					'z',
					'ghi.txt',
					'za',
					'ghi 1.txt',
					'ghi 12.txt',
					'zz',
					'15.txt',
					'15b.txt',
				],
				// sorted
				[
					'15.txt',
					'15b.txt',
					'123.txt',
					'124.txt',
					'abc',
					'abc1',
					'abc2',
					'abc10',
					'abc12',
					'def.txt',
					'def (1).txt',
					'def (2).txt',
					'def (10).txt',
					'def (12).txt',
					'ghi.txt',
					'ghi 1.txt',
					'ghi 2.txt',
					'ghi 10.txt',
					'ghi 12.txt',
					'z',
					'za',
					'zz',
				]
			],
			// chinese characters
			[
				// unsorted
				[
					'十.txt',
					'一.txt',
					'二.txt',
					'十 2.txt',
					'三.txt',
					'四.txt',
					'abc.txt',
					'五.txt',
					'七.txt',
					'八.txt',
					'九.txt',
					'六.txt',
					'十一.txt',
					'波.txt',
					'破.txt',
					'莫.txt',
					'啊.txt',
					'123.txt',
				],
				// sorted
				[
					'123.txt',
					'abc.txt',
					'一.txt',
					'七.txt',
					'三.txt',
					'九.txt',
					'二.txt',
					'五.txt',
					'八.txt',
					'六.txt',
					'十.txt',
					'十 2.txt',
					'十一.txt',
					'啊.txt',
					'四.txt',
					'波.txt',
					'破.txt',
					'莫.txt',
				]
			],
			// with umlauts
			[
				// unsorted
				[
					'öh.txt',
					'Äh.txt',
					'oh.txt',
					'Üh 2.txt',
					'Üh.txt',
					'ah.txt',
					'Öh.txt',
					'uh.txt',
					'üh.txt',
					'äh.txt',
				],
				// sorted
				[
					'ah.txt',
					'äh.txt',
					'Äh.txt',
					'oh.txt',
					'öh.txt',
					'Öh.txt',
					'uh.txt',
					'üh.txt',
					'Üh.txt',
					'Üh 2.txt',
				]
			],
		];
	}

	/**
	 * Data provider for natural sorting with \OC\NaturalSort_DefaultCollator.
	 * Must provide the same result as in core/js/tests/specs/coreSpec.js
	 * @return array test cases
	 */
	public static function defaultCollatorDataProvider(): array {
		return [
			// different casing
			[
				// unsorted
				[
					'aaa',
					'bbb',
					'BBB',
					'AAA'
				],
				// sorted
				[
					'aaa',
					'AAA',
					'bbb',
					'BBB'
				]
			],
			// numbers
			[
				// unsorted
				[
					'124.txt',
					'abc1',
					'123.txt',
					'abc',
					'abc2',
					'def (2).txt',
					'ghi 10.txt',
					'abc12',
					'def.txt',
					'def (1).txt',
					'ghi 2.txt',
					'def (10).txt',
					'abc10',
					'def (12).txt',
					'z',
					'ghi.txt',
					'za',
					'ghi 1.txt',
					'ghi 12.txt',
					'zz',
					'15.txt',
					'15b.txt',
				],
				// sorted
				[
					'15.txt',
					'15b.txt',
					'123.txt',
					'124.txt',
					'abc',
					'abc1',
					'abc2',
					'abc10',
					'abc12',
					'def.txt',
					'def (1).txt',
					'def (2).txt',
					'def (10).txt',
					'def (12).txt',
					'ghi.txt',
					'ghi 1.txt',
					'ghi 2.txt',
					'ghi 10.txt',
					'ghi 12.txt',
					'z',
					'za',
					'zz',
				]
			],
		];
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App;

use InvalidArgumentException;
use OC\App\CompareVersion;
use Test\TestCase;

class CompareVersionTest extends TestCase {
	/** @var CompareVersion */
	private $compare;

	protected function setUp(): void {
		parent::setUp();

		$this->compare = new CompareVersion();
	}

	public function comparisonData() {
		return [
			// Compatible versions
			['13.0.0.3', '13.0.0', '>=', true],
			['13.0.0.3', '13.0.0', '<=', true],
			['13.0.0', '13.0.0', '>=', true],
			['13.0.0', '13.0', '<=', true],
			['13.0.0', '13', '>=', true],
			['13.0.1', '13', '>=', true],
			['13.0.1', '13', '<=', true],
			['13.0.1.9', '13', '<=', true],
			['13.0.1-beta.1', '13', '<=', true],
			['7.4.14', '7.4', '<=', true],
			['7.4.14-ubuntu', '7.4', '<=', true],
			['7.4.14-ubuntu', '7.4.15', '<=', true],
			['7.4.16-ubuntu', '7.4.15', '<=', false],
			// Incompatible major versions
			['13.0.0.3', '13.0.0', '<', false],
			['12.0.0', '13.0.0', '>=', false],
			['12.0.0', '13.0', '>=', false],
			['12.0.0', '13', '>=', false],
			['7.4.15-ubuntu', '7.4.15', '>=', true],
			// Incompatible minor and patch versions
			['13.0.0', '13.0.1', '>=', false],
			['13.0.0', '13.1', '>=', false],
			// Compatible minor and patch versions
			['13.0.1', '13.0.0', '>=', true],
			['13.2.0', '13.1', '>=', true],
		];
	}

	/**
	 * @dataProvider comparisonData
	 */
	public function testComparison(string $actualVersion, string $requiredVersion,
		string $comparator, bool $expected): void {
		$isCompatible = $this->compare->isCompatible($actualVersion, $requiredVersion,
			$comparator);

		$this->assertEquals($expected, $isCompatible);
	}

	public function testInvalidServerVersion(): void {
		$actualVersion = '13';
		$this->expectException(InvalidArgumentException::class);

		$this->compare->isCompatible($actualVersion, '13.0.0');
	}

	public function testInvalidRequiredVersion(): void {
		$actualVersion = '13.0.0';
		$this->expectException(InvalidArgumentException::class);

		$this->compare->isCompatible($actualVersion, '13.0.0.9');
	}
}

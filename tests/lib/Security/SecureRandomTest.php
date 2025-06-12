<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security;

use OC\Security\SecureRandom;

class SecureRandomTest extends \Test\TestCase {
	public static function stringGenerationProvider(): array {
		return [
			[1, 1],
			[128, 128],
			[256, 256],
			[1024, 1024],
			[2048, 2048],
			[64000, 64000],
		];
	}

	public static function charCombinations(): array {
		return [
			['CHAR_LOWER', '[a-z]'],
			['CHAR_UPPER', '[A-Z]'],
			['CHAR_DIGITS', '[0-9]'],
		];
	}

	/** @var SecureRandom */
	protected $rng;

	protected function setUp(): void {
		parent::setUp();
		$this->rng = new SecureRandom();
	}

	/**
	 * @dataProvider stringGenerationProvider
	 */
	public function testGetLowStrengthGeneratorLength($length, $expectedLength): void {
		$generator = $this->rng;

		$this->assertEquals($expectedLength, strlen($generator->generate($length)));
	}

	/**
	 * @dataProvider stringGenerationProvider
	 */
	public function testMediumLowStrengthGeneratorLength($length, $expectedLength): void {
		$generator = $this->rng;

		$this->assertEquals($expectedLength, strlen($generator->generate($length)));
	}

	/**
	 * @dataProvider stringGenerationProvider
	 */
	public function testUninitializedGenerate($length, $expectedLength): void {
		$this->assertEquals($expectedLength, strlen($this->rng->generate($length)));
	}

	/**
	 * @dataProvider charCombinations
	 */
	public function testScheme($charName, $chars): void {
		$generator = $this->rng;
		$scheme = constant('OCP\Security\ISecureRandom::' . $charName);
		$randomString = $generator->generate(100, $scheme);
		$matchesRegex = preg_match('/^' . $chars . '+$/', $randomString);
		$this->assertSame(1, $matchesRegex);
	}

	public static function invalidLengths(): array {
		return [
			[0],
			[-1],
		];
	}

	/**
	 * @dataProvider invalidLengths
	 */
	public function testInvalidLengths($length): void {
		$this->expectException(\LengthException::class);
		$generator = $this->rng;
		$generator->generate($length);
	}
}

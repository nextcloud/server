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
			[16, 16],
			[31, 31],
			[64, 64],
			[128, 128],
			[1024, 1024],
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

	#[\PHPUnit\Framework\Attributes\DataProvider('stringGenerationProvider')]
	public function testGetLowStrengthGeneratorLength($length, $expectedLength): void {
		$generator = $this->rng;

		$this->assertEquals($expectedLength, strlen($generator->generate($length)));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('stringGenerationProvider')]
	public function testMediumLowStrengthGeneratorLength($length, $expectedLength): void {
		$generator = $this->rng;

		$this->assertEquals($expectedLength, strlen($generator->generate($length)));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('stringGenerationProvider')]
	public function testUninitializedGenerate($length, $expectedLength): void {
		$this->assertEquals($expectedLength, strlen($this->rng->generate($length)));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('charCombinations')]
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

	#[\PHPUnit\Framework\Attributes\DataProvider('invalidLengths')]
	public function testInvalidLengths($length): void {
		$this->expectException(\LengthException::class);
		$generator = $this->rng;
		$generator->generate($length);
	}

	public static function invalidCharProviders(): array {
		return [
			'invalid_too_short' => ['abc'],
			'invalid_duplicates' => ['aabcd'],
			'invalid_non_ascii' => ["abcd\xf0"],
		];
	}

	/**
	 * @dataProvider invalidCharProviders
	 */
	public function testInvalidCharacterSets(string $invalidCharset): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->rng->generate(10, $invalidCharset);
	}

	public function testDefaultCharsetBase64Characters(): void {
		$randomString = $this->rng->generate(100);
		$this->assertMatchesRegularExpression('/^[A-Za-z0-9\+\/]+$/', $randomString);
	}

	public function testAllOutputsAreUnique(): void {
		// While collisions are technically possible, extremely unlikely for these sizes
		$first = $this->rng->generate(1000);
		$second = $this->rng->generate(1000);
		$this->assertNotEquals($first, $second, "Random output should not be repeated.");
	}

	public function testMinimumValidCharset(): void {
		$charset = 'abcd';
		$randomString = $this->rng->generate(500, $charset);
		$this->assertMatchesRegularExpression('/^[abcd]+$/', $randomString);
		$this->assertEquals(500, strlen($randomString));
	}

	public function testLargeCustomCharset(): void {
		$charset = '';
		for ($i = 32; $i <= 126; $i++) { // all printable ASCII
			$charset .= chr($i);
		}
		$randomString = $this->rng->generate(200, $charset);
		foreach (str_split($randomString) as $char) {
			$this->assertStringContainsString($char, $charset);
		}
	}

	public function testUserProvidedValidCharset(): void {
		$charset = '@#$!';
		$randomString = $this->rng->generate(64, $charset);
		$this->assertMatchesRegularExpression('/^[@#$!]+$/', $randomString);
	}
}

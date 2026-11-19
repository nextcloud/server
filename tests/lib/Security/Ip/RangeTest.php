<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Ip;

use InvalidArgumentException;
use OC\Security\Ip\Address;
use OC\Security\Ip\Range;
use OCP\Security\Ip\IRange;

class RangeTest extends \Test\TestCase {
	public function testImplementsInterface(): void {
		$range = new Range('192.168.1.0/24');
		$this->assertInstanceOf(IRange::class, $range);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('validRangeProvider')]
	public function testConstructorWithValidRange(string $range): void {
		$rangeObj = new Range($range);
		$this->assertNotEmpty((string)$rangeObj);
	}

	public static function validRangeProvider(): array {
		return [
			'IPv4 CIDR /24' => ['192.168.1.0/24'],
			'IPv4 CIDR /32' => ['10.0.0.1/32'],
			'IPv4 CIDR /0' => ['0.0.0.0/0'],
			'IPv4 CIDR /16' => ['172.16.0.0/16'],
			'IPv4 CIDR /8' => ['10.0.0.0/8'],
			'IPv4 single address' => ['192.168.1.1'],
			'IPv4 wildcard' => ['192.168.1.*'],
			'IPv6 CIDR /64' => ['2001:db8::/64'],
			'IPv6 CIDR /128' => ['::1/128'],
			'IPv6 CIDR /32' => ['2001:db8::/32'],
			'IPv6 single address' => ['::1'],
			'IPv6 full notation' => ['2001:0db8:85a3:0000:0000:8a2e:0370:7334'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('invalidRangeProvider')]
	public function testConstructorWithInvalidRange(string $range): void {
		$this->expectException(InvalidArgumentException::class);
		new Range($range);
	}

	public static function invalidRangeProvider(): array {
		return [
			'empty string' => [''],
			'random text' => ['not-a-range'],
			'invalid CIDR' => ['192.168.1.0/33'],
			'negative CIDR' => ['192.168.1.0/-1'],
			'IPv4 out of range' => ['256.256.256.256/24'],
			'malformed' => ['192.168/24'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('isValidProvider')]
	public function testIsValid(string $range, bool $expected): void {
		$this->assertSame($expected, Range::isValid($range));
	}

	public static function isValidProvider(): array {
		return [
			['192.168.1.0/24', true],
			['10.0.0.0/8', true],
			['::1/128', true],
			['2001:db8::/32', true],
			['192.168.1.*', true],
			['192.168.1.1', true],
			['', false],
			['not-a-range', false],
			['192.168.1.0/33', false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('containsProvider')]
	public function testContains(string $range, string $address, bool $expected): void {
		$rangeObj = new Range($range);
		$addressObj = new Address($address);
		$this->assertSame($expected, $rangeObj->contains($addressObj));
	}

	public static function containsProvider(): array {
		return [
			'IPv4 in /24' => ['192.168.1.0/24', '192.168.1.100', true],
			'IPv4 first in /24' => ['192.168.1.0/24', '192.168.1.0', true],
			'IPv4 last in /24' => ['192.168.1.0/24', '192.168.1.255', true],
			'IPv4 outside /24' => ['192.168.1.0/24', '192.168.2.1', false],
			'IPv4 in /32' => ['10.0.0.1/32', '10.0.0.1', true],
			'IPv4 outside /32' => ['10.0.0.1/32', '10.0.0.2', false],
			'IPv4 in /8' => ['10.0.0.0/8', '10.255.255.255', true],
			'IPv4 outside /8' => ['10.0.0.0/8', '11.0.0.1', false],
			'IPv4 wildcard match' => ['192.168.1.*', '192.168.1.50', true],
			'IPv4 wildcard no match' => ['192.168.1.*', '192.168.2.50', false],
			'IPv6 in /64' => ['2001:db8::/64', '2001:db8::ffff', true],
			'IPv6 outside /64' => ['2001:db8::/64', '2001:db9::1', false],
			'IPv6 in /128' => ['::1/128', '::1', true],
			'IPv6 outside /128' => ['::1/128', '::2', false],
			'IPv4 match all' => ['0.0.0.0/0', '192.168.1.1', true],
			'IPv4 loopback in range' => ['127.0.0.0/8', '127.0.0.1', true],
		];
	}

	public function testToString(): void {
		$range = new Range('192.168.1.0/24');
		$this->assertSame('192.168.1.0/24', (string)$range);
	}

	public function testToStringNormalizesIPv6(): void {
		$range = new Range('2001:0db8:0000:0000:0000:0000:0000:0000/32');
		$this->assertSame('2001:db8::/32', (string)$range);
	}

	public function testToStringSingleIPv4(): void {
		$range = new Range('192.168.1.1');
		$this->assertSame('192.168.1.1', (string)$range);
	}
}

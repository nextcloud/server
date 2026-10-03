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
use OCP\Security\Ip\IAddress;

class AddressTest extends \Test\TestCase {
	public function testImplementsInterface(): void {
		$address = new Address('127.0.0.1');
		$this->assertInstanceOf(IAddress::class, $address);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('validAddressProvider')]
	public function testConstructorWithValidAddress(string $ip): void {
		$address = new Address($ip);
		$this->assertNotEmpty((string)$address);
	}

	public static function validAddressProvider(): array {
		return [
			'IPv4 loopback' => ['127.0.0.1'],
			'IPv4 private' => ['192.168.1.1'],
			'IPv4 public' => ['8.8.8.8'],
			'IPv4 zero' => ['0.0.0.0'],
			'IPv4 broadcast' => ['255.255.255.255'],
			'IPv6 loopback' => ['::1'],
			'IPv6 full' => ['2001:0db8:85a3:0000:0000:8a2e:0370:7334'],
			'IPv6 compressed' => ['2001:db8::1'],
			'IPv6 link-local' => ['fe80::1'],
			'IPv6 with zone ID' => ['fe80::1%eth0'],
			'IPv6 mapped IPv4' => ['::ffff:192.168.1.1'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('invalidAddressProvider')]
	public function testConstructorWithInvalidAddress(string $ip): void {
		$this->expectException(InvalidArgumentException::class);
		new Address($ip);
	}

	public static function invalidAddressProvider(): array {
		return [
			'empty string' => [''],
			'random text' => ['not-an-ip'],
			'incomplete IPv4' => ['192.168.1'],
			'IPv4 out of range' => ['256.256.256.256'],
			'CIDR notation' => ['192.168.1.0/24'],
			'IPv4 with port' => ['192.168.1.1:8080'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('isValidProvider')]
	public function testIsValid(string $ip, bool $expected): void {
		$this->assertSame($expected, Address::isValid($ip));
	}

	public static function isValidProvider(): array {
		return [
			['127.0.0.1', true],
			['::1', true],
			['192.168.1.1', true],
			['2001:db8::1', true],
			['fe80::1%eth0', true],
			['', false],
			['not-an-ip', false],
			['256.1.2.3', false],
			['192.168.1.0/24', false],
		];
	}

	public function testToString(): void {
		$address = new Address('127.0.0.1');
		$this->assertSame('127.0.0.1', (string)$address);
	}

	public function testToStringIPv6Normalized(): void {
		$address = new Address('2001:0db8:0000:0000:0000:0000:0000:0001');
		$this->assertSame('2001:db8::1', (string)$address);
	}

	public function testMatchesReturnsTrueWhenInRange(): void {
		$address = new Address('192.168.1.100');
		$range = new Range('192.168.1.0/24');
		$this->assertTrue($address->matches($range));
	}

	public function testMatchesReturnsFalseWhenNotInRange(): void {
		$address = new Address('10.0.0.1');
		$range = new Range('192.168.1.0/24');
		$this->assertFalse($address->matches($range));
	}

	public function testMatchesWithMultipleRanges(): void {
		$address = new Address('10.0.0.5');
		$range1 = new Range('192.168.1.0/24');
		$range2 = new Range('10.0.0.0/8');
		$this->assertTrue($address->matches($range1, $range2));
	}

	public function testMatchesWithNoRanges(): void {
		$address = new Address('192.168.1.1');
		$this->assertFalse($address->matches());
	}

	public function testMatchesWithMultipleRangesNoneMatching(): void {
		$address = new Address('172.16.0.1');
		$range1 = new Range('192.168.1.0/24');
		$range2 = new Range('10.0.0.0/8');
		$this->assertFalse($address->matches($range1, $range2));
	}

	public function testMatchesIPv6InRange(): void {
		$address = new Address('2001:db8::1');
		$range = new Range('2001:db8::/32');
		$this->assertTrue($address->matches($range));
	}

	public function testMatchesIPv6NotInRange(): void {
		$address = new Address('2001:db9::1');
		$range = new Range('2001:db8::/32');
		$this->assertFalse($address->matches($range));
	}
}

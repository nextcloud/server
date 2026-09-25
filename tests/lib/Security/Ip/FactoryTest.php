<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Ip;

use InvalidArgumentException;
use OC\Security\Ip\Factory;
use OCP\Security\Ip\IAddress;
use OCP\Security\Ip\IFactory;
use OCP\Security\Ip\IRange;

class FactoryTest extends \Test\TestCase {
	private Factory $factory;

	protected function setUp(): void {
		parent::setUp();
		$this->factory = new Factory();
	}

	public function testImplementsInterface(): void {
		$this->assertInstanceOf(IFactory::class, $this->factory);
	}

	public function testRangeFromStringReturnsIRange(): void {
		$range = $this->factory->rangeFromString('192.168.1.0/24');
		$this->assertInstanceOf(IRange::class, $range);
	}

	public function testAddressFromStringReturnsIAddress(): void {
		$address = $this->factory->addressFromString('192.168.1.1');
		$this->assertInstanceOf(IAddress::class, $address);
	}

	public function testRangeFromStringWithIPv4(): void {
		$range = $this->factory->rangeFromString('10.0.0.0/8');
		$this->assertSame('10.0.0.0/8', (string)$range);
	}

	public function testRangeFromStringWithIPv6(): void {
		$range = $this->factory->rangeFromString('2001:db8::/32');
		$this->assertSame('2001:db8::/32', (string)$range);
	}

	public function testAddressFromStringWithIPv4(): void {
		$address = $this->factory->addressFromString('127.0.0.1');
		$this->assertSame('127.0.0.1', (string)$address);
	}

	public function testAddressFromStringWithIPv6(): void {
		$address = $this->factory->addressFromString('::1');
		$this->assertSame('::1', (string)$address);
	}

	public function testRangeFromStringWithInvalidRange(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->factory->rangeFromString('not-a-range');
	}

	public function testAddressFromStringWithInvalidAddress(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->factory->addressFromString('not-an-ip');
	}

	public function testCreatedRangeContainsCreatedAddress(): void {
		$range = $this->factory->rangeFromString('192.168.1.0/24');
		$address = $this->factory->addressFromString('192.168.1.50');
		$this->assertTrue($range->contains($address));
	}

	public function testCreatedRangeDoesNotContainOutsideAddress(): void {
		$range = $this->factory->rangeFromString('192.168.1.0/24');
		$address = $this->factory->addressFromString('10.0.0.1');
		$this->assertFalse($range->contains($address));
	}

	public function testCreatedAddressMatchesCreatedRange(): void {
		$range = $this->factory->rangeFromString('10.0.0.0/8');
		$address = $this->factory->addressFromString('10.5.3.2');
		$this->assertTrue($address->matches($range));
	}

	public function testRangeFromStringWithWildcard(): void {
		$range = $this->factory->rangeFromString('192.168.1.*');
		$address = $this->factory->addressFromString('192.168.1.123');
		$this->assertTrue($range->contains($address));
	}
}

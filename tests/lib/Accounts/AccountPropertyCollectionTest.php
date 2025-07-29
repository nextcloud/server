<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Accounts;

use InvalidArgumentException;
use OC\Accounts\AccountPropertyCollection;
use OCP\Accounts\IAccountProperty;
use OCP\Accounts\IAccountPropertyCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AccountPropertyCollectionTest extends TestCase {
	/** @var IAccountPropertyCollection */
	protected $collection;

	protected const COLLECTION_NAME = 'my_multivalue_property';

	public function setUp(): void {
		parent::setUp();

		$this->collection = new AccountPropertyCollection(self::COLLECTION_NAME);
	}

	/**
	 * @return IAccountProperty|MockObject
	 */
	protected function makePropertyMock(string $propertyName): MockObject {
		$mock = $this->createMock(IAccountProperty::class);
		$mock->expects($this->any())
			->method('getName')
			->willReturn($propertyName);

		return $mock;
	}

	public function testSetAndGetProperties(): void {
		$propsBefore = $this->collection->getProperties();
		$this->assertIsArray($propsBefore);
		$this->assertEmpty($propsBefore);

		$props = [
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
		];

		$this->collection->setProperties($props);
		$propsAfter = $this->collection->getProperties();
		$this->assertIsArray($propsAfter);
		$this->assertCount(count($props), $propsAfter);
	}

	public function testSetPropertiesMixedInvalid(): void {
		$props = [
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock('sneaky_property'),
			$this->makePropertyMock(self::COLLECTION_NAME),
		];

		$this->expectException(InvalidArgumentException::class);
		$this->collection->setProperties($props);
	}

	public function testAddProperty(): void {
		$props = [
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
		];
		$this->collection->setProperties($props);

		$additionalProperty = $this->makePropertyMock(self::COLLECTION_NAME);
		$this->collection->addProperty($additionalProperty);

		$propsAfter = $this->collection->getProperties();
		$this->assertCount(count($props) + 1, $propsAfter);
		$this->assertNotFalse(array_search($additionalProperty, $propsAfter, true));
	}

	public function testAddPropertyInvalid(): void {
		$props = [
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
		];
		$this->collection->setProperties($props);

		$additionalProperty = $this->makePropertyMock('sneaky_property');
		$exceptionThrown = false;
		try {
			$this->collection->addProperty($additionalProperty);
		} catch (\InvalidArgumentException $e) {
			$exceptionThrown = true;
		} finally {
			$propsAfter = $this->collection->getProperties();
			$this->assertCount(count($props), $propsAfter);
			$this->assertFalse(array_search($additionalProperty, $propsAfter, true));
			$this->assertTrue($exceptionThrown);
		}
	}

	public function testRemoveProperty(): void {
		$additionalProperty = $this->makePropertyMock(self::COLLECTION_NAME);
		$props = [
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
			$additionalProperty,
			$this->makePropertyMock(self::COLLECTION_NAME),
		];
		$this->collection->setProperties($props);

		$propsBefore = $this->collection->getProperties();
		$this->collection->removeProperty($additionalProperty);
		$propsAfter = $this->collection->getProperties();

		$this->assertTrue(count($propsBefore) > count($propsAfter));
		$this->assertCount(count($propsBefore) - 1, $propsAfter);
		$this->assertFalse(array_search($additionalProperty, $propsAfter, true));
	}

	public function testRemovePropertyNotFound(): void {
		$additionalProperty = $this->makePropertyMock(self::COLLECTION_NAME);
		$props = [
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
		];
		$this->collection->setProperties($props);

		$propsBefore = $this->collection->getProperties();
		$this->collection->removeProperty($additionalProperty);
		$propsAfter = $this->collection->getProperties();

		// no errors, gently
		$this->assertCount(count($propsBefore), $propsAfter);
	}

	public function testRemovePropertyByValue(): void {
		$additionalProperty = $this->makePropertyMock(self::COLLECTION_NAME);
		$additionalProperty->expects($this->any())
			->method('getValue')
			->willReturn('Lorem ipsum');

		$additionalPropertyTwo = clone $additionalProperty;

		$props = [
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
			$additionalProperty,
			$this->makePropertyMock(self::COLLECTION_NAME),
			$additionalPropertyTwo
		];
		$this->collection->setProperties($props);

		$propsBefore = $this->collection->getProperties();
		$this->collection->removePropertyByValue('Lorem ipsum');
		$propsAfter = $this->collection->getProperties();

		$this->assertTrue(count($propsBefore) > count($propsAfter));
		$this->assertCount(count($propsBefore) - 2, $propsAfter);
		$this->assertFalse(array_search($additionalProperty, $propsAfter, true));
		$this->assertFalse(array_search($additionalPropertyTwo, $propsAfter, true));
	}

	public function testRemovePropertyByValueNotFound(): void {
		$additionalProperty = $this->makePropertyMock(self::COLLECTION_NAME);
		$additionalProperty->expects($this->any())
			->method('getValue')
			->willReturn('Lorem ipsum');

		$props = [
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock(self::COLLECTION_NAME),
		];
		$this->collection->setProperties($props);

		$propsBefore = $this->collection->getProperties();
		$this->collection->removePropertyByValue('Lorem ipsum');
		$propsAfter = $this->collection->getProperties();

		// no errors, gently
		$this->assertCount(count($propsBefore), $propsAfter);
	}
}

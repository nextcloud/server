<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
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

	public function testSetAndGetProperties() {
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

	public function testSetPropertiesMixedInvalid() {
		$props = [
			$this->makePropertyMock(self::COLLECTION_NAME),
			$this->makePropertyMock('sneaky_property'),
			$this->makePropertyMock(self::COLLECTION_NAME),
		];

		$this->expectException(InvalidArgumentException::class);
		$this->collection->setProperties($props);
	}

	public function testAddProperty() {
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

	public function testAddPropertyInvalid() {
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

	public function testRemoveProperty() {
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

	public function testRemovePropertyNotFound() {
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

	public function testRemovePropertyByValue() {
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

	public function testRemovePropertyByValueNotFound() {
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

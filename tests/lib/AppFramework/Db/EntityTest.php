<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Db;

use OCP\AppFramework\Db\Entity;
use PHPUnit\Framework\Constraint\IsType;

/**
 * @method integer getId()
 * @method void setId(integer $id)
 * @method integer getTestId()
 * @method void setTestId(integer $id)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getEmail()
 * @method void setEmail(string $email)
 * @method string getPreName()
 * @method void setPreName(string $preName)
 * @method bool getTrueOrFalse()
 * @method bool isTrueOrFalse()
 * @method void setTrueOrFalse(bool $trueOrFalse)
 * @method bool getAnotherBool()
 * @method bool isAnotherBool()
 * @method string getLongText()
 * @method void setLongText(string $longText)
 */
class TestEntity extends Entity {
	protected $name;
	protected $email;
	protected $testId;
	protected $preName;
	protected $trueOrFalse;
	protected $anotherBool;
	protected $longText;

	public function __construct($name = null) {
		$this->addType('testId', 'integer');
		$this->addType('trueOrFalse', 'bool');
		$this->addType('anotherBool', 'boolean');
		$this->addType('longText', 'blob');
		$this->name = $name;
	}

	public function setAnotherBool(bool $anotherBool): void {
		parent::setAnotherBool($anotherBool);
	}
}


class EntityTest extends \Test\TestCase {
	private $entity;

	protected function setUp(): void {
		parent::setUp();
		$this->entity = new TestEntity();
	}


	public function testResetUpdatedFields() {
		$entity = new TestEntity();
		$entity->setId(3);
		$entity->resetUpdatedFields();

		$this->assertEquals([], $entity->getUpdatedFields());
	}


	public function testFromRow() {
		$row = [
			'pre_name' => 'john',
			'email' => 'john@something.com',
			'another_bool' => 1,
		];
		$this->entity = TestEntity::fromRow($row);

		$this->assertEquals($row['pre_name'], $this->entity->getPreName());
		$this->assertEquals($row['email'], $this->entity->getEmail());
		$this->assertEquals($row['another_bool'], $this->entity->getAnotherBool());
	}


	public function testGetSetId() {
		$id = 3;
		$this->entity->setId(3);

		$this->assertEquals($id, $this->entity->getId());
	}


	public function testColumnToPropertyNoReplacement() {
		$column = 'my';
		$this->assertEquals('my',
			$this->entity->columnToProperty($column));
	}


	public function testColumnToProperty() {
		$column = 'my_attribute';
		$this->assertEquals('myAttribute',
			$this->entity->columnToProperty($column));
	}


	public function testPropertyToColumnNoReplacement() {
		$property = 'my';
		$this->assertEquals('my',
			$this->entity->propertyToColumn($property));
	}


	public function testSetterMarksFieldUpdated() {
		$this->entity->setId(3);

		$this->assertContains('id', array_keys($this->entity->getUpdatedFields()));
	}



	public function testCallShouldOnlyWorkForGetterSetter() {
		$this->expectException(\BadFunctionCallException::class);

		$this->entity->something();
	}



	public function testGetterShouldFailIfAttributeNotDefined() {
		$this->expectException(\BadFunctionCallException::class);

		$this->entity->getTest();
	}


	public function testSetterShouldFailIfAttributeNotDefined() {
		$this->expectException(\BadFunctionCallException::class);

		$this->entity->setTest();
	}


	public function testFromRowShouldNotAssignEmptyArray() {
		$row = [];
		$entity2 = new TestEntity();

		$this->entity = TestEntity::fromRow($row);
		$this->assertEquals($entity2, $this->entity);
	}


	public function testIdGetsConvertedToInt() {
		$row = ['id' => '4'];

		$this->entity = TestEntity::fromRow($row);
		$this->assertSame(4, $this->entity->getId());
	}


	public function testSetType() {
		$row = ['testId' => '4'];

		$this->entity = TestEntity::fromRow($row);
		$this->assertSame(4, $this->entity->getTestId());
	}


	public function testFromParams() {
		$params = [
			'testId' => 4,
			'email' => 'john@doe'
		];

		$entity = TestEntity::fromParams($params);

		$this->assertEquals($params['testId'], $entity->getTestId());
		$this->assertEquals($params['email'], $entity->getEmail());
		$this->assertTrue($entity instanceof TestEntity);
	}

	public function testSlugify() {
		$entity = new TestEntity();
		$entity->setName('Slugify this!');
		$this->assertEquals('slugify-this', $entity->slugify('name'));
		$entity->setName('°!"§$%&/()=?`´ß\}][{³²#\'+~*-_.:,;<>|äöüÄÖÜSlugify this!');
		$this->assertEquals('slugify-this', $entity->slugify('name'));
	}


	public function testSetterCasts() {
		$entity = new TestEntity();
		$entity->setId('3');
		$this->assertSame(3, $entity->getId());
	}


	public function testSetterDoesNotCastOnNull() {
		$entity = new TestEntity();
		$entity->setId(null);
		$this->assertSame(null, $entity->getId());
	}

	public function testSetterConvertsResourcesToStringProperly() {
		$string = 'Definitely a string';
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $string);
		rewind($stream);

		$entity = new TestEntity();
		$entity->setLongText($stream);
		fclose($stream);
		$this->assertSame($string, $entity->getLongText());
	}


	public function testGetFieldTypes() {
		$entity = new TestEntity();
		$this->assertEquals([
			'id' => 'integer',
			'testId' => 'integer',
			'trueOrFalse' => 'bool',
			'anotherBool' => 'boolean',
			'longText' => 'blob',
		], $entity->getFieldTypes());
	}


	public function testGetItInt() {
		$entity = new TestEntity();
		$entity->setId(3);
		$this->assertEquals('integer', gettype($entity->getId()));
	}


	public function testFieldsNotMarkedUpdatedIfNothingChanges() {
		$entity = new TestEntity('hey');
		$entity->setName('hey');
		$this->assertEquals(0, count($entity->getUpdatedFields()));
	}

	public function testIsGetter() {
		$entity = new TestEntity();
		$entity->setTrueOrFalse(false);
		$entity->setAnotherBool(false);
		$this->assertThat($entity->isTrueOrFalse(), new IsType(IsType::TYPE_BOOL));
		$this->assertThat($entity->isAnotherBool(), new IsType(IsType::TYPE_BOOL));
	}


	public function testIsGetterShoudFailForOtherType() {
		$this->expectException(\BadFunctionCallException::class);

		$entity = new TestEntity();
		$entity->setName('hello');
		$this->assertThat($entity->isName(), new IsType(IsType::TYPE_BOOL));
	}
}

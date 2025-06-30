<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;
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
 * @method \DateTime getTime()
 * @method void setTime(\DateTime $time)
 * @method \DateTimeImmutable getDatetime()
 * @method void setDatetime(\DateTimeImmutable $datetime)
 */
class TestEntity extends Entity {
	protected $email;
	protected $testId;
	protected $smallInt;
	protected $bigInt;
	protected $preName;
	protected $trueOrFalse;
	protected $anotherBool;
	protected $text;
	protected $longText;
	protected $time;
	protected $datetime;

	public function __construct(
		protected $name = null,
	) {
		$this->addType('testId', Types::INTEGER);
		$this->addType('smallInt', Types::SMALLINT);
		$this->addType('bigInt', Types::BIGINT);
		$this->addType('anotherBool', Types::BOOLEAN);
		$this->addType('text', Types::TEXT);
		$this->addType('longText', Types::BLOB);
		$this->addType('time', Types::TIME);
		$this->addType('datetime', Types::DATETIME_IMMUTABLE);

		// Legacy types
		$this->addType('trueOrFalse', 'bool');
		$this->addType('legacyInt', 'int');
		$this->addType('doubleNowFloat', 'double');
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


	public function testResetUpdatedFields(): void {
		$entity = new TestEntity();
		$entity->setId(3);
		$entity->resetUpdatedFields();

		$this->assertEquals([], $entity->getUpdatedFields());
	}


	public function testFromRow(): void {
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


	public function testGetSetId(): void {
		$id = 3;
		$this->entity->setId(3);

		$this->assertEquals($id, $this->entity->getId());
	}


	public function testColumnToPropertyNoReplacement(): void {
		$column = 'my';
		$this->assertEquals('my',
			$this->entity->columnToProperty($column));
	}


	public function testColumnToProperty(): void {
		$column = 'my_attribute';
		$this->assertEquals('myAttribute',
			$this->entity->columnToProperty($column));
	}


	public function testPropertyToColumnNoReplacement(): void {
		$property = 'my';
		$this->assertEquals('my',
			$this->entity->propertyToColumn($property));
	}


	public function testSetterMarksFieldUpdated(): void {
		$this->entity->setId(3);

		$this->assertContains('id', array_keys($this->entity->getUpdatedFields()));
	}



	public function testCallShouldOnlyWorkForGetterSetter(): void {
		$this->expectException(\BadFunctionCallException::class);

		$this->entity->something();
	}



	public function testGetterShouldFailIfAttributeNotDefined(): void {
		$this->expectException(\BadFunctionCallException::class);

		$this->entity->getTest();
	}


	public function testSetterShouldFailIfAttributeNotDefined(): void {
		$this->expectException(\BadFunctionCallException::class);

		$this->entity->setTest();
	}


	public function testFromRowShouldNotAssignEmptyArray(): void {
		$row = [];
		$entity2 = new TestEntity();

		$this->entity = TestEntity::fromRow($row);
		$this->assertEquals($entity2, $this->entity);
	}


	public function testIdGetsConvertedToInt(): void {
		$row = ['id' => '4'];

		$this->entity = TestEntity::fromRow($row);
		$this->assertSame(4, $this->entity->getId());
	}


	public function testSetType(): void {
		$row = ['testId' => '4'];

		$this->entity = TestEntity::fromRow($row);
		$this->assertSame(4, $this->entity->getTestId());
	}


	public function testFromParams(): void {
		$params = [
			'testId' => 4,
			'email' => 'john@doe'
		];

		$entity = TestEntity::fromParams($params);

		$this->assertEquals($params['testId'], $entity->getTestId());
		$this->assertEquals($params['email'], $entity->getEmail());
		$this->assertTrue($entity instanceof TestEntity);
	}

	public function testSlugify(): void {
		$entity = new TestEntity();
		$entity->setName('Slugify this!');
		$this->assertEquals('slugify-this', $entity->slugify('name'));
		$entity->setName('°!"§$%&/()=?`´ß\}][{³²#\'+~*-_.:,;<>|äöüÄÖÜSlugify this!');
		$this->assertEquals('slugify-this', $entity->slugify('name'));
	}


	public static function dataSetterCasts(): array {
		return [
			['Id', '3', 3],
			['smallInt', '3', 3],
			['bigInt', '' . PHP_INT_MAX, PHP_INT_MAX],
			['trueOrFalse', 0, false],
			['trueOrFalse', 1, true],
			['anotherBool', 0, false],
			['anotherBool', 1, true],
			['text', 33, '33'],
			['longText', PHP_INT_MAX, '' . PHP_INT_MAX],
		];
	}


	/**
	 * @dataProvider dataSetterCasts
	 */
	public function testSetterCasts(string $field, mixed $in, mixed $out): void {
		$entity = new TestEntity();
		$entity->{'set' . $field}($in);
		$this->assertSame($out, $entity->{'get' . $field}());
	}


	public function testSetterDoesNotCastOnNull(): void {
		$entity = new TestEntity();
		$entity->setId(null);
		$this->assertSame(null, $entity->getId());
	}

	public function testSetterConvertsResourcesToStringProperly(): void {
		$string = 'Definitely a string';
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $string);
		rewind($stream);

		$entity = new TestEntity();
		$entity->setLongText($stream);
		fclose($stream);
		$this->assertSame($string, $entity->getLongText());
	}

	public function testSetterConvertsDatetime() {
		$entity = new TestEntity();
		$entity->setDatetime('2024-08-19 15:26:00');
		$this->assertEquals(new \DateTimeImmutable('2024-08-19 15:26:00'), $entity->getDatetime());
	}

	public function testSetterDoesNotConvertNullOnDatetime() {
		$entity = new TestEntity();
		$entity->setDatetime(null);
		$this->assertNull($entity->getDatetime());
	}

	public function testSetterConvertsTime() {
		$entity = new TestEntity();
		$entity->setTime('15:26:00');
		$this->assertEquals(new \DateTime('15:26:00'), $entity->getTime());
	}

	public function testGetFieldTypes(): void {
		$entity = new TestEntity();
		$this->assertEquals([
			'id' => Types::INTEGER,
			'testId' => Types::INTEGER,
			'smallInt' => Types::SMALLINT,
			'bigInt' => Types::BIGINT,
			'anotherBool' => Types::BOOLEAN,
			'text' => Types::TEXT,
			'longText' => Types::BLOB,
			'time' => Types::TIME,
			'datetime' => Types::DATETIME_IMMUTABLE,
			'trueOrFalse' => Types::BOOLEAN,
			'legacyInt' => Types::INTEGER,
			'doubleNowFloat' => Types::FLOAT,
		], $entity->getFieldTypes());
	}


	public function testGetItInt(): void {
		$entity = new TestEntity();
		$entity->setId(3);
		$this->assertEquals(Types::INTEGER, gettype($entity->getId()));
	}


	public function testFieldsNotMarkedUpdatedIfNothingChanges(): void {
		$entity = new TestEntity('hey');
		$entity->setName('hey');
		$this->assertEquals(0, count($entity->getUpdatedFields()));
	}

	public function testIsGetter(): void {
		$entity = new TestEntity();
		$entity->setTrueOrFalse(false);
		$entity->setAnotherBool(false);
		$this->assertThat($entity->isTrueOrFalse(), new IsType(IsType::TYPE_BOOL));
		$this->assertThat($entity->isAnotherBool(), new IsType(IsType::TYPE_BOOL));
	}


	public function testIsGetterShoudFailForOtherType(): void {
		$this->expectException(\BadFunctionCallException::class);

		$entity = new TestEntity();
		$entity->setName('hello');
		$this->assertThat($entity->isName(), new IsType(IsType::TYPE_BOOL));
	}
}

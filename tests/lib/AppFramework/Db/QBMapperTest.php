<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Db;

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @method bool getBoolProp()
 * @method void setBoolProp(bool $boolProp)
 * @method integer getIntProp()
 * @method void setIntProp(integer $intProp)
 * @method string getStringProp()
 * @method void setStringProp(string $stringProp)
 * @method bool getBooleanProp()
 * @method void setBooleanProp(bool $booleanProp)
 * @method integer getIntegerProp()
 * @method void setIntegerProp(integer $integerProp)
 * @method ?\DateTimeImmutable getDatetimeProp()
 * @method void setDatetimeProp(?\DateTimeImmutable $datetime)
 */
class QBTestEntity extends Entity {
	protected $intProp;
	protected $boolProp;
	protected $stringProp;
	protected $integerProp;
	protected $booleanProp;
	protected $jsonProp;
	protected $datetimeProp;

	public function __construct() {
		$this->addType('intProp', 'int');
		$this->addType('boolProp', 'bool');
		$this->addType('stringProp', Types::STRING);
		$this->addType('integerProp', Types::INTEGER);
		$this->addType('booleanProp', Types::BOOLEAN);
		$this->addType('jsonProp', Types::JSON);
		$this->addType('datetimeProp', Types::DATETIME_IMMUTABLE);
	}
}

/**
 * Class QBTestMapper
 *
 * @package Test\AppFramework\Db
 */
class QBTestMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'table');
	}

	public function getParameterTypeForPropertyForTest(Entity $entity, string $property) {
		return parent::getParameterTypeForProperty($entity, $property);
	}
}

/**
 * Class QBMapperTest
 *
 * @package Test\AppFramework\Db
 */
class QBMapperTest extends \Test\TestCase {

	protected IDBConnection&MockObject $db;
	protected IQueryBuilder&MockObject $qb;
	protected IExpressionBuilder&MockObject $expr;
	protected QBTestMapper $mapper;

	/**
	 * @throws \ReflectionException
	 */
	protected function setUp(): void {
		$this->db = $this->getMockBuilder(IDBConnection::class)
			->disableOriginalConstructor()
			->getMock();

		$this->qb = $this->getMockBuilder(IQueryBuilder::class)
			->disableOriginalConstructor()
			->getMock();

		$this->expr = $this->getMockBuilder(IExpressionBuilder::class)
			->disableOriginalConstructor()
			->getMock();


		$this->qb->method('expr')->willReturn($this->expr);
		$this->db->method('getQueryBuilder')->willReturn($this->qb);


		$this->mapper = new QBTestMapper($this->db);
	}


	public function testInsertEntityParameterTypeMapping(): void {
		$datetime = new \DateTimeImmutable();
		$entity = new QBTestEntity();
		$entity->setIntProp(123);
		$entity->setBoolProp(true);
		$entity->setStringProp('string');
		$entity->setIntegerProp(456);
		$entity->setBooleanProp(false);
		$entity->setDatetimeProp($datetime);

		$intParam = $this->qb->createNamedParameter('int_prop', IQueryBuilder::PARAM_INT);
		$boolParam = $this->qb->createNamedParameter('bool_prop', IQueryBuilder::PARAM_BOOL);
		$stringParam = $this->qb->createNamedParameter('string_prop', IQueryBuilder::PARAM_STR);
		$integerParam = $this->qb->createNamedParameter('integer_prop', IQueryBuilder::PARAM_INT);
		$booleanParam = $this->qb->createNamedParameter('boolean_prop', IQueryBuilder::PARAM_BOOL);
		$datetimeParam = $this->qb->createNamedParameter('datetime_prop', IQueryBuilder::PARAM_DATETIME_IMMUTABLE);

		$createNamedParameterCalls = [
			[123, IQueryBuilder::PARAM_INT, null],
			[true, IQueryBuilder::PARAM_BOOL, null],
			['string', IQueryBuilder::PARAM_STR, null],
			[456, IQueryBuilder::PARAM_INT, null],
			[false, IQueryBuilder::PARAM_BOOL, null],
			[$datetime, IQueryBuilder::PARAM_DATETIME_IMMUTABLE, null],
		];
		$this->qb->expects($this->exactly(6))
			->method('createNamedParameter')
			->willReturnCallback(function () use (&$createNamedParameterCalls): void {
				$expected = array_shift($createNamedParameterCalls);
				$this->assertEquals($expected, func_get_args());
			});

		$setValueCalls = [
			['int_prop', $intParam],
			['bool_prop', $boolParam],
			['string_prop', $stringParam],
			['integer_prop', $integerParam],
			['boolean_prop', $booleanParam],
			['datetime_prop', $datetimeParam],
		];
		$this->qb->expects($this->exactly(6))
			->method('setValue')
			->willReturnCallback(function () use (&$setValueCalls): void {
				$expected = array_shift($setValueCalls);
				$this->assertEquals($expected, func_get_args());
			});

		$this->mapper->insert($entity);
	}


	public function testUpdateEntityParameterTypeMapping(): void {
		$datetime = new \DateTimeImmutable();
		$entity = new QBTestEntity();
		$entity->setId(789);
		$entity->setIntProp(123);
		$entity->setBoolProp('true');
		$entity->setStringProp('string');
		$entity->setIntegerProp(456);
		$entity->setBooleanProp(false);
		$entity->setJsonProp(['hello' => 'world']);
		$entity->setDatetimeProp($datetime);

		$idParam = $this->qb->createNamedParameter('id', IQueryBuilder::PARAM_INT);
		$intParam = $this->qb->createNamedParameter('int_prop', IQueryBuilder::PARAM_INT);
		$boolParam = $this->qb->createNamedParameter('bool_prop', IQueryBuilder::PARAM_BOOL);
		$stringParam = $this->qb->createNamedParameter('string_prop', IQueryBuilder::PARAM_STR);
		$integerParam = $this->qb->createNamedParameter('integer_prop', IQueryBuilder::PARAM_INT);
		$booleanParam = $this->qb->createNamedParameter('boolean_prop', IQueryBuilder::PARAM_BOOL);
		$jsonParam = $this->qb->createNamedParameter('json_prop', IQueryBuilder::PARAM_JSON);
		$datetimeParam = $this->qb->createNamedParameter('datetime_prop', IQueryBuilder::PARAM_DATETIME_IMMUTABLE);

		$createNamedParameterCalls = [
			[123, IQueryBuilder::PARAM_INT, null],
			[true, IQueryBuilder::PARAM_BOOL, null],
			['string', IQueryBuilder::PARAM_STR, null],
			[456, IQueryBuilder::PARAM_INT, null],
			[false, IQueryBuilder::PARAM_BOOL, null],
			[['hello' => 'world'], IQueryBuilder::PARAM_JSON, null],
			[$datetime, IQueryBuilder::PARAM_DATETIME_IMMUTABLE, null],
			[789, IQueryBuilder::PARAM_INT, null],
		];
		$this->qb->expects($this->exactly(8))
			->method('createNamedParameter')
			->willReturnCallback(function () use (&$createNamedParameterCalls): void {
				$expected = array_shift($createNamedParameterCalls);
				$this->assertEquals($expected, func_get_args());
			});

		$setCalls = [
			['int_prop', $intParam],
			['bool_prop', $boolParam],
			['string_prop', $stringParam],
			['integer_prop', $integerParam],
			['boolean_prop', $booleanParam],
			['json_prop', $datetimeParam],
			['datetime_prop', $datetimeParam],
		];
		$this->qb->expects($this->exactly(7))
			->method('set')
			->willReturnCallback(function () use (&$setCalls): void {
				$expected = array_shift($setCalls);
				$this->assertEquals($expected, func_get_args());
			});

		$this->expr->expects($this->once())
			->method('eq')
			->with($this->equalTo('id'), $this->equalTo($idParam));


		$this->mapper->update($entity);
	}


	public function testGetParameterTypeForProperty(): void {
		$entity = new QBTestEntity();

		$intType = $this->mapper->getParameterTypeForPropertyForTest($entity, 'intProp');
		$this->assertEquals(IQueryBuilder::PARAM_INT, $intType, 'Int type property mapping incorrect');

		$integerType = $this->mapper->getParameterTypeForPropertyForTest($entity, 'integerProp');
		$this->assertEquals(IQueryBuilder::PARAM_INT, $integerType, 'Integer type property mapping incorrect');

		$boolType = $this->mapper->getParameterTypeForPropertyForTest($entity, 'boolProp');
		$this->assertEquals(IQueryBuilder::PARAM_BOOL, $boolType, 'Bool type property mapping incorrect');

		$booleanType = $this->mapper->getParameterTypeForPropertyForTest($entity, 'booleanProp');
		$this->assertEquals(IQueryBuilder::PARAM_BOOL, $booleanType, 'Boolean type property mapping incorrect');

		$stringType = $this->mapper->getParameterTypeForPropertyForTest($entity, 'stringProp');
		$this->assertEquals(IQueryBuilder::PARAM_STR, $stringType, 'String type property mapping incorrect');

		$jsonType = $this->mapper->getParameterTypeForPropertyForTest($entity, 'jsonProp');
		$this->assertEquals(IQueryBuilder::PARAM_JSON, $jsonType, 'JSON type property mapping incorrect');

		$datetimeType = $this->mapper->getParameterTypeForPropertyForTest($entity, 'datetimeProp');
		$this->assertEquals(IQueryBuilder::PARAM_DATETIME_IMMUTABLE, $datetimeType, 'DateTimeImmutable type property mapping incorrect');

		$unknownType = $this->mapper->getParameterTypeForPropertyForTest($entity, 'someProp');
		$this->assertEquals(IQueryBuilder::PARAM_STR, $unknownType, 'Unknown type property mapping incorrect');
	}
}

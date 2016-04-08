<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt dev@bernhard-posselt.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace Test\AppFramework\Db;

use \OCP\IDBConnection;
use \OCP\AppFramework\Db\Entity;
use \OCP\AppFramework\Db\Mapper;

/**
 * @method integer getId()
 * @method void setId(integer $id)
 * @method string getEmail()
 * @method void setEmail(string $email)
 * @method string getPreName()
 * @method void setPreName(string $preName)
 */
class Example extends Entity {
	protected $preName;
	protected $email;
};


class ExampleMapper extends Mapper {
	public function __construct(IDBConnection $db){ parent::__construct($db, 'table'); }
	public function find($table, $id){ return $this->findOneQuery($table, $id); }
	public function findOneEntity($table, $id){ return $this->findEntity($table, $id); }
	public function findAllEntities($table){ return $this->findEntities($table); }
	public function mapRow($row){ return $this->mapRowToEntity($row); }
	public function execSql($sql, $params){ return $this->execute($sql, $params); }
}


class MapperTest extends MapperTestUtility {

	/**
	 * @var Mapper
	 */
	private $mapper;

	protected function setUp(){
		parent::setUp();
		$this->mapper = new ExampleMapper($this->db);
	}


	public function testMapperShouldSetTableName(){
		$this->assertEquals('*PREFIX*table', $this->mapper->getTableName());
	}


	public function testFindQuery(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array(
			array('hi')
		);
		$this->setMapperResult($sql, $params, $rows);
		$this->mapper->find($sql, $params);
	}

	public function testFindEntity(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array(
			array('pre_name' => 'hi')
		);
		$this->setMapperResult($sql, $params, $rows, null, null, true);
		$this->mapper->findOneEntity($sql, $params);
	}

	public function testFindNotFound(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array();
		$this->setMapperResult($sql, $params, $rows);
		$this->setExpectedException(
			'\OCP\AppFramework\Db\DoesNotExistException');
		$this->mapper->find($sql, $params);
	}

	public function testFindEntityNotFound(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array();
		$this->setMapperResult($sql, $params, $rows, null, null, true);
		$this->setExpectedException(
			'\OCP\AppFramework\Db\DoesNotExistException');
		$this->mapper->findOneEntity($sql, $params);
	}

	public function testFindMultiple(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array(
			array('jo'), array('ho')
		);
		$this->setMapperResult($sql, $params, $rows, null, null, true);
		$this->setExpectedException(
			'\OCP\AppFramework\Db\MultipleObjectsReturnedException');
		$this->mapper->find($sql, $params);
	}

	public function testFindEntityMultiple(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array(
			array('jo'), array('ho')
		);
		$this->setMapperResult($sql, $params, $rows, null, null, true);
		$this->setExpectedException(
			'\OCP\AppFramework\Db\MultipleObjectsReturnedException');
		$this->mapper->findOneEntity($sql, $params);
	}


	public function testDelete(){
		$sql = 'DELETE FROM `*PREFIX*table` WHERE `id` = ?';
		$params = array(2);

		$this->setMapperResult($sql, $params, [], null, null, true);
		$entity = new Example();
		$entity->setId($params[0]);

		$this->mapper->delete($entity);
	}


	public function testCreate(){
		$this->db->expects($this->once())
			->method('lastInsertId')
			->with($this->equalTo('*PREFIX*table'))
			->will($this->returnValue(3));
		$this->mapper = new ExampleMapper($this->db);

		$sql = 'INSERT INTO `*PREFIX*table`(`pre_name`,`email`) ' .
				'VALUES(?,?)';
		$params = array('john', 'my@email');
		$entity = new Example();
		$entity->setPreName($params[0]);
		$entity->setEmail($params[1]);

		$this->setMapperResult($sql, $params, [], null, null, true);

		$this->mapper->insert($entity);
	}


	public function testCreateShouldReturnItemWithCorrectInsertId(){
		$this->db->expects($this->once())
			->method('lastInsertId')
			->with($this->equalTo('*PREFIX*table'))
			->will($this->returnValue(3));
		$this->mapper = new ExampleMapper($this->db);

		$sql = 'INSERT INTO `*PREFIX*table`(`pre_name`,`email`) ' .
				'VALUES(?,?)';
		$params = array('john', 'my@email');
		$entity = new Example();
		$entity->setPreName($params[0]);
		$entity->setEmail($params[1]);

		$this->setMapperResult($sql, $params);

		$result = $this->mapper->insert($entity);

		$this->assertEquals(3, $result->getId());
	}


	public function testAssocParameters() {
		$sql = 'test';
		$params = [':test' => 1, ':a' => 2];

		$this->setMapperResult($sql, $params);
		$this->mapper->execSql($sql, $params);
	}


	public function testUpdate(){
		$sql = 'UPDATE `*PREFIX*table` ' .
				'SET ' .
				'`pre_name` = ?,'.
				'`email` = ? ' .
				'WHERE `id` = ?';

		$params = array('john', 'my@email', 1);
		$entity = new Example();
		$entity->setPreName($params[0]);
		$entity->setEmail($params[1]);
		$entity->setId($params[2]);

		$this->setMapperResult($sql, $params, [], null, null, true);

		$this->mapper->update($entity);
	}


	public function testUpdateNoId(){
		$params = array('john', 'my@email');
		$entity = new Example();
		$entity->setPreName($params[0]);
		$entity->setEmail($params[1]);

		$this->setExpectedException('InvalidArgumentException');

		$this->mapper->update($entity);
	}


	public function testUpdateNothingChangedNoQuery(){
		$params = array('john', 'my@email');
		$entity = new Example();
		$entity->setId(3);
		$entity->setEmail($params[1]);
		$entity->resetUpdatedFields();

		$this->db->expects($this->never())
			->method('prepare');

		$this->mapper->update($entity);
	}


	public function testMapRowToEntity(){
		$entity1 = $this->mapper->mapRow(array('pre_name' => 'test1', 'email' => 'test2'));
		$entity2 = new Example();
		$entity2->setPreName('test1');
		$entity2->setEmail('test2');
		$entity2->resetUpdatedFields();
		$this->assertEquals($entity2, $entity1);
	}

	public function testFindEntities(){
		$sql = 'hi';
		$rows = array(
			array('pre_name' => 'hi')
		);
		$entity = new Example();
		$entity->setPreName('hi');
		$entity->resetUpdatedFields();
		$this->setMapperResult($sql, array(), $rows, null, null, true);
		$result = $this->mapper->findAllEntities($sql);
		$this->assertEquals(array($entity), $result);
	}

	public function testFindEntitiesNotFound(){
		$sql = 'hi';
		$rows = array();
		$this->setMapperResult($sql, array(), $rows);
		$result = $this->mapper->findAllEntities($sql);
		$this->assertEquals(array(), $result);
	}

	public function testFindEntitiesMultiple(){
		$sql = 'hi';
		$rows = array(
			array('pre_name' => 'jo'), array('email' => 'ho')
		);
		$entity1 = new Example();
		$entity1->setPreName('jo');
		$entity1->resetUpdatedFields();
		$entity2 = new Example();
		$entity2->setEmail('ho');
		$entity2->resetUpdatedFields();
		$this->setMapperResult($sql, array(), $rows);
		$result = $this->mapper->findAllEntities($sql);
		$this->assertEquals(array($entity1, $entity2), $result);
	}
}

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


namespace OCP\AppFramework\Db;


/**
 * Simple utility class for testing mappers
 */
abstract class MapperTestUtility extends \PHPUnit_Framework_TestCase {


	protected $db;
	private $query;
	private $pdoResult;
	private $queryAt;
	private $prepareAt;
	private $fetchAt;
	private $iterators;
	

	/**
	 * Run this function before the actual test to either set or initialize the
	 * db. After this the db can be accessed by using $this->db
	 */
	protected function setUp(){
		$this->db = $this->getMockBuilder(
			'\OCP\IDb')
			->disableOriginalConstructor()
			->getMock();

		$this->query = $this->getMock('Query', array('execute', 'bindValue'));
		$this->pdoResult = $this->getMock('Result', array('fetchRow'));
		$this->queryAt = 0;
		$this->prepareAt = 0;
		$this->iterators = array();
		$this->fetchAt = 0;
	}


	/**
	 * Create mocks and set expected results for database queries
	 * @param string $sql the sql query that you expect to receive
	 * @param array $arguments the expected arguments for the prepare query
	 * method
	 * @param array $returnRows the rows that should be returned for the result
	 * of the database query. If not provided, it wont be assumed that fetchRow
	 * will be called on the result
	 */
	protected function setMapperResult($sql, $arguments=array(), $returnRows=array(),
		$limit=null, $offset=null){

		$this->iterators[] = new ArgumentIterator($returnRows);

		$iterators = $this->iterators;
		$fetchAt = $this->fetchAt;

		$this->pdoResult->expects($this->any())
			->method('fetchRow')
			->will($this->returnCallback(
				function() use ($iterators, $fetchAt){
					$iterator = $iterators[$fetchAt];
					$result = $iterator->next();

					if($result === false) {
						$fetchAt++;
					}

					return $result;
			  	}
			));

		$index = 1;
		foreach($arguments as $argument) {
			switch (gettype($argument)) {
				case 'integer':
					$pdoConstant = \PDO::PARAM_INT;
					break;

				case 'NULL':
					$pdoConstant = \PDO::PARAM_NULL;
					break;

				case 'boolean':
					$pdoConstant = \PDO::PARAM_BOOL;
					break;
				
				default:
					$pdoConstant = \PDO::PARAM_STR;
					break;
			}
			$this->query->expects($this->at($this->queryAt))
				->method('bindValue')
				->with($this->equalTo($index),
					$this->equalTo($argument),
					$this->equalTo($pdoConstant));
			$index++;
			$this->queryAt++;
		}

		$this->query->expects($this->at($this->queryAt))
			->method('execute')
			->with()
			->will($this->returnValue($this->pdoResult));
		$this->queryAt++;

		if($limit === null && $offset === null) {
			$this->db->expects($this->at($this->prepareAt))
				->method('prepareQuery')
				->with($this->equalTo($sql))
				->will(($this->returnValue($this->query)));
		} elseif($limit !== null && $offset === null) {
			$this->db->expects($this->at($this->prepareAt))
				->method('prepareQuery')
				->with($this->equalTo($sql), $this->equalTo($limit))
				->will(($this->returnValue($this->query)));
		} elseif($limit === null && $offset !== null) {
			$this->db->expects($this->at($this->prepareAt))
				->method('prepareQuery')
				->with($this->equalTo($sql), 
					$this->equalTo(null),
					$this->equalTo($offset))
				->will(($this->returnValue($this->query)));
		} else  {
			$this->db->expects($this->at($this->prepareAt))
				->method('prepareQuery')
				->with($this->equalTo($sql), 
					$this->equalTo($limit),
					$this->equalTo($offset))
				->will(($this->returnValue($this->query)));
		}
		$this->prepareAt++;
		$this->fetchAt++;

	}


}


class ArgumentIterator {

	private $arguments;
	
	public function __construct($arguments){
		$this->arguments = $arguments;
	}
	
	public function next(){
		$result = array_shift($this->arguments);
		if($result === null){
			return false;
		} else {
			return $result;
		}
	}
}


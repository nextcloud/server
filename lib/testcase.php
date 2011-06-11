<?php

/**
* ownCloud
*
* @author Robin Appelman
* @copyright 2010 Robin Appelman icewind1991@gmailc.om
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


/**
 * base class for unit tests
 */
class OC_TestCase{
	private $tests; //array of all tests in this test case
	
	public function __construct(){
		$this->tests=array();
		$this->results=array();
		$functions=get_class_methods(get_class($this));
		$exclude=get_class_methods('OC_TestCase');
		foreach($functions as $function){
			if(array_search($function,$exclude)===false){
				$this->tests[]=$function;
			}
		}
	}
	
	public function getTests(){
		return $this->tests;
	}
	
	/**
	 * function that gets called before each test
	 */
	private function setup(){
	}

	/**
	 * function that gets called after each test
	 */
	private function tearDown(){
	}
	
	/**
	 * check if the result equals the expected result
	 * @param mixed $expected the expected result
	 * @param mixed $result the actual result
	 * @param string $error (optional) the error message to display if the result isn't expected
	 */
	protected function assertEquals($expected,$result,$error=''){
		if($expected!==$result){
			if($expected===true){
				$expected='true';
			}
			if($expected===false){
				$expected='false';
			}
			if($result===true){
				$result='true';
			}
			if($result===false){
				$result='false';
			}
			if($error==''){
				$error="Unexpected result, expected '$expected' but was '$result'";
			}
			throw new Exception($error);
		}
	}

	/**
	 * fail the test
	 * @param string $error the error message
	 */
	protected function fail($error){
		throw new Exception($error);
	}
}
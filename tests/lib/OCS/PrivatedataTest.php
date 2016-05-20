<?php
 /**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller deepdiver@owncloud.com
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

namespace Test\OCS;

use OC_OCS_Privatedata;

/**
 * Class PrivatedataTest
 *
 * @group DB
 */
class PrivatedataTest extends \Test\TestCase {
	private $appKey;

	protected function setUp() {
		parent::setUp();
		\OC::$server->getSession()->set('user_id', 'user1');
		$this->appKey = $this->getUniqueID('app');
	}

	public function testGetEmptyOne() {
		$params = array('app' => $this->appKey, 'key' => '123');
		$result = OC_OCS_Privatedata::get($params);
		$this->assertOcsResult(0, $result);
	}

	public function testGetEmptyAll() {
		$params = array('app' => $this->appKey);
		$result = OC_OCS_Privatedata::get($params);
		$this->assertOcsResult(0, $result);
	}

	public function testSetOne() {
		$_POST = array('value' => 123456789);
		$params = array('app' => $this->appKey, 'key' => 'k-1');
		$result = OC_OCS_Privatedata::set($params);
		$this->assertEquals(100, $result->getStatusCode());

		$result = OC_OCS_Privatedata::get($params);
		$this->assertOcsResult(1, $result);
	}

	public function testSetExisting() {
		$_POST = array('value' => 123456789);
		$params = array('app' => $this->appKey, 'key' => 'k-10');
		$result = OC_OCS_Privatedata::set($params);
		$this->assertEquals(100, $result->getStatusCode());

		$result = OC_OCS_Privatedata::get($params);
		$this->assertOcsResult(1, $result);
		$data = $result->getData();
		$data = $data[0];
		$this->assertEquals('123456789', $data['value']);

		$_POST = array('value' => 'updated');
		$params = array('app' => $this->appKey, 'key' => 'k-10');
		$result = OC_OCS_Privatedata::set($params);
		$this->assertEquals(100, $result->getStatusCode());

		$result = OC_OCS_Privatedata::get($params);
		$this->assertOcsResult(1, $result);
		$data = $result->getData();
		$data = $data[0];
		$this->assertEquals('updated', $data['value']);
	}

	public function testSetSameValue() {
		$_POST = array('value' => 123456789);
		$params = array('app' => $this->appKey, 'key' => 'k-10');
		$result = OC_OCS_Privatedata::set($params);
		$this->assertEquals(100, $result->getStatusCode());

		$result = OC_OCS_Privatedata::get($params);
		$this->assertOcsResult(1, $result);
		$data = $result->getData();
		$data = $data[0];
		$this->assertEquals('123456789', $data['value']);

		// set the same value again
		$_POST = array('value' => 123456789);
		$params = array('app' => $this->appKey, 'key' => 'k-10');
		$result = OC_OCS_Privatedata::set($params);
		$this->assertEquals(100, $result->getStatusCode());

		$result = OC_OCS_Privatedata::get($params);
		$this->assertOcsResult(1, $result);
		$data = $result->getData();
		$data = $data[0];
		$this->assertEquals('123456789', $data['value']);
	}

	public function testSetMany() {
		$_POST = array('value' => 123456789);

		// set key 'k-1'
		$params = array('app' => $this->appKey, 'key' => 'k-1');
		$result = OC_OCS_Privatedata::set($params);
		$this->assertEquals(100, $result->getStatusCode());

		// set key 'k-2'
		$params = array('app' => $this->appKey, 'key' => 'k-2');
		$result = OC_OCS_Privatedata::set($params);
		$this->assertEquals(100, $result->getStatusCode());

		// query for all
		$params = array('app' => $this->appKey);
		$result = OC_OCS_Privatedata::get($params);
		$this->assertOcsResult(2, $result);
	}

	public function testDelete() {
		$_POST = array('value' => 123456789);

		// set key 'k-1'
		$params = array('app' => $this->appKey, 'key' => 'k-3');
		$result = OC_OCS_Privatedata::set($params);
		$this->assertEquals(100, $result->getStatusCode());

		$result = OC_OCS_Privatedata::delete($params);
		$this->assertEquals(100, $result->getStatusCode());

		$result = OC_OCS_Privatedata::get($params);
		$this->assertOcsResult(0, $result);
	}

	/**
	 * @dataProvider deleteWithEmptyKeysProvider
	 */
	public function testDeleteWithEmptyKeys($params) {
		$result = OC_OCS_Privatedata::delete($params);
		$this->assertEquals(101, $result->getStatusCode());
	}

	public function deleteWithEmptyKeysProvider() {
		return array(
			array(array()),
			array(array('app' => '123')),
			array(array('key' => '123')),
		);
	}

	/**
	 * @param \OC_OCS_Result $result
	 * @param integer $expectedArraySize
	 */
	public function assertOcsResult($expectedArraySize, $result) {
		$this->assertEquals(100, $result->getStatusCode());
		$data = $result->getData();
		$this->assertTrue(is_array($data));
		$this->assertEquals($expectedArraySize, sizeof($data));
	}
}

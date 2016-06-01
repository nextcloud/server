<?php
/**
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test;

class CapabilitiesManagerTest extends TestCase {

	/**
	 * Test no capabilities
	 */
	public function testNoCapabilities() {
		$manager = new \OC\CapabilitiesManager();
		$res = $manager->getCapabilities();
		$this->assertEmpty($res);
	}

	/**
	 * Test a valid capabilitie
	 */
	public function testValidCapability() {
		$manager = new \OC\CapabilitiesManager();

		$manager->registerCapability(function() {
			return new SimpleCapability();
		});

		$res = $manager->getCapabilities();
		$this->assertEquals(['foo' => 1], $res);
	}

	/**
	 * Test that we need something that implents ICapability
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage The given Capability (Test\NoCapability) does not implement the ICapability interface
	 */
	public function testNoICapability() {
		$manager = new \OC\CapabilitiesManager();

		$manager->registerCapability(function() {
			return new NoCapability();
		});

		$res = $manager->getCapabilities();
		$this->assertEquals([], $res);
	}

	/**
	 * Test a bunch of merged Capabilities
	 */
	public function testMergedCapabilities() {
		$manager = new \OC\CapabilitiesManager();

		$manager->registerCapability(function() {
			return new SimpleCapability();
		});
		$manager->registerCapability(function() {
			return new SimpleCapability2();
		});
		$manager->registerCapability(function() {
			return new SimpleCapability3();
		});

		$res = $manager->getCapabilities();
		$expected = [
			'foo' => 1,
			'bar' => [
				'x' => 1,
				'y' => 2
			]
		];

		$this->assertEquals($expected, $res);
	}

	/**
	 * Test deep identical capabilities
	 */
	public function testDeepIdenticalCapabilities() {
		$manager = new \OC\CapabilitiesManager();

		$manager->registerCapability(function() {
			return new DeepCapability();
		});
		$manager->registerCapability(function() {
			return new DeepCapability();
		});

		$res = $manager->getCapabilities();
		$expected = [
			'foo' => [
				'bar' => [
					'baz' => true
				]
			]
		];
		
		$this->assertEquals($expected, $res);
	}
}

class SimpleCapability implements \OCP\Capabilities\ICapability {
	public function getCapabilities() {
		return [
			'foo' => 1
		];
	}
}

class SimpleCapability2 implements \OCP\Capabilities\ICapability {
	public function getCapabilities() {
		return [
			'bar' => ['x' => 1]
		];
	}
}

class SimpleCapability3 implements \OCP\Capabilities\ICapability {
	public function getCapabilities() {
		return [
			'bar' => ['y' => 2]
		];
	}
}

class NoCapability {
	public function getCapabilities() {
		return [
			'baz' => 'z'
		];
	}
}

class DeepCapability implements \OCP\Capabilities\ICapability {
	public function getCapabilities() {
		return [
			'foo' => [
				'bar' => [
					'baz' => true
				]
			]
		];
	}
}


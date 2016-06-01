<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
namespace Test\Share20;

use OCP\Files\IRootFolder;

/**
 * Class ShareTest
 *
 * @package Test\Share20
 */
class ShareTest extends \Test\TestCase {

	/** @var IRootFolder|\PHPUnit_Framework_MockObject_MockObject */
	protected $rootFolder;
	/** @var \OCP\Share\IShare */
	protected $share;

	public function setUp() {
		$this->rootFolder = $this->getMock('\OCP\Files\IRootFolder');
		$this->userManager = $this->getMock('OCP\IUserManager');
		$this->share = new \OC\Share20\Share($this->rootFolder, $this->userManager);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage String expected.
	 */
	public function testSetIdInvalid() {
		$this->share->setId(1.2);
	}

	public function testSetIdInt() {
		$this->share->setId(42);
		$this->assertEquals('42', $this->share->getId());
	}


	public function testSetIdString() {
		$this->share->setId('foo');
		$this->assertEquals('foo', $this->share->getId());
	}

	/**
	 * @expectedException \OCP\Share\Exceptions\IllegalIDChangeException
	 * @expectedExceptionMessage Not allowed to assign a new internal id to a share
	 */
	public function testSetIdOnce() {
		$this->share->setId('foo');
		$this->share->setId('bar');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage String expected.
	 */
	public function testSetProviderIdInt() {
		$this->share->setProviderId(42);
	}


	public function testSetProviderIdString() {
		$this->share->setProviderId('foo');
		$this->share->setId('bar');
		$this->assertEquals('foo:bar', $this->share->getFullId());
	}

	/**
	 * @expectedException \OCP\Share\Exceptions\IllegalIDChangeException
	 * @expectedExceptionMessage Not allowed to assign a new provider id to a share
	 */
	public function testSetProviderIdOnce() {
		$this->share->setProviderId('foo');
		$this->share->setProviderId('bar');
	}
}

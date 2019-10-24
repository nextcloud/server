<?php
/**
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @copyright Copyright (c) 2018 Georg Ehrke <oc.list@georgehrke.com>
 * @license GNU AGPL version 3 or any later version
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

namespace OCA\DAV\Tests\unit\Provisioning\Apple;

use OCA\DAV\Provisioning\Apple\AppleProvisioningNode;
use OCP\AppFramework\Utility\ITimeFactory;
use Sabre\DAV\PropPatch;
use Test\TestCase;

class AppleProvisioningNodeTest extends TestCase {

	/** @var ITimeFactory|\PHPUnit_Framework_MockObject_MockObject */
	private $timeFactory;

	/** @var AppleProvisioningNode */
	private $node;

	public function setUp() {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->node = new AppleProvisioningNode($this->timeFactory);
	}

	public function testGetName() {
		$this->assertEquals('apple-provisioning.mobileconfig', $this->node->getName());
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Renaming apple-provisioning.mobileconfig is forbidden
	 */
	public function testSetName() {
		$this->node->setName('foo');
	}

	public function testGetLastModified() {
		$this->assertEquals(null, $this->node->getLastModified());
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage apple-provisioning.mobileconfig may not be deleted
	 */
	public function testDelete() {
		$this->node->delete();
	}

	public function testGetProperties() {
		$this->timeFactory->expects($this->at(0))
			->method('getDateTime')
			->willReturn(new \DateTime('2000-01-01'));

		$this->assertEquals([
			'{DAV:}getcontentlength' => 42,
			'{DAV:}getlastmodified' => 'Sat, 01 Jan 2000 00:00:00 +0000',
		], $this->node->getProperties([]));
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage apple-provisioning.mobileconfig's properties may not be altered.
	 */
	public function testGetPropPatch() {
		$propPatch = $this->createMock(PropPatch::class);

		$this->node->propPatch($propPatch);
	}
}
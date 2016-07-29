<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCP\IGroupManager;
use \Sabre\DAV\PropPatch;
use OCP\IUserManager;
use Test\TestCase;

class PrincipalTest extends TestCase {
	/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var \OCA\DAV\Connector\Sabre\Principal */
	private $connector;
	/** @var IGroupManager | \PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;

	public function setUp() {
		$this->userManager = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()->getMock();
		$this->groupManager = $this->getMockBuilder('\OCP\IGroupManager')
			->disableOriginalConstructor()->getMock();

		$this->connector = new \OCA\DAV\Connector\Sabre\Principal(
			$this->userManager,
			$this->groupManager);
		parent::setUp();
	}

	public function testGetPrincipalsByPrefixWithoutPrefix() {
		$response = $this->connector->getPrincipalsByPrefix('');
		$this->assertSame([], $response);
	}

	public function testGetPrincipalsByPrefixWithUsers() {
		$fooUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$fooUser
				->expects($this->exactly(1))
				->method('getUID')
				->will($this->returnValue('foo'));
		$fooUser
				->expects($this->exactly(1))
				->method('getDisplayName')
				->will($this->returnValue('Dr. Foo-Bar'));
		$fooUser
				->expects($this->exactly(1))
				->method('getEMailAddress')
				->will($this->returnValue(''));
		$barUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$barUser
			->expects($this->exactly(1))
			->method('getUID')
			->will($this->returnValue('bar'));
		$barUser
				->expects($this->exactly(1))
				->method('getEMailAddress')
				->will($this->returnValue('bar@owncloud.org'));
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([$fooUser, $barUser]));

		$expectedResponse = [
			0 => [
				'uri' => 'principals/users/foo',
				'{DAV:}displayname' => 'Dr. Foo-Bar'
			],
			1 => [
				'uri' => 'principals/users/bar',
				'{DAV:}displayname' => 'bar',
				'{http://sabredav.org/ns}email-address' => 'bar@owncloud.org'
			]
		];
		$response = $this->connector->getPrincipalsByPrefix('principals/users');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPrefixEmpty() {
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([]));

		$response = $this->connector->getPrincipalsByPrefix('principals/users');
		$this->assertSame([], $response);
	}

	public function testGetPrincipalsByPathWithoutMail() {
		$fooUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$fooUser
			->expects($this->exactly(1))
			->method('getUID')
			->will($this->returnValue('foo'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));

		$expectedResponse = [
			'uri' => 'principals/users/foo',
			'{DAV:}displayname' => 'foo'
		];
		$response = $this->connector->getPrincipalByPath('principals/users/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathWithMail() {
		$fooUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$fooUser
				->expects($this->exactly(1))
				->method('getEMailAddress')
				->will($this->returnValue('foo@owncloud.org'));
		$fooUser
				->expects($this->exactly(1))
				->method('getUID')
				->will($this->returnValue('foo'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));

		$expectedResponse = [
			'uri' => 'principals/users/foo',
			'{DAV:}displayname' => 'foo',
			'{http://sabredav.org/ns}email-address' => 'foo@owncloud.org'
		];
		$response = $this->connector->getPrincipalByPath('principals/users/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathEmpty() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue(null));

		$response = $this->connector->getPrincipalByPath('principals/users/foo');
		$this->assertSame(null, $response);
	}

	public function testGetGroupMemberSet() {
		$fooUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$fooUser
			->expects($this->exactly(1))
			->method('getUID')
			->will($this->returnValue('foo'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));

		$response = $this->connector->getGroupMemberSet('principals/users/foo');
		$this->assertSame(['principals/users/foo'], $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception
	 * @expectedExceptionMessage Principal not found
	 */
	public function testGetGroupMemberSetEmpty() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue(null));

		$this->connector->getGroupMemberSet('principals/users/foo');
	}

	public function testGetGroupMembership() {
		$fooUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$group = $this->getMockBuilder('\OCP\IGroup')
			->disableOriginalConstructor()->getMock();
		$group->expects($this->once())
			->method('getGID')
			->willReturn('group1');
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($fooUser);
		$this->groupManager
			->expects($this->once())
			->method('getUserGroups')
			->willReturn([
				$group
			]);

		$expectedResponse = [
			'principals/groups/group1'
		];
		$response = $this->connector->getGroupMembership('principals/users/foo');
		$this->assertSame($expectedResponse, $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception
	 * @expectedExceptionMessage Principal not found
	 */
	public function testGetGroupMembershipEmpty() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue(null));

		$this->connector->getGroupMembership('principals/users/foo');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception
	 * @expectedExceptionMessage Setting members of the group is not supported yet
	 */
	public function testSetGroupMembership() {
		$this->connector->setGroupMemberSet('principals/users/foo', ['foo']);
	}

	public function testUpdatePrincipal() {
		$this->assertSame(0, $this->connector->updatePrincipal('foo', new PropPatch(array())));
	}

	public function testSearchPrincipals() {
		$this->assertSame([], $this->connector->searchPrincipals('principals/users', []));
	}

	public function testFindByUri() {
		$fooUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$fooUser
			->expects($this->exactly(1))
			->method('getUID')
			->will($this->returnValue('foo'));

		$this->userManager->expects($this->once())->method('getByEmail')->willReturn([
			$fooUser
		]);
		$ret = $this->connector->findByUri('mailto:foo@bar.net', 'principals/users');
		$this->assertSame('principals/users/foo', $ret);
	}
}

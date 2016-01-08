<?php

namespace OCA\DAV\Tests\Unit\DAV;

use OCA\DAV\DAV\GroupPrincipalBackend;
use OCP\IGroupManager;
use PHPUnit_Framework_MockObject_MockObject;
use \Sabre\DAV\PropPatch;

class GroupPrincipalTest extends \Test\TestCase {

	/** @var IGroupManager | PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;

	/** @var GroupPrincipalBackend */
	private $connector;

	public function setUp() {
		$this->groupManager = $this->getMockBuilder('\OCP\IGroupManager')
			->disableOriginalConstructor()->getMock();

		$this->connector = new GroupPrincipalBackend($this->groupManager);
		parent::setUp();
	}

	public function testGetPrincipalsByPrefixWithoutPrefix() {
		$response = $this->connector->getPrincipalsByPrefix('');
		$this->assertSame([], $response);
	}

	public function testGetPrincipalsByPrefixWithUsers() {
		$group1 = $this->mockGroup('foo');
		$group2 = $this->mockGroup('bar');
		$this->groupManager
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([$group1, $group2]));

		$expectedResponse = [
			0 => [
				'uri' => 'principals/groups/foo',
				'{DAV:}displayname' => 'foo'
			],
			1 => [
				'uri' => 'principals/groups/bar',
				'{DAV:}displayname' => 'bar',
			]
		];
		$response = $this->connector->getPrincipalsByPrefix('principals/groups');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPrefixEmpty() {
		$this->groupManager
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([]));

		$response = $this->connector->getPrincipalsByPrefix('principals/groups');
		$this->assertSame([], $response);
	}

	public function testGetPrincipalsByPathWithoutMail() {
		$group1 = $this->mockGroup('foo');
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($group1));

		$expectedResponse = [
			'uri' => 'principals/groups/foo',
			'{DAV:}displayname' => 'foo'
		];
		$response = $this->connector->getPrincipalByPath('principals/groups/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathWithMail() {
		$fooUser = $this->mockGroup('foo');
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));

		$expectedResponse = [
			'uri' => 'principals/groups/foo',
			'{DAV:}displayname' => 'foo',
		];
		$response = $this->connector->getPrincipalByPath('principals/groups/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathEmpty() {
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue(null));

		$response = $this->connector->getPrincipalByPath('principals/groups/foo');
		$this->assertSame(null, $response);
	}

	public function testGetGroupMemberSet() {
		$response = $this->connector->getGroupMemberSet('principals/groups/foo');
		$this->assertSame([], $response);
	}

	public function testGetGroupMembership() {
		$response = $this->connector->getGroupMembership('principals/groups/foo');
		$this->assertSame([], $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception
	 * @expectedExceptionMessage Setting members of the group is not supported yet
	 */
	public function testSetGroupMembership() {
		$this->connector->setGroupMemberSet('principals/groups/foo', ['foo']);
	}

	public function testUpdatePrincipal() {
		$this->assertSame(0, $this->connector->updatePrincipal('foo', new PropPatch(array())));
	}

	public function testSearchPrincipals() {
		$this->assertSame([], $this->connector->searchPrincipals('principals/groups', []));
	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	private function mockGroup($gid) {
		$fooUser = $this->getMockBuilder('\OC\Group\Group')
			->disableOriginalConstructor()->getMock();
		$fooUser
			->expects($this->exactly(1))
			->method('getGID')
			->will($this->returnValue($gid));
		return $fooUser;
	}
}

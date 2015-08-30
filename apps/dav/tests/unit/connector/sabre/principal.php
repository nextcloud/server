<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Connector\Sabre;

use \Sabre\DAV\PropPatch;
use OCP\IUserManager;
use OCP\IConfig;

class Principal extends \Test\TestCase {
	/** @var IUserManager */
	private $userManager;
	/** @var IConfig */
	private $config;
	/** @var \OCA\DAV\Connector\Sabre\Principal */
	private $connector;

	public function setUp() {
		$this->userManager = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();

		$this->connector = new \OCA\DAV\Connector\Sabre\Principal($this->config, $this->userManager);
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
			->expects($this->exactly(3))
			->method('getUID')
			->will($this->returnValue('foo'));
		$barUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$barUser
			->expects($this->exactly(3))
			->method('getUID')
			->will($this->returnValue('bar'));
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([$fooUser, $barUser]));
		$this->config
			->expects($this->at(0))
			->method('getUserValue')
			->with('foo', 'settings', 'email')
			->will($this->returnValue(''));
		$this->config
			->expects($this->at(1))
			->method('getUserValue')
			->with('bar', 'settings', 'email')
			->will($this->returnValue('bar@owncloud.org'));

		$expectedResponse = [
			0 => [
				'uri' => 'principals/foo',
				'{DAV:}displayname' => 'foo'
			],
			1 => [
				'uri' => 'principals/bar',
				'{DAV:}displayname' => 'bar',
				'{http://sabredav.org/ns}email-address' => 'bar@owncloud.org'
			]
		];
		$response = $this->connector->getPrincipalsByPrefix('principals');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPrefixEmpty() {
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([]));

		$response = $this->connector->getPrincipalsByPrefix('principals');
		$this->assertSame([], $response);
	}

	public function testGetPrincipalsByPathWithoutMail() {
		$fooUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$fooUser
			->expects($this->exactly(3))
			->method('getUID')
			->will($this->returnValue('foo'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));
		$this->config
			->expects($this->once())
			->method('getUserValue')
			->with('foo', 'settings', 'email')
			->will($this->returnValue(''));

		$expectedResponse = [
			'uri' => 'principals/foo',
			'{DAV:}displayname' => 'foo'
		];
		$response = $this->connector->getPrincipalByPath('principals/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathWithMail() {
		$fooUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$fooUser
			->expects($this->exactly(3))
			->method('getUID')
			->will($this->returnValue('foo'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));
		$this->config
			->expects($this->once())
			->method('getUserValue')
			->with('foo', 'settings', 'email')
			->will($this->returnValue('foo@owncloud.org'));

		$expectedResponse = [
			'uri' => 'principals/foo',
			'{DAV:}displayname' => 'foo',
			'{http://sabredav.org/ns}email-address' => 'foo@owncloud.org'
		];
		$response = $this->connector->getPrincipalByPath('principals/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathEmpty() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue(null));

		$response = $this->connector->getPrincipalByPath('principals/foo');
		$this->assertSame(null, $response);
	}

	public function testGetGroupMemberSet() {
		$fooUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$fooUser
			->expects($this->exactly(3))
			->method('getUID')
			->will($this->returnValue('foo'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));
		$this->config
			->expects($this->once())
			->method('getUserValue')
			->with('foo', 'settings', 'email')
			->will($this->returnValue('foo@owncloud.org'));

		$response = $this->connector->getGroupMemberSet('principals/foo');
		$this->assertSame(['principals/foo'], $response);
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

		$this->connector->getGroupMemberSet('principals/foo');
	}

	public function testGetGroupMembership() {
		$fooUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$fooUser
			->expects($this->exactly(3))
			->method('getUID')
			->will($this->returnValue('foo'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));
		$this->config
			->expects($this->once())
			->method('getUserValue')
			->with('foo', 'settings', 'email')
			->will($this->returnValue('foo@owncloud.org'));

		$expectedResponse = [
			'principals/foo/calendar-proxy-read',
			'principals/foo/calendar-proxy-write'
		];
		$response = $this->connector->getGroupMembership('principals/foo');
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

		$this->connector->getGroupMembership('principals/foo');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception
	 * @expectedExceptionMessage Setting members of the group is not supported yet
	 */
	public function testSetGroupMembership() {
		$this->connector->setGroupMemberSet('principals/foo', ['foo']);
	}

	public function testUpdatePrincipal() {
		$this->assertSame(0, $this->connector->updatePrincipal('foo', new PropPatch(array())));
	}

	public function testSearchPrincipals() {
		$this->assertSame([], $this->connector->searchPrincipals('principals', []));
	}
}

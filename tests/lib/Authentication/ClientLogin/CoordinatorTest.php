<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
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

namespace Test\Authentication\ClientLogin;

use OC\Authentication\ClientLogin\AccessToken;
use OC\Authentication\ClientLogin\Coordinator;
use OC\Authentication\Exceptions\InvalidAccessTokenException;
use OC\Authentication\Token\IToken;
use OCP\AppFramework\Db\DoesNotExistException;
use Test\TestCase;

class CoordinatorTest extends TestCase {

	private $mapper;
	private $tokenProvider;
	private $random;
	private $config;
	private $timeFactory;

	/** @var Coordinator */
	private $coordinator;

	protected function setUp() {
		parent::setUp();

		$this->mapper = $this->getMockBuilder('\OC\Authentication\ClientLogin\AccessTokenMapper')
			->disableOriginalConstructor()
			->getMock();
		$this->tokenProvider = $this->getMock('\OC\Authentication\Token\IProvider');
		$this->random = $this->getMock('\OCP\Security\ISecureRandom');
		$this->config = $this->getMock('\OCP\IConfig');
		$this->timeFactory = $this->getMock('\OCP\AppFramework\Utility\ITimeFactory');

		$this->coordinator = new Coordinator($this->mapper, $this->tokenProvider, $this->random, $this->config, $this->timeFactory);
	}

	public function testStartClientLogin() {
		$this->random->expects($this->once())
			->method('generate')
			->with(128)
			->will($this->returnValue('randomtoken'));
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('secret')
			->will($this->returnValue('abc'));

		$accessToken = new AccessToken();
		$accessToken->setToken(hash('sha512', 'randomtokenabc'));
		$accessToken->setClientName('sync client');
		$accessToken->setStatus(AccessToken::STATUS_PENDING);
		$this->timeFactory->expects($this->once())
			->method('getTime')
			->will($this->returnValue(1000));
		$accessToken->setCreatedAt(1000);

		$this->mapper->expects($this->once())
			->method('insert')
			->with($accessToken);

		$this->assertEquals('randomtoken', $this->coordinator->startClientLogin('sync client'));
	}

	public function testFinishClientLogin() {
		$accessToken = 'secrettoken';
		$user = $this->getMock('\OCP\IUser');
		$dbToken = $this->getMockBuilder('\OC\Authentication\ClientLogin\AccessToken')
			->disableOriginalConstructor()
			->setMethods(['setStatus', 'setUid'])
			->getMock();

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('secret')
			->will($this->returnValue('abc'));
		$this->mapper->expects($this->once())
			->method('getToken')
			->with(hash('sha512', 'secrettokenabc'))
			->will($this->returnValue($dbToken));

		$dbToken->expects($this->once())
			->method('setStatus')
			->with(AccessToken::STATUS_FINISHED);
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('user123'));
		$dbToken->expects($this->once())
			->method('setUid')
			->with('user123');
		$this->mapper->expects($this->once())
			->method('update')
			->with($dbToken);

		$this->coordinator->finishClientLogin($accessToken, $user);
	}

	/**
	 * @expectedException \OC\Authentication\Exceptions\InvalidAccessTokenException
	 */
	public function testFinishClientLoginInvalidToken() {
		$accessToken = 'secrettoken';
		$user = $this->getMock('\OCP\IUser');

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('secret')
			->will($this->returnValue('abc'));
		$this->mapper->expects($this->once())
			->method('getToken')
			->with(hash('sha512', 'secrettokenabc'))
			->will($this->throwException(new DoesNotExistException('token does not exist')));

		$this->coordinator->finishClientLogin($accessToken, $user);
	}

	public function testGetClientToken() {
		$accessToken = 'secrettoken';
		$dbToken = $this->getMockBuilder('\OC\Authentication\ClientLogin\AccessToken')
			->disableOriginalConstructor()
			->setMethods(['getStatus', 'getUid', 'getClientName'])
			->getMock();
		$clientToken = $this->getMock('\OC\Authentication\Token\IToken');

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('secret')
			->will($this->returnValue('abc'));
		$this->mapper->expects($this->once())
			->method('getToken')
			->with(hash('sha512', 'secrettokenabc'))
			->will($this->returnValue($dbToken));

		$dbToken->expects($this->once())
			->method('getStatus')
			->will($this->returnValue(AccessToken::STATUS_FINISHED));
		$this->mapper->expects($this->once())
			->method('delete')
			->with($dbToken);

		$this->random->expects($this->once())
			->method('generate')
			->with(128)
			->will($this->returnValue('securerandom'));
		$dbToken->expects($this->once())
			->method('getUID')
			->will($this->returnValue('user123'));
		$dbToken->expects($this->once())
			->method('getClientName')
			->will($this->returnValue('sync client'));
		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with('securerandom', 'user123', '?', '?', 'sync client', IToken::PERMANENT_TOKEN)
			->will($this->returnValue($clientToken));

		$this->assertEquals('securerandom', $this->coordinator->getClientToken($accessToken));
	}

	/**
	 * @expectedException OC\Authentication\Exceptions\InvalidAccessTokenException
	 */
	public function testGetClientTokenInvalidToken() {
		$accessToken = 'secrettoken';

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('secret')
			->will($this->returnValue('abc'));
		$this->mapper->expects($this->once())
			->method('getToken')
			->with(hash('sha512', 'secrettokenabc'))
			->will($this->throwException(new DoesNotExistException('')));

		$this->coordinator->getClientToken($accessToken);
	}

	/**
	 * @expectedException \OC\Authentication\Exceptions\ClientLoginPendingException
	 */
	public function testGetClientTOkenPendingState() {
		$accessToken = 'secrettoken';
		$dbToken = $this->getMockBuilder('\OC\Authentication\ClientLogin\AccessToken')
			->disableOriginalConstructor()
			->setMethods(['getStatus'])
			->getMock();

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('secret')
			->will($this->returnValue('abc'));
		$this->mapper->expects($this->once())
			->method('getToken')
			->with(hash('sha512', 'secrettokenabc'))
			->will($this->returnValue($dbToken));

		$dbToken->expects($this->once())
			->method('getStatus')
			->will($this->returnValue(AccessToken::STATUS_PENDING));

		$this->coordinator->getClientToken($accessToken);
	}

}

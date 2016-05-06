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

namespace Test\Authentication\Token;

use OC\Authentication\Token\DefaultToken;
use OC\Authentication\Token\DefaultTokenProvider;
use OC\Authentication\Token\IToken;
use OCP\AppFramework\Db\DoesNotExistException;
use Test\TestCase;

class DefaultTokenProviderTest extends TestCase {

	/** @var DefaultTokenProvider */
	private $tokenProvider;
	private $mapper;
	private $crypto;
	private $config;
	private $logger;
	private $timeFactory;
	private $time;

	protected function setUp() {
		parent::setUp();

		$this->mapper = $this->getMockBuilder('\OC\Authentication\Token\DefaultTokenMapper')
			->disableOriginalConstructor()
			->getMock();
		$this->crypto = $this->getMock('\OCP\Security\ICrypto');
		$this->config = $this->getMock('\OCP\IConfig');
		$this->logger = $this->getMock('\OCP\ILogger');
		$this->timeFactory = $this->getMock('\OCP\AppFramework\Utility\ITimeFactory');
		$this->time = 1313131;
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->will($this->returnValue($this->time));

		$this->tokenProvider = new DefaultTokenProvider($this->mapper, $this->crypto, $this->config, $this->logger, $this->timeFactory);
	}

	public function testGenerateToken() {
		$token = 'token';
		$uid = 'user';
		$password = 'passme';
		$name = 'Some browser';
		$type = IToken::PERMANENT_TOKEN;

		$toInsert = new DefaultToken();
		$toInsert->setUid($uid);
		$toInsert->setPassword('encryptedpassword');
		$toInsert->setName($name);
		$toInsert->setToken(hash('sha512', $token . '1f4h9s'));
		$toInsert->setType($type);
		$toInsert->setLastActivity($this->time);

		$this->config->expects($this->any())
			->method('getSystemValue')
			->with('secret')
			->will($this->returnValue('1f4h9s'));
		$this->crypto->expects($this->once())
			->method('encrypt')
			->with($password, $token . '1f4h9s')
			->will($this->returnValue('encryptedpassword'));
		$this->mapper->expects($this->once())
			->method('insert')
			->with($this->equalTo($toInsert));

		$actual = $this->tokenProvider->generateToken($token, $uid, $password, $name, $type);

		$this->assertEquals($toInsert, $actual);
	}

	public function testUpdateToken() {
		$tk = new DefaultToken();
		$this->mapper->expects($this->once())
			->method('update')
			->with($tk);

		$this->tokenProvider->updateToken($tk);

		$this->assertEquals($this->time, $tk->getLastActivity());
	}

	public function testGetPassword() {
		$token = 'token1234';
		$tk = new DefaultToken();
		$tk->setPassword('someencryptedvalue');
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('secret')
			->will($this->returnValue('1f4h9s'));
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('someencryptedvalue', $token . '1f4h9s')
			->will($this->returnValue('passme'));

		$actual = $this->tokenProvider->getPassword($tk, $token);

		$this->assertEquals('passme', $actual);
	}

	public function testInvalidateToken() {
		$this->mapper->expects($this->once())
			->method('invalidate')
			->with(hash('sha512', 'token7'));

		$this->tokenProvider->invalidateToken('token7');
	}

	public function testInvalidateOldTokens() {
		$defaultSessionLifetime = 60 * 60 * 24;
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('session_lifetime', $defaultSessionLifetime)
			->will($this->returnValue(150));
		$this->mapper->expects($this->once())
			->method('invalidateOld')
			->with($this->time - 150);

		$this->tokenProvider->invalidateOldTokens();
	}

	public function testValidateToken() {
		$token = 'sometoken';
		$dbToken = new DefaultToken();
		$this->mapper->expects($this->once())
			->method('getToken')
			->with(hash('sha512', $token))
			->will($this->returnValue($dbToken));

		$actual = $this->tokenProvider->validateToken($token);

		$this->assertEquals($dbToken, $actual);
	}

	/**
	 * @expectedException \OC\Authentication\Exceptions\InvalidTokenException
	 */
	public function testValidateInvalidToken() {
		$token = 'sometoken';
		$this->mapper->expects($this->once())
			->method('getToken')
			->with(hash('sha512', $token))
			->will($this->throwException(new DoesNotExistException('')));

		$this->tokenProvider->validateToken($token);
	}

}

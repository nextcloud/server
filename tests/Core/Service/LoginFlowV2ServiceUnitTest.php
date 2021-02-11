<?php
/**
 * @author Konrad Abicht <hi@inspirito.de>
 *
 * @copyright Copyright (c) 2021, Konrad Abicht <hi@inspirito.de>
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

namespace Tests\Core\Data;

use Exception;
use OC\Core\Service\LoginFlowV2Service;
use OC\Core\Db\LoginFlowV2Mapper;
use OC\Core\Exception\LoginFlowV2NotFoundException;
use OC\Authentication\Token\IProvider;
use OC\Core\Data\LoginFlowV2Credentials;
use OC\Core\Db\LoginFlowV2;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use Test\TestCase;

/**
 * Unit tests for \OC\Core\Service\LoginFlowV2Service
 */
class LoginFlowV2ServiceUnitTest extends TestCase {
	/** @var \OCP\IConfig */
	private $config;

	/** @var \OCP\Security\ICrypto */
	private $crypto;

	/** @var \OCP\ILogger */
	private $logger;

	/** @var \OC\Core\Db\LoginFlowV2Mapper */
	private $mapper;

	/** @var \OCP\Security\ISecureRandom */
	private $secureRandom;

	/** @var \OC\Core\Service\LoginFlowV2Service */
	private $subjectUnderTest;

	/** @var \OCP\AppFramework\Utility\ITimeFactory */
	private $timeFactory;

	/** @var \OC\Authentication\Token\IProvider */
	private $tokenProvider;

	public function setUp(): void {
		parent::setUp();

		$this->setupSubjectUnderTest();
	}

	/**
	 * Setup subject under test with mocked constructor arguments.
	 *
	 * Code was moved to separate function to keep setUp function small and clear.
	 */
	private function setupSubjectUnderTest(): void {
		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()->getMock();

		$this->crypto = $this->getMockBuilder(ICrypto::class)
			->disableOriginalConstructor()->getMock();

		$this->mapper = $this->getMockBuilder(LoginFlowV2Mapper::class)
			->disableOriginalConstructor()->getMock();

		$this->logger = $this->getMockBuilder(ILogger::class)
			->disableOriginalConstructor()->getMock();

		$this->tokenProvider = $this->getMockBuilder(IProvider::class)
			->disableOriginalConstructor()->getMock();

		$this->secureRandom = $this->getMockBuilder(ISecureRandom::class)
			->disableOriginalConstructor()->getMock();

		$this->timeFactory = $this->getMockBuilder(ITimeFactory::class)
			->disableOriginalConstructor()->getMock();

		$this->subjectUnderTest = new LoginFlowV2Service(
			$this->mapper,
			$this->secureRandom,
			$this->timeFactory,
			$this->config,
			$this->crypto,
			$this->logger,
			$this->tokenProvider
		);
	}

	/**
	 *
	 */
	private function getOpenSSLEncryptedAndPrivateKey(string $appPassword): array {
		// Create the private and public key
		$res = openssl_pkey_new([
			'digest_alg' => 'md5', // take fast algorithm for testing purposes
			'private_key_bits' => 512,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		]);

		// Extract the private key from $res
		openssl_pkey_export($res, $privateKey);

		// Extract the public key from $res
		$publicKey = openssl_pkey_get_details($res);
		$publicKey = $publicKey['key'];

		// Encrypt the data to $encrypted using the public key
		openssl_public_encrypt($appPassword, $encrypted, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);

		return [$encrypted, $privateKey];
	}

	/*
	 * Tests for poll
	 */

	public function testPollApptokenCouldNotBeDecrypted() {
		$this->expectException(LoginFlowV2NotFoundException::class);
		$this->expectExceptionMessage('Apptoken could not be decrypted');

		/*
		 * Cannot be mocked, because functions like getLoginName are magic functions.
		 * To be able to set internal properties, we have to use the real class here.
		 */
		$loginFlowV2 = new LoginFlowV2();
		$loginFlowV2->setLoginName('test');
		$loginFlowV2->setServer('test');
		$loginFlowV2->setAppPassword('test');
		$loginFlowV2->setPrivateKey('test');

		$this->mapper->expects($this->once())
			->method('getByPollToken')
			->willReturn($loginFlowV2);

		$this->subjectUnderTest->poll('');
	}

	public function testPollInvalidToken() {
		$this->expectException(LoginFlowV2NotFoundException::class);
		$this->expectExceptionMessage('Invalid token');

		$this->mapper->expects($this->once())
			->method('getByPollToken')
			->willThrowException(new DoesNotExistException(''));

		$this->subjectUnderTest->poll('');
	}

	public function testPollTokenNotYetReady() {
		$this->expectException(LoginFlowV2NotFoundException::class);
		$this->expectExceptionMessage('Token not yet ready');

		$this->subjectUnderTest->poll('');
	}

	public function testPollRemoveDataFromDb() {
		list($encrypted, $privateKey) = $this->getOpenSSLEncryptedAndPrivateKey('test_pass');

		$this->crypto->expects($this->once())
			->method('decrypt')
			->willReturn($privateKey);

		/*
		 * Cannot be mocked, because functions like getLoginName are magic functions.
		 * To be able to set internal properties, we have to use the real class here.
		 */
		$loginFlowV2 = new LoginFlowV2();
		$loginFlowV2->setLoginName('test_login');
		$loginFlowV2->setServer('test_server');
		$loginFlowV2->setAppPassword(base64_encode($encrypted));
		$loginFlowV2->setPrivateKey($privateKey);

		$this->mapper->expects($this->once())
			->method('delete')
			->with($this->equalTo($loginFlowV2));

		$this->mapper->expects($this->once())
			->method('getByPollToken')
			->willReturn($loginFlowV2);

		$credentials = $this->subjectUnderTest->poll('');

		$this->assertTrue($credentials instanceof LoginFlowV2Credentials);
		$this->assertEquals(
			[
				'server' => 'test_server',
				'loginName' => 'test_login',
				'appPassword' => 'test_pass',
			],
			$credentials->jsonSerialize()
		);
	}

	/*
	 * Tests for getByLoginToken
	 */

	public function testGetByLoginToken() {
		$loginFlowV2 = new LoginFlowV2();
		$loginFlowV2->setLoginName('test_login');
		$loginFlowV2->setServer('test_server');
		$loginFlowV2->setAppPassword('test');

		$this->mapper->expects($this->once())
			->method('getByLoginToken')
			->willReturn($loginFlowV2);

		$result = $this->subjectUnderTest->getByLoginToken('test_token');

		$this->assertTrue($result instanceof LoginFlowV2);
		$this->assertEquals('test_server', $result->getServer());
		$this->assertEquals('test_login', $result->getLoginName());
		$this->assertEquals('test', $result->getAppPassword());
	}

	public function testGetByLoginTokenLoginTokenInvalid() {
		$this->expectException(LoginFlowV2NotFoundException::class);
		$this->expectExceptionMessage('Login token invalid');

		$this->mapper->expects($this->once())
			->method('getByLoginToken')
			->willThrowException(new DoesNotExistException(''));

		$this->subjectUnderTest->getByLoginToken('test_token');
	}

	/*
	 * Tests for startLoginFlow
	 */

	public function testStartLoginFlow() {
		$loginFlowV2 = new LoginFlowV2();

		$this->mapper->expects($this->once())
			->method('getByLoginToken')
			->willReturn($loginFlowV2);

		$this->mapper->expects($this->once())
			->method('update');

		$this->assertTrue($this->subjectUnderTest->startLoginFlow('test_token'));
	}

	public function testStartLoginFlowDoesNotExistException() {
		$this->mapper->expects($this->once())
			->method('getByLoginToken')
			->willThrowException(new DoesNotExistException(''));

		$this->assertFalse($this->subjectUnderTest->startLoginFlow('test_token'));
	}

	/**
	 * If an exception not of type DoesNotExistException is thrown,
	 * it is expected that it is not being handled by startLoginFlow.
	 */
	public function testStartLoginFlowException() {
		$this->expectException(Exception::class);

		$this->mapper->expects($this->once())
			->method('getByLoginToken')
			->willThrowException(new Exception(''));

		$this->subjectUnderTest->startLoginFlow('test_token');
	}
}

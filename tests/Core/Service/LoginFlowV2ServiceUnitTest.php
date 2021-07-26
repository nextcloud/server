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
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Core\Data\LoginFlowV2Credentials;
use OC\Core\Data\LoginFlowV2Tokens;
use OC\Core\Db\LoginFlowV2Mapper;
use OC\Core\Db\LoginFlowV2;
use OC\Core\Exception\LoginFlowV2NotFoundException;
use OC\Core\Service\LoginFlowV2Service;
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
	 * Generates for a given password required OpenSSL parts.
	 *
	 * @return array Array contains encrypted password, private key and public key.
	 */
	private function getOpenSSLEncryptedPublicAndPrivateKey(string $appPassword): array {
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

		return [$encrypted, $privateKey, $publicKey];
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
		[$encrypted, $privateKey] = $this->getOpenSSLEncryptedPublicAndPrivateKey('test_pass');

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

	/*
	 * Tests for flowDone
	 */

	public function testFlowDone() {
		[,, $publicKey] = $this->getOpenSSLEncryptedPublicAndPrivateKey('test_pass');

		$loginFlowV2 = new LoginFlowV2();
		$loginFlowV2->setPublicKey($publicKey);
		$loginFlowV2->setClientName('client_name');

		$this->mapper->expects($this->once())
			->method('getByLoginToken')
			->willReturn($loginFlowV2);

		$this->mapper->expects($this->once())
			->method('update');

		$this->secureRandom->expects($this->once())
			->method('generate')
			->with(72, ISecureRandom::CHAR_UPPER.ISecureRandom::CHAR_LOWER.ISecureRandom::CHAR_DIGITS)
			->willReturn('test_pass');

		// session token
		$sessionToken = $this->getMockBuilder(IToken::class)->disableOriginalConstructor()->getMock();
		$sessionToken->expects($this->once())
			->method('getLoginName')
			->willReturn('login_name');

		$this->tokenProvider->expects($this->once())
			->method('getPassword')
			->willReturn('test_pass');

		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->willReturn($sessionToken);

		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with(
				'test_pass',
				'user_id',
				'login_name',
				'test_pass',
				'client_name',
				IToken::PERMANENT_TOKEN,
				IToken::DO_NOT_REMEMBER
			);

		$result = $this->subjectUnderTest->flowDone(
			'login_token',
			'session_id',
			'server',
			'user_id'
		);
		$this->assertTrue($result);

		// app password is encrypted and must look like:
		// ZACZOOzxTpKz4+KXL5kZ/gCK0xvkaVi/8yzupAn6Ui6+5qCSKvfPKGgeDRKs0sivvSLzk/XSp811SZCZmH0Y3g==
		$this->assertRegExp('/[a-zA-Z\/0-9+=]+/', $loginFlowV2->getAppPassword());

		$this->assertEquals('server', $loginFlowV2->getServer());
	}

	public function testFlowDoneDoesNotExistException() {
		$this->mapper->expects($this->once())
			->method('getByLoginToken')
			->willThrowException(new DoesNotExistException(''));

		$result = $this->subjectUnderTest->flowDone(
			'login_token',
			'session_id',
			'server',
			'user_id'
		);
		$this->assertFalse($result);
	}

	public function testFlowDonePasswordlessTokenException() {
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->willThrowException(new InvalidTokenException(''));

		$result = $this->subjectUnderTest->flowDone(
			'login_token',
			'session_id',
			'server',
			'user_id'
		);
		$this->assertFalse($result);
	}

	/*
	 * Tests for createTokens
	 */

	public function testCreateTokens() {
		$this->config->expects($this->exactly(2))
			->method('getSystemValue')
			->willReturn($this->returnCallback(function ($key) {
				// Note: \OCP\IConfig::getSystemValue returns either an array or string.
				return 'openssl' == $key ? [] : '';
			}));

		$this->mapper->expects($this->once())
			->method('insert');

		$this->secureRandom->expects($this->exactly(2))
			->method('generate');

		$token = $this->subjectUnderTest->createTokens('user_agent');
		$this->assertTrue($token instanceof LoginFlowV2Tokens);
	}
}

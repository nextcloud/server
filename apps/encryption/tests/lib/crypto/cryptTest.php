<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
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


namespace OCA\Encryption\Tests\lib\Crypto;


use OCA\Encryption\Crypto\Crypt;
use Test\TestCase;

class cryptTest extends TestCase {


	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $userSession;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var  Crypt */
	private $crypt;

	public function setUp() {
		parent::setUp();

		$this->logger = $this->getMockBuilder('OCP\ILogger')
			->disableOriginalConstructor()
			->getMock();
		$this->logger->expects($this->any())
			->method('warning')
			->willReturn(true);
		$this->userSession = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();

		$this->crypt = new Crypt($this->logger, $this->userSession, $this->config);
	}

	/**
	 * test getOpenSSLConfig without any additional parameters
	 */
	public function testGetOpenSSLConfigBasic() {

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('openssl'), $this->equalTo([]))
			->willReturn(array());

		$result = self::invokePrivate($this->crypt, 'getOpenSSLConfig');
		$this->assertSame(1, count($result));
		$this->assertArrayHasKey('private_key_bits', $result);
		$this->assertSame(4096, $result['private_key_bits']);
	}

	/**
	 * test getOpenSSLConfig with additional parameters defined in config.php
	 */
	public function testGetOpenSSLConfig() {

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('openssl'), $this->equalTo([]))
			->willReturn(array('foo' => 'bar', 'private_key_bits' => 1028));

		$result = self::invokePrivate($this->crypt, 'getOpenSSLConfig');
		$this->assertSame(2, count($result));
		$this->assertArrayHasKey('private_key_bits', $result);
		$this->assertArrayHasKey('foo', $result);
		$this->assertSame(1028, $result['private_key_bits']);
		$this->assertSame('bar', $result['foo']);
	}


	/**
	 * test generateHeader with valid key formats
	 *
	 * @dataProvider dataTestGenerateHeader
	 */
	public function testGenerateHeader($keyFormat, $expected) {

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('cipher'), $this->equalTo('AES-256-CFB'))
			->willReturn('AES-128-CFB');

		if ($keyFormat) {
			$result = $this->crypt->generateHeader($keyFormat);
		} else {
			$result = $this->crypt->generateHeader();
		}

		$this->assertSame($expected, $result);
	}

	/**
	 * test generateHeader with invalid key format
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testGenerateHeaderInvalid() {
		$this->crypt->generateHeader('unknown');
	}

	public function dataTestGenerateHeader() {
		return [
			[null, 'HBEGIN:cipher:AES-128-CFB:keyFormat:hash:HEND'],
			['password', 'HBEGIN:cipher:AES-128-CFB:keyFormat:password:HEND'],
			['hash', 'HBEGIN:cipher:AES-128-CFB:keyFormat:hash:HEND']
		];
	}

	/**
	 * @dataProvider dataProviderGetCipher
	 * @param string $configValue
	 * @param string $expected
	 */
	public function testGetCipher($configValue, $expected) {

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('cipher'), $this->equalTo('AES-256-CFB'))
			->willReturn($configValue);

		$this->assertSame($expected,
			$this->crypt->getCipher()
		);

	}

	/**
	 * data provider for testGetCipher
	 *
	 * @return array
	 */
	public function dataProviderGetCipher() {
		return array(
			array('AES-128-CFB', 'AES-128-CFB'),
			array('AES-256-CFB', 'AES-256-CFB'),
			array('unknown', 'AES-256-CFB')
		);
	}

	/**
	 * test concatIV()
	 */
	public function testConcatIV() {

		$result = self::invokePrivate(
			$this->crypt,
			'concatIV',
			array('content', 'my_iv'));

		$this->assertSame('content00iv00my_iv',
			$result
		);
	}

	/**
	 * test splitIV()
	 */
	public function testSplitIV() {
		$data = 'encryptedContent00iv001234567890123456';
		$result = self::invokePrivate($this->crypt, 'splitIV', array($data));
		$this->assertTrue(is_array($result));
		$this->assertSame(2, count($result));
		$this->assertArrayHasKey('encrypted', $result);
		$this->assertArrayHasKey('iv', $result);
		$this->assertSame('encryptedContent', $result['encrypted']);
		$this->assertSame('1234567890123456', $result['iv']);
	}

	/**
	 * test addPadding()
	 */
	public function testAddPadding() {
		$result = self::invokePrivate($this->crypt, 'addPadding', array('data'));
		$this->assertSame('dataxx', $result);
	}

	/**
	 * test removePadding()
	 *
	 * @dataProvider dataProviderRemovePadding
	 * @param $data
	 * @param $expected
	 */
	public function testRemovePadding($data, $expected) {
		$result = self::invokePrivate($this->crypt, 'removePadding', array($data));
		$this->assertSame($expected, $result);
	}

	/**
	 * data provider for testRemovePadding
	 *
	 * @return array
	 */
	public function dataProviderRemovePadding()  {
		return array(
			array('dataxx', 'data'),
			array('data', false)
		);
	}

	/**
	 * test parseHeader()
	 */
	public function testParseHeader() {

		$header= 'HBEGIN:foo:bar:cipher:AES-256-CFB:HEND';
		$result = self::invokePrivate($this->crypt, 'parseHeader', array($header));

		$this->assertTrue(is_array($result));
		$this->assertSame(2, count($result));
		$this->assertArrayHasKey('foo', $result);
		$this->assertArrayHasKey('cipher', $result);
		$this->assertSame('bar', $result['foo']);
		$this->assertSame('AES-256-CFB', $result['cipher']);
	}

	/**
	 * test encrypt()
	 *
	 * @return string
	 */
	public function testEncrypt() {

		$decrypted = 'content';
		$password = 'password';
		$iv = self::invokePrivate($this->crypt, 'generateIv');

		$this->assertTrue(is_string($iv));
		$this->assertSame(16, strlen($iv));

		$result = self::invokePrivate($this->crypt, 'encrypt', array($decrypted, $iv, $password));

		$this->assertTrue(is_string($result));

		return array(
			'password' => $password,
			'iv' => $iv,
			'encrypted' => $result,
			'decrypted' => $decrypted);

	}

	/**
	 * test decrypt()
	 *
	 * @depends testEncrypt
	 */
	public function testDecrypt($data) {

		$result = self::invokePrivate(
			$this->crypt,
			'decrypt',
			array($data['encrypted'], $data['iv'], $data['password']));

		$this->assertSame($data['decrypted'], $result);

	}

	/**
	 * test return values of valid ciphers
	 *
	 * @dataProvider dataTestGetKeySize
	 */
	public function testGetKeySize($cipher, $expected) {
		$result = $this->invokePrivate($this->crypt, 'getKeySize', [$cipher]);
		$this->assertSame($expected, $result);
	}

	/**
	 * test exception if cipher is unknown
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetKeySizeFailure() {
		$this->invokePrivate($this->crypt, 'getKeySize', ['foo']);
	}

	public function dataTestGetKeySize() {
		return [
			['AES-256-CFB', 32],
			['AES-128-CFB', 16],
		];
	}

	/**
	 * @dataProvider dataTestDecryptPrivateKey
	 */
	public function testDecryptPrivateKey($header, $privateKey, $expectedCipher, $isValidKey, $expected) {
		/** @var \OCA\Encryption\Crypto\Crypt | \PHPUnit_Framework_MockObject_MockObject $crypt */
		$crypt = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->setConstructorArgs(
				[
					$this->logger,
					$this->userSession,
					$this->config
				]
			)
			->setMethods(
				[
					'parseHeader',
					'generatePasswordHash',
					'symmetricDecryptFileContent',
					'isValidPrivateKey'
				]
			)
			->getMock();

		$crypt->expects($this->once())->method('parseHeader')->willReturn($header);
		if (isset($header['keyFormat']) && $header['keyFormat'] === 'hash') {
			$crypt->expects($this->once())->method('generatePasswordHash')->willReturn('hash');
			$password = 'hash';
		} else {
			$crypt->expects($this->never())->method('generatePasswordHash');
			$password = 'password';
		}

		$crypt->expects($this->once())->method('symmetricDecryptFileContent')
			->with('privateKey', $password, $expectedCipher)->willReturn('key');
		$crypt->expects($this->once())->method('isValidPrivateKey')->willReturn($isValidKey);

		$result = $crypt->decryptPrivateKey($privateKey, 'password');

		$this->assertSame($expected, $result);
	}

	public function dataTestDecryptPrivateKey() {
		return [
			[['cipher' => 'AES-128-CFB', 'keyFormat' => 'password'], 'HBEGIN:HENDprivateKey', 'AES-128-CFB', true, 'key'],
			[['cipher' => 'AES-256-CFB', 'keyFormat' => 'password'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', true, 'key'],
			[['cipher' => 'AES-256-CFB', 'keyFormat' => 'password'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', false, false],
			[['cipher' => 'AES-256-CFB', 'keyFormat' => 'hash'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', true, 'key'],
			[['cipher' => 'AES-256-CFB'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', true, 'key'],
			[[], 'privateKey', 'AES-128-CFB', true, 'key'],
		];
	}

	public function testIsValidPrivateKey() {
		$res = openssl_pkey_new();
		openssl_pkey_export($res, $privateKey);

		// valid private key
		$this->assertTrue(
			$this->invokePrivate($this->crypt, 'isValidPrivateKey', [$privateKey])
		);

		// invalid private key
		$this->assertFalse(
			$this->invokePrivate($this->crypt, 'isValidPrivateKey', ['foo'])
		);
	}

}

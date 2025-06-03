<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Tests\Crypto;

use OCA\Encryption\Crypto\Crypt;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CryptTest extends TestCase {
	protected LoggerInterface&MockObject $logger;
	protected IUserSession&MockObject $userSession;
	protected IConfig&MockObject $config;
	protected IL10N&MockObject $l;

	protected Crypt $crypt;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->getMockBuilder(LoggerInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->logger->expects($this->any())
			->method('warning');
		$this->userSession = $this->getMockBuilder(IUserSession::class)
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->l = $this->createMock(IL10N::class);

		$this->crypt = new Crypt($this->logger, $this->userSession, $this->config, $this->l);
	}

	/**
	 * test getOpenSSLConfig without any additional parameters
	 */
	public function testGetOpenSSLConfigBasic(): void {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('openssl'), $this->equalTo([]))
			->willReturn([]);

		$result = self::invokePrivate($this->crypt, 'getOpenSSLConfig');
		$this->assertSame(1, count($result));
		$this->assertArrayHasKey('private_key_bits', $result);
		$this->assertSame(4096, $result['private_key_bits']);
	}

	/**
	 * test getOpenSSLConfig with additional parameters defined in config.php
	 */
	public function testGetOpenSSLConfig(): void {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('openssl'), $this->equalTo([]))
			->willReturn(['foo' => 'bar', 'private_key_bits' => 1028]);

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
	public function testGenerateHeader($keyFormat, $expected): void {
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with($this->equalTo('cipher'), $this->equalTo('AES-256-CTR'))
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
	 */
	public function testGenerateHeaderInvalid(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->crypt->generateHeader('unknown');
	}

	public static function dataTestGenerateHeader(): array {
		return [
			[null, 'HBEGIN:cipher:AES-128-CFB:keyFormat:hash2:encoding:binary:HEND'],
			['password', 'HBEGIN:cipher:AES-128-CFB:keyFormat:password:encoding:binary:HEND'],
			['hash', 'HBEGIN:cipher:AES-128-CFB:keyFormat:hash:encoding:binary:HEND']
		];
	}

	public function testGetCipherWithInvalidCipher(): void {
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with($this->equalTo('cipher'), $this->equalTo('AES-256-CTR'))
			->willReturn('Not-Existing-Cipher');
		$this->logger
			->expects($this->once())
			->method('warning')
			->with('Unsupported cipher (Not-Existing-Cipher) defined in config.php supported. Falling back to AES-256-CTR');

		$this->assertSame('AES-256-CTR', $this->crypt->getCipher());
	}

	/**
	 * @dataProvider dataProviderGetCipher
	 * @param string $configValue
	 * @param string $expected
	 */
	public function testGetCipher($configValue, $expected): void {
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with($this->equalTo('cipher'), $this->equalTo('AES-256-CTR'))
			->willReturn($configValue);

		$this->assertSame($expected,
			$this->crypt->getCipher()
		);
	}

	/**
	 * data provider for testGetCipher
	 */
	public static function dataProviderGetCipher(): array {
		return [
			['AES-128-CFB', 'AES-128-CFB'],
			['AES-256-CFB', 'AES-256-CFB'],
			['AES-128-CTR', 'AES-128-CTR'],
			['AES-256-CTR', 'AES-256-CTR'],

			['unknown', 'AES-256-CTR']
		];
	}

	/**
	 * test concatIV()
	 */
	public function testConcatIV(): void {
		$result = self::invokePrivate(
			$this->crypt,
			'concatIV',
			['content', 'my_iv']);

		$this->assertSame('content00iv00my_iv',
			$result
		);
	}

	/**
	 * @dataProvider dataTestSplitMetaData
	 */
	public function testSplitMetaData($data, $expected): void {
		$this->config->method('getSystemValueBool')
			->with('encryption_skip_signature_check', false)
			->willReturn(true);
		$result = self::invokePrivate($this->crypt, 'splitMetaData', [$data, 'AES-256-CFB']);
		$this->assertTrue(is_array($result));
		$this->assertSame(3, count($result));
		$this->assertArrayHasKey('encrypted', $result);
		$this->assertArrayHasKey('iv', $result);
		$this->assertArrayHasKey('signature', $result);
		$this->assertSame($expected['encrypted'], $result['encrypted']);
		$this->assertSame($expected['iv'], $result['iv']);
		$this->assertSame($expected['signature'], $result['signature']);
	}

	public static function dataTestSplitMetaData(): array {
		return [
			['encryptedContent00iv001234567890123456xx',
				['encrypted' => 'encryptedContent', 'iv' => '1234567890123456', 'signature' => false]],
			['encryptedContent00iv00123456789012345600sig00e1992521e437f6915f9173b190a512cfc38a00ac24502db44e0ba10c2bb0cc86xxx',
				['encrypted' => 'encryptedContent', 'iv' => '1234567890123456', 'signature' => 'e1992521e437f6915f9173b190a512cfc38a00ac24502db44e0ba10c2bb0cc86']],
		];
	}

	/**
	 * @dataProvider dataTestHasSignature
	 */
	public function testHasSignature($data, $expected): void {
		$this->config->method('getSystemValueBool')
			->with('encryption_skip_signature_check', false)
			->willReturn(true);
		$this->assertSame($expected,
			$this->invokePrivate($this->crypt, 'hasSignature', [$data, 'AES-256-CFB'])
		);
	}

	public static function dataTestHasSignature(): array {
		return [
			['encryptedContent00iv001234567890123456xx', false],
			['encryptedContent00iv00123456789012345600sig00e1992521e437f6915f9173b190a512cfc38a00ac24502db44e0ba10c2bb0cc86xxx', true]
		];
	}

	/**
	 * @dataProvider dataTestHasSignatureFail
	 */
	public function testHasSignatureFail($cipher): void {
		$this->expectException(GenericEncryptionException::class);

		$data = 'encryptedContent00iv001234567890123456xx';
		$this->invokePrivate($this->crypt, 'hasSignature', [$data, $cipher]);
	}

	public static function dataTestHasSignatureFail(): array {
		return [
			['AES-256-CTR'],
			['aes-256-ctr'],
			['AES-128-CTR'],
			['ctr-256-ctr']
		];
	}

	/**
	 * test addPadding()
	 */
	public function testAddPadding(): void {
		$result = self::invokePrivate($this->crypt, 'addPadding', ['data']);
		$this->assertSame('dataxxx', $result);
	}

	/**
	 * test removePadding()
	 *
	 * @dataProvider dataProviderRemovePadding
	 * @param $data
	 * @param $expected
	 */
	public function testRemovePadding($data, $expected): void {
		$result = self::invokePrivate($this->crypt, 'removePadding', [$data]);
		$this->assertSame($expected, $result);
	}

	/**
	 * data provider for testRemovePadding
	 */
	public static function dataProviderRemovePadding(): array {
		return [
			['dataxx', 'data'],
			['data', false]
		];
	}

	/**
	 * test parseHeader()
	 */
	public function testParseHeader(): void {
		$header = 'HBEGIN:foo:bar:cipher:AES-256-CFB:encoding:binary:HEND';
		$result = self::invokePrivate($this->crypt, 'parseHeader', [$header]);

		$this->assertTrue(is_array($result));
		$this->assertSame(3, count($result));
		$this->assertArrayHasKey('foo', $result);
		$this->assertArrayHasKey('cipher', $result);
		$this->assertArrayHasKey('encoding', $result);
		$this->assertSame('bar', $result['foo']);
		$this->assertSame('AES-256-CFB', $result['cipher']);
		$this->assertSame('binary', $result['encoding']);
	}

	/**
	 * test encrypt()
	 *
	 * @return string
	 */
	public function testEncrypt() {
		$decrypted = 'content';
		$password = 'password';
		$cipher = 'AES-256-CTR';
		$iv = self::invokePrivate($this->crypt, 'generateIv');

		$this->assertTrue(is_string($iv));
		$this->assertSame(16, strlen($iv));

		$result = self::invokePrivate($this->crypt, 'encrypt', [$decrypted, $iv, $password, $cipher]);

		$this->assertTrue(is_string($result));

		return [
			'password' => $password,
			'iv' => $iv,
			'cipher' => $cipher,
			'encrypted' => $result,
			'decrypted' => $decrypted];
	}

	/**
	 * test decrypt()
	 *
	 * @depends testEncrypt
	 */
	public function testDecrypt($data): void {
		$result = self::invokePrivate(
			$this->crypt,
			'decrypt',
			[$data['encrypted'], $data['iv'], $data['password'], $data['cipher'], true]);

		$this->assertSame($data['decrypted'], $result);
	}

	/**
	 * test return values of valid ciphers
	 *
	 * @dataProvider dataTestGetKeySize
	 */
	public function testGetKeySize($cipher, $expected): void {
		$result = $this->invokePrivate($this->crypt, 'getKeySize', [$cipher]);
		$this->assertSame($expected, $result);
	}

	/**
	 * test exception if cipher is unknown
	 *
	 */
	public function testGetKeySizeFailure(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->invokePrivate($this->crypt, 'getKeySize', ['foo']);
	}

	public static function dataTestGetKeySize(): array {
		return [
			['AES-256-CFB', 32],
			['AES-128-CFB', 16],
			['AES-256-CTR', 32],
			['AES-128-CTR', 16],
		];
	}

	/**
	 * @dataProvider dataTestDecryptPrivateKey
	 */
	public function testDecryptPrivateKey($header, $privateKey, $expectedCipher, $isValidKey, $expected): void {
		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['encryption.legacy_format_support', false, true],
				['encryption.use_legacy_base64_encoding', false, false],
			]);

		/** @var Crypt|\PHPUnit\Framework\MockObject\MockObject $crypt */
		$crypt = $this->getMockBuilder(Crypt::class)
			->setConstructorArgs([
				$this->logger,
				$this->userSession,
				$this->config,
				$this->l
			])
			->onlyMethods([
				'parseHeader',
				'generatePasswordHash',
				'symmetricDecryptFileContent',
				'isValidPrivateKey'
			])
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

	public static function dataTestDecryptPrivateKey(): array {
		return [
			[['cipher' => 'AES-128-CFB', 'keyFormat' => 'password'], 'HBEGIN:HENDprivateKey', 'AES-128-CFB', true, 'key'],
			[['cipher' => 'AES-256-CFB', 'keyFormat' => 'password'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', true, 'key'],
			[['cipher' => 'AES-256-CFB', 'keyFormat' => 'password'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', false, false],
			[['cipher' => 'AES-256-CFB', 'keyFormat' => 'hash'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', true, 'key'],
			[['cipher' => 'AES-256-CFB'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', true, 'key'],
			[[], 'privateKey', 'AES-128-CFB', true, 'key'],
		];
	}

	public function testIsValidPrivateKey(): void {
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

	public function testMultiKeyEncrypt(): void {
		$res = openssl_pkey_new();
		openssl_pkey_export($res, $privateKey);
		$publicKeyPem = openssl_pkey_get_details($res)['key'];
		$publicKey = openssl_pkey_get_public($publicKeyPem);

		$shareKeys = $this->crypt->multiKeyEncrypt('content', ['user1' => $publicKey]);
		$this->assertEquals(
			'content',
			$this->crypt->multiKeyDecrypt($shareKeys['user1'], $privateKey)
		);
	}
}

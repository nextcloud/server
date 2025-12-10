<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\IntegrityCheck;

use OC\Core\Command\Maintenance\Mimetype\GenerateMimetypeFileBuilder;
use OC\Files\Type\Detection;
use OC\IntegrityCheck\Checker;
use OC\IntegrityCheck\Helpers\EnvironmentHelper;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OC\Memcache\NullCache;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\ServerVersion;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use Test\TestCase;

class CheckerTest extends TestCase {
	/** @var ServerVersion|\PHPUnit\Framework\MockObject\MockObject */
	private $serverVersion;
	/** @var EnvironmentHelper|\PHPUnit\Framework\MockObject\MockObject */
	private $environmentHelper;
	/** @var FileAccessHelper|\PHPUnit\Framework\MockObject\MockObject */
	private $fileAccessHelper;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IAppConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $appConfig;
	/** @var ICacheFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $cacheFactory;
	/** @var IAppManager|\PHPUnit\Framework\MockObject\MockObject */
	private $appManager;
	/** @var \OC\Files\Type\Detection|\PHPUnit\Framework\MockObject\MockObject */
	private $mimeTypeDetector;

	private Checker $checker;

	protected function setUp(): void {
		parent::setUp();
		$this->serverVersion = $this->createMock(ServerVersion::class);
		$this->environmentHelper = $this->createMock(EnvironmentHelper::class);
		$this->fileAccessHelper = $this->createMock(FileAccessHelper::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->mimeTypeDetector = $this->createMock(Detection::class);

		$this->config->method('getAppValue')
			->willReturnArgument(2);

		$this->cacheFactory
			->expects($this->any())
			->method('createDistributed')
			->with('oc.integritycheck.checker')
			->willReturn(new NullCache());

		$this->checker = new Checker(
			$this->serverVersion,
			$this->environmentHelper,
			$this->fileAccessHelper,
			$this->config,
			$this->appConfig,
			$this->cacheFactory,
			$this->appManager,
			$this->mimeTypeDetector
		);
	}


	public function testWriteAppSignatureOfNotExistingApp(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Exception message');

		$this->fileAccessHelper
			->expects($this->once())
			->method('assertDirectoryExists')
			->with('NotExistingApp/appinfo')
			->willThrowException(new \Exception('Exception message'));
		$this->fileAccessHelper
			->expects($this->once())
			->method('is_writable')
			->with('NotExistingApp/appinfo')
			->willReturn(true);

		$keyBundle = file_get_contents(__DIR__ . '/../../data/integritycheck/SomeApp.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ . '/../../data/integritycheck/SomeApp.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeAppSignature('NotExistingApp', $x509, $rsa);
	}


	public function testWriteAppSignatureWrongPermissions(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessageMatches('/[a-zA-Z\\/_-]+ is not writable/');

		$this->fileAccessHelper
			->expects($this->once())
			->method('file_put_contents')
			->willThrowException(new \Exception('Exception message'));

		$keyBundle = file_get_contents(__DIR__ . '/../../data/integritycheck/SomeApp.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ . '/../../data/integritycheck/SomeApp.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeAppSignature(\OC::$SERVERROOT . '/tests/data/integritycheck/app/', $x509, $rsa);
	}

	public function testWriteAppSignature(): void {
		$this->fileAccessHelper
			->expects($this->once())
			->method('file_put_contents')
			->with(
				$this->equalTo(\OC::$SERVERROOT . '/tests/data/integritycheck/app//appinfo/signature.json'),
				$this->callback(function ($signature) {
					$actualArray = json_decode($signature, true);
					$this->assertIsArray($actualArray, 'signature.json should decode to an array');

					// New canonical format must include format_version = 2 and hashes as an array
					$this->assertArrayHasKey('format_version', $actualArray);
					$this->assertSame(2, $actualArray['format_version']);

					$this->assertArrayHasKey('hashes', $actualArray);
					$this->assertIsArray($actualArray['hashes']);

					// Expect signature and certificate to be present
					$this->assertArrayHasKey('signature', $actualArray);
					$this->assertArrayHasKey('certificate', $actualArray);

					// Verify expected files are present in the new list-of-entries format
					$files = array_column($actualArray['hashes'], 'file');
					$this->assertContains('AnotherFile.txt', $files);
					$this->assertContains('subfolder/file.txt', $files);

					// Validate hash format
					foreach ($actualArray['hashes'] as $entry) {
						$this->assertArrayHasKey('file', $entry);
						$this->assertArrayHasKey('hash', $entry);
						$this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $entry['hash']);
					}

					return true;
				})
			);

		$keyBundle = file_get_contents(__DIR__ . '/../../data/integritycheck/SomeApp.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ . '/../../data/integritycheck/SomeApp.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeAppSignature(\OC::$SERVERROOT . '/tests/data/integritycheck/app/', $x509, $rsa);
	}

	public function testVerifyAppSignatureWithoutSignatureData(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		// Ensure getServerRoot is defined since verify() will read root.crt relative to it
		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/');

		$result = $this->checker->verifyAppSignature('SomeApp');

		$this->assertIsArray($result);
		$this->assertArrayHasKey('EXCEPTION', $result);
		$this->assertIsString($result['EXCEPTION']['class']);
		// message should mention 'signature' (matches either "Could not read signature.json" or "malformed")
		$this->assertMatchesRegularExpression('/signature/i', $result['EXCEPTION']['message']);
	}

	public function testVerifyAppSignatureWithValidSignatureData(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->appManager
			->expects($this->once())
			->method('getAppPath')
			->with('SomeApp')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');

		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odF[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDM[...]"
}';

		// Make verify() find the root CA via environmentHelper
		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');

		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//appinfo/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$this->assertSame([], $this->checker->verifyAppSignature('SomeApp'));
	}

	public function testVerifyAppSignatureWithTamperedSignatureData(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->appManager
			->expects($this->once())
			->method('getAppPath')
			->with('SomeApp')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "tampered",
        "subfolder\/file.txt": "tampered"
    },
    "signature": "EL49UaSeyMAqyMtqId+tgOhhwgOevPZsRLX4j2blnybAB6fN07z0936JqZV7+eMPiE30Idx+UCY6rCFN531Kqe9vAOCdgtHUSOjjKyKc+lvULESlMb6YQcrZrvDlEMMjzjH49ewG7Ai8sNN6HrRUd9U8ws+ewSkW2DOOBItj\/21RBnkr[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEwTCCAqmgAwIBAgIUWv0iujufs5lUr0svCf\/qTQvoyKAwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIyNDk[...]"
}';
		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');

		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//appinfo/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$result = $this->checker->verifyAppSignature('SomeApp');
		$this->assertIsArray($result);
		$this->assertArrayHasKey('EXCEPTION', $result);
		$this->assertSame('OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', $result['EXCEPTION']['class']);
		$this->assertMatchesRegularExpression('/signature.*ver/i', $result['EXCEPTION']['message']);
	}

	public function testVerifyAppSignatureWithTamperedFiles(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->appManager
			->expects($this->once())
			->method('getAppPath')
			->with('SomeApp')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odF[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDM[...]"
}';
		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/');

		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//appinfo/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$expected = [
			'INVALID_HASH' => [
				'AnotherFile.txt' => [
					'expected' => '1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112',
					'current' => '7322348ba269c6d5522efe02f424fa3a0da319a7cd9c33142a5afe32a2d9af2da3a411f086fcfc96ff4301ea566f481dba0960c2abeef3594c4d930462f6584c',
				],
			],
			'FILE_MISSING' => [
				'subfolder/file.txt' => [
					'expected' => '410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b',
					'current' => '',
				],
			],
			'EXTRA_FILE' => [
				'UnecessaryFile' => [
					'expected' => '',
					'current' => 'cf83e1357eefb8bdf1542850d66d8007d620e4050b5715dc83f4a921d36ce9ce47d0d13c5d85f2b0ff8318d2877eec2f63b931bd47417a81a538327af927da3e',
				],
			],

		];
		$this->assertSame($expected, $this->checker->verifyAppSignature('SomeApp'));
	}

	public function testVerifyAppSignatureWithTamperedFilesAndAlternatePath(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->appManager
			->expects($this->never())
			->method('getAppPath')
			->with('SomeApp');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odF[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDM[...]"
}';
		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/');

		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//appinfo/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$expected = [
			'INVALID_HASH' => [
				'AnotherFile.txt' => [
					'expected' => '1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112',
					'current' => '7322348ba269c6d5522efe02f424fa3a0da319a7cd9c33142a5afe32a2d9af2da3a411f086fcfc96ff4301ea566f481dba0960c2abeef3594c4d930462f6584c',
				],
			],
			'FILE_MISSING' => [
				'subfolder/file.txt' => [
					'expected' => '410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b',
					'current' => '',
				],
			],
			'EXTRA_FILE' => [
				'UnecessaryFile' => [
					'expected' => '',
					'current' => 'cf83e1357eefb8bdf1542850d66d8007d620e4050b5715dc83f4a921d36ce9ce47d0d13c5d85f2b0ff8318d2877eec2f63b931bd47417a81a538327af927da3e',
				],
			],

		];
		$this->assertSame($expected, $this->checker->verifyAppSignature('SomeApp', \OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/'));
	}

	public function testVerifyAppWithDifferentScope(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->appManager
			->expects($this->once())
			->method('getAppPath')
			->with('SomeApp')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "eXesvDm3pkek12xSwMG10y9suRES79Nye3jYNe5KYq1tTUPqRRNgxmMGAfcUro0zpLeAr2YgHeSMWtglblGOW7pmwGVPZ0O1Y4r1fE6jnep0kW+35PLIaqCorIOnCAtSzDNKBhwd1ow3zW2wC0DFouuEkIO8u5Fw28g8E8dp8zEk1xMbl[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIExjCCAq6gAwIBAgIUHSJjhJqMwr+3TkoiQFg4SVVYQ1gwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIzMjc1[...]"
}';
		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/');

		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//appinfo/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$result = $this->checker->verifyAppSignature('SomeApp');
		$this->assertIsArray($result);
		$this->assertArrayHasKey('EXCEPTION', $result);
		$this->assertSame('OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', $result['EXCEPTION']['class']);
		$this->assertMatchesRegularExpression('/Certificate is not valid for required scope/i', $result['EXCEPTION']['message']);
		$this->assertMatchesRegularExpression('/Requested: SomeApp/i', $result['EXCEPTION']['message']);
	}

	public function testVerifyAppWithDifferentScopeAndAlwaysTrustedCore(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->appManager
			->expects($this->once())
			->method('getAppPath')
			->with('SomeApp')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odF[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDM[...]"
}';
		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');

		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//appinfo/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$this->assertSame([], $this->checker->verifyAppSignature('SomeApp'));
	}


	public function testWriteCoreSignatureWithException(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Exception message');

		$this->fileAccessHelper
			->expects($this->once())
			->method('assertDirectoryExists')
			->willThrowException(new \Exception('Exception message'));
		$this->fileAccessHelper
			->expects($this->once())
			->method('is_writable')
			->with(__DIR__ . '/core')
			->willReturn(true);

		$keyBundle = file_get_contents(__DIR__ . '/../../data/integritycheck/SomeApp.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ . '/../../data/integritycheck/SomeApp.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeCoreSignature($x509, $rsa, __DIR__);
	}


	public function testWriteCoreSignatureWrongPermissions(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessageMatches('/[a-zA-Z\\/_-]+ is not writable/');

		$this->fileAccessHelper
			->expects($this->once())
			->method('assertDirectoryExists')
			->willThrowException(new \Exception('Exception message'));
		$this->fileAccessHelper
			->expects($this->once())
			->method('is_writable')
			->with(__DIR__ . '/core')
			->willReturn(false);

		$keyBundle = file_get_contents(__DIR__ . '/../../data/integritycheck/SomeApp.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ . '/../../data/integritycheck/SomeApp.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeCoreSignature($x509, $rsa, __DIR__);
	}

	public function testWriteCoreSignature(): void {
		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');
		$this->fileAccessHelper
			->expects($this->once())
			->method('file_put_contents')
			->with(
				\OC::$SERVERROOT . '/tests/data/integritycheck/app//core/signature.json',
				$this->callback(function ($signature) {
					$actualArray = json_decode($signature, true);
					$this->assertIsArray($actualArray, 'signature.json should decode to an array');

					$this->assertArrayHasKey('format_version', $actualArray);
					$this->assertSame(2, $actualArray['format_version']);

					$this->assertArrayHasKey('hashes', $actualArray);
					$this->assertIsArray($actualArray['hashes']);

					$this->assertArrayHasKey('signature', $actualArray);
					$this->assertArrayHasKey('certificate', $actualArray);

					$files = array_column($actualArray['hashes'], 'file');
					$this->assertContains('AnotherFile.txt', $files);
					$this->assertContains('subfolder/file.txt', $files);

					return true;
				})
			);

		$keyBundle = file_get_contents(__DIR__ . '/../../data/integritycheck/core.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ . '/../../data/integritycheck/core.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeCoreSignature($x509, $rsa, \OC::$SERVERROOT . '/tests/data/integritycheck/app/');
	}

	public function testWriteCoreSignatureWithUnmodifiedHtaccess(): void {
		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessUnmodified/');
		$this->fileAccessHelper
			->expects($this->once())
			->method('file_put_contents')
			->with(
				\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessUnmodified//core/signature.json',
				$this->callback(function ($signature) {
					$actualArray = json_decode($signature, true);
					$this->assertIsArray($actualArray);

					$this->assertArrayHasKey('format_version', $actualArray);
					$this->assertSame(2, $actualArray['format_version']);

					$this->assertArrayHasKey('hashes', $actualArray);
					$this->assertIsArray($actualArray['hashes']);
					$files = array_column($actualArray['hashes'], 'file');
					$this->assertContains('.htaccess', $files);

					return true;
				})
			);

		$keyBundle = file_get_contents(__DIR__ . '/../../data/integritycheck/core.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ . '/../../data/integritycheck/core.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeCoreSignature($x509, $rsa, \OC::$SERVERROOT . '/tests/data/integritycheck/htaccessUnmodified/');
	}

	public function testWriteCoreSignatureWithInvalidModifiedHtaccess(): void {
		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithInvalidModifiedContent/');
		$this->fileAccessHelper
			->expects($this->once())
			->method('file_put_contents')
			->with(
				\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithInvalidModifiedContent//core/signature.json',
				$this->callback(function ($signature) {
					$actualArray = json_decode($signature, true);
					$this->assertIsArray($actualArray);
					$this->assertArrayHasKey('format_version', $actualArray);
					$this->assertSame(2, $actualArray['format_version']);
					$this->assertArrayHasKey('hashes', $actualArray);
					$this->assertIsArray($actualArray['hashes']);

					return true;
				})
			);

		$keyBundle = file_get_contents(__DIR__ . '/../../data/integritycheck/core.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ . '/../../data/integritycheck/core.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeCoreSignature($x509, $rsa, \OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithInvalidModifiedContent/');
	}

	public function testWriteCoreSignatureWithValidModifiedHtaccess(): void {
		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent');
		$this->fileAccessHelper
			->expects($this->once())
			->method('file_put_contents')
			->with(
				\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent/core/signature.json',
				$this->callback(function ($signature) {
					$actualArray = json_decode($signature, true);
					$this->assertIsArray($actualArray);
					$this->assertArrayHasKey('format_version', $actualArray);
					$this->assertSame(2, $actualArray['format_version']);
					$this->assertArrayHasKey('hashes', $actualArray);
					$this->assertIsArray($actualArray['hashes']);
					$files = array_column($actualArray['hashes'], 'file');
					$this->assertContains('.htaccess', $files);

					return true;
				})
			);

		$keyBundle = file_get_contents(__DIR__ . '/../../data/integritycheck/core.crt');
		$rsaPrivateKey = file_get_contents(__DIR__ . '/../../data/integritycheck/core.key');
		$rsa = new RSA();
		$rsa->loadKey($rsaPrivateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$this->checker->writeCoreSignature($x509, $rsa, \OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent');
	}

	public function testVerifyCoreSignatureWithoutSignatureData(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');

		$result = $this->checker->verifyCoreSignature();

		$this->assertIsArray($result);
		$this->assertArrayHasKey('EXCEPTION', $result);
		$this->assertMatchesRegularExpression('/signature/i', $result['EXCEPTION']['message']);
	}

	public function testVerifyCoreSignatureWithValidSignatureData(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odF[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDM[...]"
}';
		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//core/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$this->assertSame([], $this->checker->verifyCoreSignature());
	}

	public function testVerifyCoreSignatureWithValidModifiedHtaccessSignatureData(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent');
		$signatureDataFile = '{
    "hashes": {
        ".htaccess": "b1a6a9fbb85417f3f461e654f1a8ae56a131fe54e4257b2b8b7ba6b3fedd55b83c0df20550cd6c52bd3a96d148a5a3c4ea24d99dca5d45a644491e56ad99df8e",
        "subfolder\/.htaccess": "2c57b1e25050e11dc3ae975832f378c452159f7b69f818e47eeeafadd6ba568517461dcb4d843b90b906cd7c89d161bc1b89dff8e3ae0eb6f5088508c47befd1"
    },
    "signature": "nkCCG4hEtRyIo7rxnBBTCYtb4aIoCZA/bgbi8OrsKA9ZcZBEWqpSjWMvl9K88e+Ci/HIynv3Y/JdsN4OABRnNtyTMgsVuzqqK+mYYTFlzmRBmCNJjHSjVgfxQ6vhlYGwTGJWuhxFY5sv/dolx2G3TP7fJT71XdWI/wPkoCoQpDCx4ciFH[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDM[...]"
}';
		$this->fileAccessHelper
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent/core/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/htaccessWithValidModifiedContent/resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$this->assertSame([], $this->checker->verifyCoreSignature());
	}

	/**
	 * See inline instruction on how to update the test assets when changing mimetypealiases.dist.json
	 */
	public function testVerifyCoreSignatureWithModifiedMimetypelistSignatureData(): void {
		$shippedMimetypeAliases = (array)json_decode(file_get_contents(\OC::$SERVERROOT . '/resources/config/mimetypealiases.dist.json'));
		$shippedMimetypeNames = (array)json_decode(file_get_contents(\OC::$SERVERROOT . '/resources/config/mimetypenames.dist.json'));
		$allAliases = array_merge($shippedMimetypeAliases, ['my-custom/mimetype' => 'custom']);
		$allMimetypeNames = array_merge($shippedMimetypeNames, ['my-custom/mimetype' => 'Custom Document']);

		$this->mimeTypeDetector
			->method('getOnlyDefaultAliases')
			->willReturn($shippedMimetypeAliases);

		$this->mimeTypeDetector
			->method('getAllAliases')
			->willReturn($allAliases);

		$this->mimeTypeDetector
			->method('getAllNamings')
			->willReturn($allMimetypeNames);

		$oldMimetypeList = new GenerateMimetypeFileBuilder();
		$all = $this->mimeTypeDetector->getAllAliases();
		$namings = $this->mimeTypeDetector->getAllNamings();
		$newFile = $oldMimetypeList->generateFile($all, $namings);

		// When updating the mimetype list the test assets need to be updated as well
		// 1. Update core/js/mimetypelist.js with the new generated js by running the test with the next line uncommented:
		// file_put_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/mimetypeListModified/core/js/mimetypelist.js', $newFile);
		// 2. Update signature.json using the following occ command:
		// occ integrity:sign-core --privateKey=./tests/data/integritycheck/core.key --certificate=./tests/data/integritycheck/core.crt --path=./tests/data/integritycheck/mimetypeListModified
		self::assertEquals($newFile, file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/mimetypeListModified/core/js/mimetypelist.js'));

		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/mimetypeListModified');

		$signatureDataFile = file_get_contents(__DIR__ . '/../../data/integritycheck/mimetypeListModified/core/signature.json');
		$this->fileAccessHelper
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/mimetypeListModified/core/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/mimetypeListModified/resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$this->assertSame([], $this->checker->verifyCoreSignature());
	}

	public function testVerifyCoreSignatureWithValidSignatureDataAndNotAlphabeticOrder(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odF[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDM[...]"
}';
		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//core/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$this->assertSame([], $this->checker->verifyCoreSignature());
	}

	public function testVerifyCoreSignatureWithTamperedSignatureData(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "tampered",
        "subfolder\/file.txt": "tampered"
    },
    "signature": "eXesvDm3pkek12xSwMG10y9suRES79Nye3jYNe5KYq1tTUPqRRNgxmMGAfcUro0zpLeAr2YgHeSMWtglblGOW7pmwGVPZ0O1Y4r1fE6jnep0kW+35PLIaqCorIOnCAtSzDNKBhwd1ow3zW2wC0DFouuEkIO8u5Fw28g8E8dp8zEk1xMbl[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDM[...]"
}';
		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//core/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$result = $this->checker->verifyCoreSignature();
		$this->assertIsArray($result);
		$this->assertArrayHasKey('EXCEPTION', $result);
		$this->assertSame('OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', $result['EXCEPTION']['class']);
		$this->assertMatchesRegularExpression('/signature.*ver/i', $result['EXCEPTION']['message']);
	}

	public function testVerifyCoreSignatureWithTamperedFiles(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData/');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "dYoohBaWIFR\/To1FXEbMQB5apUhVYlEauBGSPo12nq84wxWkBx2EM3KDRgkB5Sub2tr0CgmAc2EVjPhKIEzAam26cyUb48bJziz1V6wvW7z4GZAfaJpzLkyHdSfV5117VSf5w1rDcAeZDXfGUaaNEJPWytaF4ZIxVge7f3NGshHy4odF[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUc\/0FxYrsgSs9rDxp03EJmbjN0NwwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIxMDM[...]"
}';
		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//core/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/appWithInvalidData//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$expected = [
			'INVALID_HASH' => [
				'AnotherFile.txt' => [
					'expected' => '1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112',
					'current' => '7322348ba269c6d5522efe02f424fa3a0da319a7cd9c33142a5afe32a2d9af2da3a411f086fcfc96ff4301ea566f481dba0960c2abeef3594c4d930462f6584c',
				],
			],
			'FILE_MISSING' => [
				'subfolder/file.txt' => [
					'expected' => '410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b',
					'current' => '',
				],
			],
			'EXTRA_FILE' => [
				'UnecessaryFile' => [
					'expected' => '',
					'current' => 'cf83e1357eefb8bdf1542850d66d8007d620e4050b5715dc83f4a921d36ce9ce47d0d13c5d85f2b0ff8318d2877eec2f63b931bd47417a81a538327af927da3e',
				],
			],

		];
		$this->assertSame($expected, $this->checker->verifyCoreSignature());
	}

	public function testVerifyCoreWithInvalidCertificate(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "eXesvDm3pkek12xSwMG10y9suRES79Nye3jYNe5KYq1tTUPqRRNgxmMGAfcUro0zpLeAr2YgHeSMWtglblGOW7pmwGVPZ0O1Y4r1fE6jnep0kW+35PLIaqCorIOnCAtSzDNKBhwd1ow3zW2wC0DFouuEkIO8u5Fw28g8E8dp8zEk1xMbl[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEvjCCAqagAwIBAgIUPYoweUxCPqbDW4ntuh7QvgyqSrgwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIyNDIw[...]"
}';
		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//core/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$result = $this->checker->verifyCoreSignature();
		$this->assertIsArray($result);
		$this->assertArrayHasKey('EXCEPTION', $result);
		$this->assertSame('OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', $result['EXCEPTION']['class']);
		$this->assertMatchesRegularExpression('/Certificate is not valid/i', $result['EXCEPTION']['message']);
	}

	public function testVerifyCoreWithDifferentScope(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->environmentHelper
			->expects($this->any())
			->method('getServerRoot')
			->willReturn(\OC::$SERVERROOT . '/tests/data/integritycheck/app/');
		$signatureDataFile = '{
    "hashes": {
        "AnotherFile.txt": "1570ca9420e37629de4328f48c51da29840ddeaa03ae733da4bf1d854b8364f594aac560601270f9e1797ed4cd57c1aea87bf44cf4245295c94f2e935a2f0112",
        "subfolder\/file.txt": "410738545fb623c0a5c8a71f561e48ea69e3ada0981a455e920a5ae9bf17c6831ae654df324f9328ff8453de179276ae51931cca0fa71fe8ccde6c083ca0574b"
    },
    "signature": "EL49UaSeyMAqyMtqId+tgOhhwgOevPZsRLX4j2blnybAB6fN07z0936JqZV7+eMPiE30Idx+UCY6rCFN531Kqe9vAOCdgtHUSOjjKyKc+lvULESlMb6YQcrZrvDlEMMjzjH49ewG7Ai8sNN6HrRUd9U8ws+ewSkW2DOOBItj\/21RBnkr[...]",
    "certificate": "-----BEGIN CERTIFICATE-----\r\nMIIEwTCCAqmgAwIBAgIUWv0iujufs5lUr0svCf\/qTQvoyKAwDQYJKoZIhvcNAQEF\r\nBQAwIzEhMB8GA1UECgwYb3duQ2xvdWQgQ29kZSBTaWduaW5nIENBMB4XDTE1MTEw\r\nMzIyNDk[...]"
}';
		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_get_contents')
			->willReturnMap([
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//core/signature.json', $signatureDataFile],
				[\OC::$SERVERROOT . '/tests/data/integritycheck/app//resources/codesigning/root.crt', file_get_contents(__DIR__ . '/../../data/integritycheck/root.crt')],
			]);

		$result = $this->checker->verifyCoreSignature();
		$this->assertIsArray($result);
		$this->assertArrayHasKey('EXCEPTION', $result);
		$this->assertSame('OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', $result['EXCEPTION']['class']);
		$this->assertMatchesRegularExpression('/Certificate is not valid for required scope/i', $result['EXCEPTION']['message']);
	}

	public function testRunInstanceVerification(): void {
		$this->checker = $this->getMockBuilder('\OC\IntegrityCheck\Checker')
			->setConstructorArgs([
				$this->serverVersion,
				$this->environmentHelper,
				$this->fileAccessHelper,
				$this->config,
				$this->appConfig,
				$this->cacheFactory,
				$this->appManager,
				$this->mimeTypeDetector,
			])
			->onlyMethods([
				'verifyCoreSignature',
				'verifyAppSignature',
			])
			->getMock();

		$this->checker
			->expects($this->once())
			->method('verifyCoreSignature');
		$this->appManager
			->expects($this->once())
			->method('getAllAppsInAppsFolders')
			->willReturn([
				'files',
				'calendar',
				'contacts',
				'dav',
			]);
		$this->appManager
			->expects($this->exactly(4))
			->method('isShipped')
			->willReturnMap([
				['files', true],
				['calendar', false],
				['contacts', false],
				['dav', true],
			]);

		$calls = [
			'files',
			'calendar',
			'dav',
		];
		$this->checker
			->expects($this->exactly(3))
			->method('verifyAppSignature')
			->willReturnCallback(function ($app) use (&$calls) {
				$expected = array_shift($calls);
				$this->assertSame($expected, $app);
				return [];
			});
		$this->appManager
			->expects($this->exactly(2))
			->method('getAppPath')
			->willReturnMap([
				['calendar', '/apps/calendar'],
				['contacts', '/apps/contacts'],
			]);
		$this->fileAccessHelper
			->expects($this->exactly(2))
			->method('file_exists')
			->willReturnMap([
				['/apps/calendar/appinfo/signature.json', true],
				['/apps/contacts/appinfo/signature.json', false],
			]);
		$this->appConfig
			->expects($this->once())
			->method('deleteKey')
			->with('core', 'oc.integritycheck.checker');

		$this->checker->runInstanceVerification();
	}

	public function testVerifyAppSignatureWithoutSignatureDataAndCodeCheckerDisabled(): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn('stable');
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(true);

		$expected = [];
		$this->assertSame($expected, $this->checker->verifyAppSignature('SomeApp'));
	}

	public static function channelDataProvider(): array {
		return [
			['stable', true],
			['git', false],
		];
	}

	/**
	 * @param string $channel
	 * @param bool $isCodeSigningEnforced
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('channelDataProvider')]
	public function testIsCodeCheckEnforced($channel, $isCodeSigningEnforced): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn($channel);
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(false);

		$this->assertSame($isCodeSigningEnforced, $this->checker->isCodeCheckEnforced());
	}

	/**
	 * @param string $channel
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('channelDataProvider')]
	public function testIsCodeCheckEnforcedWithDisabledConfigSwitch($channel): void {
		$this->serverVersion
			->expects($this->once())
			->method('getChannel')
			->willReturn($channel);
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('integrity.check.disabled', false)
			->willReturn(true);

		$this->assertFalse(self::invokePrivate($this->checker, 'isCodeCheckEnforced'));
	}
}

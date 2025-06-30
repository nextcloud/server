<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security;

use OC\Files\Filesystem;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OC\Security\Certificate;
use OC\Security\CertificateManager;
use OCP\Files\InvalidPathException;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class CertificateManagerTest
 *
 * @group DB
 */
class CertificateManagerTest extends \Test\TestCase {
	use \Test\Traits\UserTrait;
	use \Test\Traits\MountProviderTrait;

	private CertificateManager $certificateManager;
	private string $username;
	private ISecureRandom&MockObject $random;

	protected function setUp(): void {
		parent::setUp();

		$this->username = $this->getUniqueID('', 20);
		$this->createUser($this->username, '');

		$storage = new Temporary();
		$this->registerMount($this->username, $storage, '/' . $this->username . '/');

		\OC_Util::tearDownFS();
		\OC_User::setUserId($this->username);
		Filesystem::tearDown();
		\OC_Util::setupFS($this->username);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->any())->method('getSystemValueBool')
			->with('installed', false)->willReturn(true);

		$this->random = $this->createMock(ISecureRandom::class);
		$this->random->method('generate')
			->willReturn('random');

		$this->certificateManager = new CertificateManager(
			new View(),
			$config,
			$this->createMock(LoggerInterface::class),
			$this->random
		);
	}

	protected function tearDown(): void {
		$user = Server::get(IUserManager::class)->get($this->username);
		if ($user !== null) {
			$user->delete();
		}
		parent::tearDown();
	}

	protected function assertEqualsArrays($expected, $actual) {
		sort($expected);
		sort($actual);

		$this->assertEquals($expected, $actual);
	}

	public function testListCertificates(): void {
		// Test empty certificate bundle
		$this->assertSame([], $this->certificateManager->listCertificates());

		// Add some certificates
		$this->certificateManager->addCertificate(file_get_contents(__DIR__ . '/../../data/certificates/goodCertificate.crt'), 'GoodCertificate');
		$certificateStore = [];
		$certificateStore[] = new Certificate(file_get_contents(__DIR__ . '/../../data/certificates/goodCertificate.crt'), 'GoodCertificate');
		$this->assertEqualsArrays($certificateStore, $this->certificateManager->listCertificates());

		// Add another certificates
		$this->certificateManager->addCertificate(file_get_contents(__DIR__ . '/../../data/certificates/expiredCertificate.crt'), 'ExpiredCertificate');
		$certificateStore[] = new Certificate(file_get_contents(__DIR__ . '/../../data/certificates/expiredCertificate.crt'), 'ExpiredCertificate');
		$this->assertEqualsArrays($certificateStore, $this->certificateManager->listCertificates());
	}


	public function testAddInvalidCertificate(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Certificate could not get parsed.');

		$this->certificateManager->addCertificate('InvalidCertificate', 'invalidCertificate');
	}

	public static function dangerousFileProvider(): array {
		return [
			['.htaccess'],
			['../../foo.txt'],
			['..\..\foo.txt'],
		];
	}

	/**
	 * @dataProvider dangerousFileProvider
	 * @param string $filename
	 */
	public function testAddDangerousFile($filename): void {
		$this->expectException(InvalidPathException::class);
		$this->certificateManager->addCertificate(file_get_contents(__DIR__ . '/../../data/certificates/expiredCertificate.crt'), $filename);
	}

	public function testRemoveDangerousFile(): void {
		$this->assertFalse($this->certificateManager->removeCertificate('../../foo.txt'));
	}

	public function testRemoveExistingFile(): void {
		$this->certificateManager->addCertificate(file_get_contents(__DIR__ . '/../../data/certificates/goodCertificate.crt'), 'GoodCertificate');
		$this->assertTrue($this->certificateManager->removeCertificate('GoodCertificate'));
	}

	public function testGetCertificateBundle(): void {
		$this->assertSame('/files_external/rootcerts.crt', $this->certificateManager->getCertificateBundle());
	}

	/**
	 * @dataProvider dataTestNeedRebundling
	 *
	 * @param int $CaBundleMtime
	 * @param int $targetBundleMtime
	 * @param int $targetBundleExists
	 * @param bool $expected
	 */
	public function testNeedRebundling($CaBundleMtime,
		$targetBundleMtime,
		$targetBundleExists,
		$expected,
	): void {
		$view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()->getMock();
		$config = $this->createMock(IConfig::class);

		/** @var CertificateManager | \PHPUnit\Framework\MockObject\MockObject $certificateManager */
		$certificateManager = $this->getMockBuilder('OC\Security\CertificateManager')
			->setConstructorArgs([$view, $config, $this->createMock(LoggerInterface::class), $this->random])
			->onlyMethods(['getFilemtimeOfCaBundle', 'getCertificateBundle'])
			->getMock();

		$certificateManager->expects($this->any())->method('getFilemtimeOfCaBundle')
			->willReturn($CaBundleMtime);

		$certificateManager->expects($this->once())->method('getCertificateBundle')
			->willReturn('targetBundlePath');

		$view->expects($this->any())->method('file_exists')
			->with('targetBundlePath')
			->willReturn($targetBundleExists);


		$view->expects($this->any())->method('filemtime')
			->willReturnCallback(function ($path) use ($targetBundleMtime) {
				if ($path === 'targetBundlePath') {
					return $targetBundleMtime;
				}
				throw new \Exception('unexpected path');
			});


		$this->assertSame($expected,
			$this->invokePrivate($certificateManager, 'needsRebundling')
		);
	}

	public static function dataTestNeedRebundling(): array {
		return [
			//values: CaBundleMtime, targetBundleMtime, targetBundleExists, expected

			[10, 30, true, false],
			[10, 15, true, false],
			[10, 8, true, true],
			[10, 30, true, false],
			[10, 8, true, true],

			// if no target bundle exists we always build a new one
			[10, 30, false, true],
			[10, 15, false, true],
			[10, 8, false, true],
			[10, 30, false, true],
			[10, 8, false, true],
		];
	}
}

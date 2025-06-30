<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Command\Integrity;

use OC\Core\Command\Integrity\SignApp;
use OC\IntegrityCheck\Checker;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OCP\IURLGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class SignAppTest extends TestCase {
	/** @var Checker|\PHPUnit\Framework\MockObject\MockObject */
	private $checker;
	/** @var SignApp */
	private $signApp;
	/** @var FileAccessHelper|\PHPUnit\Framework\MockObject\MockObject */
	private $fileAccessHelper;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;

	protected function setUp(): void {
		parent::setUp();
		$this->checker = $this->createMock(Checker::class);
		$this->fileAccessHelper = $this->createMock(FileAccessHelper::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->signApp = new SignApp(
			$this->checker,
			$this->fileAccessHelper,
			$this->urlGenerator
		);
	}

	public function testExecuteWithMissingPath(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['path', null],
				['privateKey', 'PrivateKey'],
				['certificate', 'Certificate'],
			]);

		$calls = [
			'This command requires the --path, --privateKey and --certificate.',
			'*',
			'*',
		];
		$outputInterface
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $message) use (&$calls): void {
				$expected = array_shift($calls);
				if ($expected === '*') {
					$this->assertNotEmpty($message);
				} else {
					$this->assertEquals($expected, $message);
				}
			});

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithMissingPrivateKey(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['path', 'AppId'],
				['privateKey', null],
				['certificate', 'Certificate'],
			]);

		$calls = [
			'This command requires the --path, --privateKey and --certificate.',
			'*',
			'*',
		];
		$outputInterface
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $message) use (&$calls): void {
				$expected = array_shift($calls);
				if ($expected === '*') {
					$this->assertNotEmpty($message);
				} else {
					$this->assertEquals($expected, $message);
				}
			});

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithMissingCertificate(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['path', 'AppId'],
				['privateKey', 'PrivateKey'],
				['certificate', null],
			]);

		$calls = [
			'This command requires the --path, --privateKey and --certificate.',
			'*',
			'*',
		];
		$outputInterface
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $message) use (&$calls): void {
				$expected = array_shift($calls);
				if ($expected === '*') {
					$this->assertNotEmpty($message);
				} else {
					$this->assertEquals($expected, $message);
				}
			});

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithNotExistingPrivateKey(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['path', 'AppId'],
				['privateKey', 'privateKey'],
				['certificate', 'certificate'],
			]);

		$this->fileAccessHelper
			->expects($this->any())
			->method('file_get_contents')
			->willReturnMap([
				['privateKey', false],
			]);


		$outputInterface
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $message): void {
				$this->assertEquals('Private key "privateKey" does not exists.', $message);
			});

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithNotExistingCertificate(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['path', 'AppId'],
				['privateKey', 'privateKey'],
				['certificate', 'certificate'],
			]);

		$this->fileAccessHelper
			->expects($this->any())
			->method('file_get_contents')
			->willReturnMap([
				['privateKey', file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/core.key')],
				['certificate', false],
			]);

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $message): void {
				$this->assertEquals('Certificate "certificate" does not exists.', $message);
			});

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithException(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['path', 'AppId'],
				['privateKey', 'privateKey'],
				['certificate', 'certificate'],
			]);

		$this->fileAccessHelper
			->expects($this->any())
			->method('file_get_contents')
			->willReturnMap([
				['privateKey', file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/core.key')],
				['certificate', \OC::$SERVERROOT . '/tests/data/integritycheck/core.crt'],
			]);

		$this->checker
			->expects($this->once())
			->method('writeAppSignature')
			->willThrowException(new \Exception('My error message'));

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $message): void {
				$this->assertEquals('Error: My error message', $message);
			});

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecute(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['path', 'AppId'],
				['privateKey', 'privateKey'],
				['certificate', 'certificate'],
			]);

		$this->fileAccessHelper
			->expects($this->any())
			->method('file_get_contents')
			->willReturnMap([
				['privateKey', file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/core.key')],
				['certificate', \OC::$SERVERROOT . '/tests/data/integritycheck/core.crt'],
			]);

		$this->checker
			->expects($this->once())
			->method('writeAppSignature');

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $message): void {
				$this->assertEquals('Successfully signed "AppId"', $message);
			});

		$this->assertSame(0, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}
}

<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Command\Integrity;

use OC\Core\Command\Integrity\SignCore;
use OC\IntegrityCheck\Checker;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class SignCoreTest extends TestCase {
	/** @var Checker|\PHPUnit\Framework\MockObject\MockObject */
	private $checker;
	/** @var FileAccessHelper|\PHPUnit\Framework\MockObject\MockObject */
	private $fileAccessHelper;
	/** @var SignCore */
	private $signCore;

	protected function setUp(): void {
		parent::setUp();
		$this->checker = $this->createMock(Checker::class);
		$this->fileAccessHelper = $this->createMock(FileAccessHelper::class);
		$this->signCore = new SignCore(
			$this->checker,
			$this->fileAccessHelper
		);
	}

	public function testExecuteWithMissingPrivateKey(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['privateKey', null],
				['certificate', 'certificate'],
				['path', 'certificate'],
			]);

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $message): void {
				$this->assertEquals('--privateKey, --certificate and --path are required.', $message);
			});

		$this->assertSame(1, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithMissingCertificate(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['privateKey', 'privateKey'],
				['certificate', null],
				['path', 'certificate'],
			]);

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $message): void {
				$this->assertEquals('--privateKey, --certificate and --path are required.', $message);
			});

		$this->assertSame(1, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithNotExistingPrivateKey(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['privateKey', 'privateKey'],
				['certificate', 'certificate'],
				['path', 'certificate'],
			]);

		$this->fileAccessHelper
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

		$this->assertSame(1, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithNotExistingCertificate(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['privateKey', 'privateKey'],
				['certificate', 'certificate'],
				['path', 'certificate'],
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

		$this->assertSame(1, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithException(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['privateKey', 'privateKey'],
				['certificate', 'certificate'],
				['path', 'certificate'],
			]);

		$this->fileAccessHelper
			->expects($this->any())
			->method('file_get_contents')
			->willReturnMap([
				['privateKey', file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/core.key')],
				['certificate', file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/core.crt')],
			]);

		$this->checker
			->expects($this->once())
			->method('writeCoreSignature')
			->willThrowException(new \Exception('My exception message'));

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $message): void {
				$this->assertEquals('Error: My exception message', $message);
			});

		$this->assertEquals(1, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecute(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->willReturnMap([
				['privateKey', 'privateKey'],
				['certificate', 'certificate'],
				['path', 'certificate'],
			]);

		$this->fileAccessHelper
			->expects($this->any())
			->method('file_get_contents')
			->willReturnMap([
				['privateKey', file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/core.key')],
				['certificate', file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/core.crt')],
			]);

		$this->checker
			->expects($this->once())
			->method('writeCoreSignature');

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $message): void {
				$this->assertEquals('Successfully signed "core"', $message);
			});

		$this->assertEquals(0, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}
}

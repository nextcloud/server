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
			->withConsecutive(
				['path'],
				['privateKey'],
				['certificate'],
			)->willReturnOnConsecutiveCalls(
				null,
				'PrivateKey',
				'Certificate',
			);

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['This command requires the --path, --privateKey and --certificate.']
			);

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithMissingPrivateKey(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['path'],
				['privateKey'],
				['certificate'],
			)->willReturnOnConsecutiveCalls(
				'AppId',
				null,
				'Certificate',
			);

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['This command requires the --path, --privateKey and --certificate.']
			);

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithMissingCertificate(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['path'],
				['privateKey'],
				['certificate'],
			)->willReturnOnConsecutiveCalls(
				'AppId',
				'privateKey',
				null,
			);

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['This command requires the --path, --privateKey and --certificate.']
			);

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithNotExistingPrivateKey(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['path'],
				['privateKey'],
				['certificate'],
			)->willReturnOnConsecutiveCalls(
				'AppId',
				'privateKey',
				'certificate',
			);

		$this->fileAccessHelper
			->expects($this->any())
			->method('file_get_contents')
			->withConsecutive(['privateKey'])
			->willReturnOnConsecutiveCalls(false);


		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['Private key "privateKey" does not exists.']
			);

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithNotExistingCertificate(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['path'],
				['privateKey'],
				['certificate'],
			)->willReturnOnConsecutiveCalls(
				'AppId',
				'privateKey',
				'certificate',
			);

		$this->fileAccessHelper
			->expects($this->any())
			->method('file_get_contents')
			->withConsecutive(
				['privateKey'],
				['certificate'],
			)
			->willReturnOnConsecutiveCalls(
				\OC::$SERVERROOT . '/tests/data/integritycheck/core.key',
				false
			);

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['Certificate "certificate" does not exists.']
			);

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithException(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['path'],
				['privateKey'],
				['certificate'],
			)->willReturnOnConsecutiveCalls(
				'AppId',
				'privateKey',
				'certificate',
			);

		$this->fileAccessHelper
			->expects($this->any())
			->method('file_get_contents')
			->withConsecutive(
				['privateKey'],
				['certificate'],
			)
			->willReturnOnConsecutiveCalls(
				file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/core.key'),
				file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/core.crt'),
			);

		$this->checker
			->expects($this->once())
			->method('writeAppSignature')
			->willThrowException(new \Exception('My error message'));

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['Error: My error message']
			);

		$this->assertSame(1, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecute(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['path'],
				['privateKey'],
				['certificate'],
			)->willReturnOnConsecutiveCalls(
				'AppId',
				'privateKey',
				'certificate',
			);

		$this->fileAccessHelper
			->expects($this->any())
			->method('file_get_contents')
			->withConsecutive(
				['privateKey'],
				['certificate'],
			)
			->willReturnOnConsecutiveCalls(
				file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/core.key'),
				file_get_contents(\OC::$SERVERROOT . '/tests/data/integritycheck/core.crt'),
			);

		$this->checker
			->expects($this->once())
			->method('writeAppSignature');

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['Successfully signed "AppId"']
			);

		$this->assertSame(0, self::invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]));
	}
}

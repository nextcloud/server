<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

	public function testExecuteWithMissingPrivateKey() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['privateKey'],
				['certificate'],
				['path'],
			)->willReturnOnConsecutiveCalls(
				null,
				'certificate',
				'certificate',
			);

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['--privateKey, --certificate and --path are required.']
			);

		$this->assertSame(1, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithMissingCertificate() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['privateKey'],
				['certificate'],
				['path'],
			)->willReturnOnConsecutiveCalls(
				'privateKey',
				null,
				'certificate',
			);

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['--privateKey, --certificate and --path are required.']
			);

		$this->assertSame(1, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithNotExistingPrivateKey() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['privateKey'],
				['certificate'],
				['path'],
			)->willReturnOnConsecutiveCalls(
				'privateKey',
				'certificate',
				'certificate',
			);

		$this->fileAccessHelper
			->expects($this->any())
			->method('file_get_contents')
			->withConsecutive(
				['privateKey'],
			)
			->willReturnOnConsecutiveCalls(
				false,
			);

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['Private key "privateKey" does not exists.']
			);

		$this->assertSame(1, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithNotExistingCertificate() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['privateKey'],
				['certificate'],
				['path'],
			)->willReturnOnConsecutiveCalls(
				'privateKey',
				'certificate',
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
				false,
			);

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['Certificate "certificate" does not exists.']
			);

		$this->assertSame(1, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecuteWithException() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['privateKey'],
				['certificate'],
				['path'],
			)->willReturnOnConsecutiveCalls(
				'privateKey',
				'certificate',
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
			->method('writeCoreSignature')
			->willThrowException(new \Exception('My exception message'));

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['Error: My exception message']
			);

		$this->assertEquals(1, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}

	public function testExecute() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->exactly(3))
			->method('getOption')
			->withConsecutive(
				['privateKey'],
				['certificate'],
				['path'],
			)->willReturnOnConsecutiveCalls(
				'privateKey',
				'certificate',
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
			->method('writeCoreSignature');

		$outputInterface
			->expects($this->any())
			->method('writeln')
			->withConsecutive(
				['Successfully signed "core"']
			);

		$this->assertEquals(0, self::invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]));
	}
}
